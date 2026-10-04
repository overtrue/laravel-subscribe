<?php

namespace Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Overtrue\LaravelSubscribe\Events\Subscribed;
use Overtrue\LaravelSubscribe\Events\Unsubscribed;
use Overtrue\LaravelSubscribe\SubscribeServiceProvider;
use Overtrue\LaravelSubscribe\Subscription;
use PHPUnit\Framework\Attributes\DataProvider;

class CompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        config(['auth.providers.users.model' => User::class]);
    }

    public function test_provider_registers_configuration_and_publishable_files(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(SubscribeServiceProvider::class));
        $this->assertSame(Subscription::class, config('subscribe.subscription_model'));
        $this->assertSame('subscriptions', config('subscribe.subscriptions_table'));
        $this->assertSame('user_id', config('subscribe.user_foreign_key'));
        $this->assertFalse(config('subscribe.uuids'));
        $this->assertTrue(Schema::hasColumns('subscriptions', [
            'id', 'user_id', 'subscribable_id', 'subscribable_type', 'created_at', 'updated_at',
        ]));

        $this->assertSame([
            dirname(__DIR__).'/config/subscribe.php' => config_path('subscribe.php'),
        ], ServiceProvider::pathsToPublish(SubscribeServiceProvider::class, 'config'));
        $this->assertSame([
            dirname(__DIR__).'/migrations/' => database_path('migrations'),
        ], ServiceProvider::pathsToPublish(SubscribeServiceProvider::class, 'migrations'));
    }

    public function test_repeated_subscriptions_and_toggles_preserve_events_and_rows(): void
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        $user->subscribe($post);
        $user->subscribe($post);

        $this->assertSame(1, $user->subscriptions()->count());
        Event::assertDispatched(Subscribed::class, 1);

        $user->toggleSubscribe($post);

        $this->assertFalse($user->hasSubscribed($post));
        $this->assertSame(0, $user->subscriptions()->count());
        Event::assertDispatched(Unsubscribed::class, 1);

        $user->unsubscribe($post);
        Event::assertDispatched(Unsubscribed::class, 1);

        $user->toggleSubscribe($post);

        $this->assertTrue($user->hasSubscribed($post));
        $this->assertSame(1, $user->subscriptions()->count());
        Event::assertDispatched(Subscribed::class, 2);
    }

    public function test_polymorphic_subscriptions_keep_matching_ids_separate(): void
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'A post']);
        $book = Book::create(['title' => 'A book']);

        $this->assertSame($post->getKey(), $book->getKey());
        $user->subscribe($post);

        $this->assertTrue($user->hasSubscribed($post));
        $this->assertFalse($user->hasSubscribed($book));
        $this->assertSame(0, $book->subscribers()->count());

        $user->subscribe($book);
        $user->unsubscribe($post);

        $this->assertFalse($user->hasSubscribed($post));
        $this->assertTrue($user->hasSubscribed($book));
        $this->assertSame(1, $user->subscriptions()->withType(Book::class)->count());
        $this->assertSame(0, $user->subscriptions()->withType(Post::class)->count());
    }

    public function test_status_attachment_returns_the_same_single_model(): void
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'A post']);
        $user->subscribe($post);

        $this->assertSame($post, $user->attachSubscriptionStatus($post));
        $this->assertTrue($post->has_subscribed);
    }

    #[DataProvider('collectionInputs')]
    public function test_status_attachment_supports_collection_and_pagination_inputs(string $input): void
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Subscribed']);
        Post::create(['title' => 'Not subscribed']);
        $user->subscribe($post);

        $query = Post::orderBy('id');
        $posts = match ($input) {
            'collection' => $query->get(),
            'array' => $query->get()->all(),
            'lazy' => $query->cursor(),
            'length-aware paginator' => $query->paginate(10),
            'simple paginator' => $query->simplePaginate(10),
            'cursor paginator' => $query->cursorPaginate(10),
        };

        $result = $user->attachSubscriptionStatus($posts);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result[0]->has_subscribed);
        $this->assertFalse($result[1]->has_subscribed);
    }

    public static function collectionInputs(): array
    {
        return [
            'collection' => ['collection'],
            'array' => ['array'],
            'lazy' => ['lazy'],
            'length-aware paginator' => ['length-aware paginator'],
            'simple paginator' => ['simple paginator'],
            'cursor paginator' => ['cursor paginator'],
        ];
    }
}

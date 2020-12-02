<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Overtrue\LaravelSubscribe\Events\Subscribed;
use Overtrue\LaravelSubscribe\Events\Unsubscribed;

/**
 * Class FeatureTest.
 */
class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function testBasicFeatures()
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        $user->subscribe($post);

        Event::assertDispatched(Subscribed::class, function ($event) use ($user, $post) {
            return $event->subscription->subscribable instanceof Post
                && $event->subscription->user instanceof User
                && $event->subscription->user->id === $user->id
                && $event->subscription->subscribable->id === $post->id;
        });

        $this->assertTrue($user->hasSubscribed($post));
        $this->assertTrue($post->isSubscribedBy($user));

        $user->unsubscribe($post);

        Event::assertDispatched(Unsubscribed::class, function ($event) use ($user, $post) {
            return $event->subscription->subscribable instanceof Post
                && $event->subscription->user instanceof User
                && $event->subscription->user->id === $user->id
                && $event->subscription->subscribable->id === $post->id;
        });
    }

    public function test_unsubscribe_features()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user2->subscribe($post);
        $user3->subscribe($post);
        $user1->subscribe($post);

        $user1->unsubscribe($post);

        $this->assertFalse($user1->hasSubscribed($post));
        $this->assertTrue($user2->hasSubscribed($post));
        $this->assertTrue($user3->hasSubscribed($post));
    }

    public function test_aggregations()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->subscribe($post1);
        $user->subscribe($post2);
        $user->subscribe($book1);
        $user->subscribe($book2);

        $this->assertSame(4, $user->subscriptions()->count());
        $this->assertSame(2, $user->subscriptions()->withType(Book::class)->count());
    }

    public function test_object_subscribers()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user1->subscribe($post);
        $user2->subscribe($post);

        $this->assertCount(2, $post->subscribers);
        $this->assertSame('overtrue', $post->subscribers[0]['name']);
        $this->assertSame('allen', $post->subscribers[1]['name']);

        $sqls = $this->getQueryLog(function () use ($user1, $user2, $user3, $post) {
            $this->assertTrue($post->isSubscribedBy($user1));
            $this->assertTrue($post->isSubscribedBy($user2));
            $this->assertFalse($post->isSubscribedBy($user3));
        });

        $this->assertEmpty($sqls->all());
    }

    public function test_eager_loading()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->subscribe($post1);
        $user->subscribe($post2);
        $user->subscribe($book1);
        $user->subscribe($book2);

        // start recording
        $sqls = $this->getQueryLog(function () use ($user) {
            $user->load('subscriptions.subscribable');
        });

        $this->assertSame(3, $sqls->count());

        // from loaded relations
        $sqls = $this->getQueryLog(function () use ($user, $post1) {
            $user->hasSubscribed($post1);
        });

        $this->assertEmpty($sqls->all());
    }

    /**
     * @param \Closure $callback
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getQueryLog(\Closure $callback): \Illuminate\Support\Collection
    {
        $sqls = \collect([]);
        \DB::listen(function ($query) use ($sqls) {
            $sqls->push(['sql' => $query->sql, 'bindings' => $query->bindings]);
        });

        $callback();

        return $sqls;
    }
}

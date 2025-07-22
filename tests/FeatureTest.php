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
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function test_basic_features()
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

    public function test_user_can_also_subscribe_user()
    {
        /* @var \Tests\User $user1 */
        $user1 = User::create(['name' => 'overtrue']);
        /* @var \Tests\User $user2 */
        $user2 = User::create(['name' => 'allen']);

        $user1->subscribe($user2);

        $this->assertTrue($user1->hasSubscribed($user2));
        $this->assertTrue($user2->isSubscribedBy($user1));
    }

    public function test_attach_subscription_status()
    {
        $post1 = Post::create(['title' => 'title 1']);
        $post2 = Post::create(['title' => 'title 2']);
        $post3 = Post::create(['title' => 'title 3']);
        $post4 = Post::create(['title' => 'title 4']);

        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);

        $user->subscribe($post2);
        $user->subscribe($post3);

        $list = Post::all();

        $sqls = $this->getQueryLog(
            function () use ($user, $list) {
                $user->attachSubscriptionStatus($list);
            }
        );

        $this->assertSame(1, $sqls->count());

        $this->assertFalse($list[0]->has_subscribed);
        $this->assertTrue($list[1]->has_subscribed);
        $this->assertTrue($list[2]->has_subscribed);
        $this->assertFalse($list[3]->has_subscribed);

        // with custom resolver
        $list = \collect([['post' => $post1], ['post' => $post2], ['post' => $post3]]);

        $user->attachSubscriptionStatus($list, fn ($item) => $item['post']);

        $this->assertTrue($list[1]['post']['has_subscribed']);
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

    public function test_get_recent_subscribers()
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user */
        $user = User::create(['name' => 'user']);

        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user1 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user2 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user3 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user4 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user5 */
        $user1 = User::create(['name' => 'user1']);
        $user2 = User::create(['name' => 'user2']);
        $user3 = User::create(['name' => 'user3']);
        $user4 = User::create(['name' => 'user4']);
        $user5 = User::create(['name' => 'user5']);

        // today subscribers
        $user1->subscribe($user);
        $user2->subscribe($user);
        $user3->subscribe($user);

        // next week subscribers
        $this->travel(7)->days();
        $user4->subscribe($user);
        $user5->subscribe($user);

        $this->travelBack();

        $from = \now()->startofDay();
        $to = \now()->endofDay();

        $allSubscribers = $user->subscribers;
        $todaySubscribedUsers = $user->subscribers()->wherePivotBetween('subscriptions.created_at', [$from, $to])->get();
        $todaySubscribedUsersCount = $user->subscribers()->wherePivotBetween('subscriptions.created_at', [$from, $to])->count();

        $this->assertCount(5, $allSubscribers);
        $this->assertCount(3, $todaySubscribedUsers);
        $this->assertSame(3, $todaySubscribedUsersCount);
        $this->assertSame($user1->name, $todaySubscribedUsers[0]->name);
        $this->assertSame($user2->name, $todaySubscribedUsers[1]->name);
    }

    public function test_get_post_popular_subscribable_object()
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user1 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user2 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user3 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user4 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user5 */
        $user1 = User::create(['name' => 'user1']);
        $user2 = User::create(['name' => 'user2']);
        $user3 = User::create(['name' => 'user3']);
        $user4 = User::create(['name' => 'user4']);
        $user5 = User::create(['name' => 'user5']);

        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post1 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post2 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post3 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post4 */
        $post1 = Post::create(['title' => 'post1']);
        $post2 = Post::create(['title' => 'post1']);
        $post3 = Post::create(['title' => 'post1']);
        $post4 = Post::create(['title' => 'post1']);

        // post2: 2 subscribers
        $user2->subscribe($post2);
        $user3->subscribe($post2);

        // post3: 0 subscribers
        // post4: 1 subscribers
        $user4->subscribe($post4);

        // post1: 3 subscribers
        $user1->subscribe($post1);
        $user2->subscribe($post1);
        $user3->subscribe($post1);

        $postsOrderBySubscribersCount = Post::orderBySubscribersCountDesc()->get();
        // same as:
        // $postsOrderBySubscribersCount = Post::withCount('subscribers')->orderByDesc('subscribers_count')->get();

        $this->assertSame($post1->title, $postsOrderBySubscribersCount[0]->title);
        $this->assertEquals(3, $postsOrderBySubscribersCount[0]->subscribers_count);
        $this->assertSame($post2->title, $postsOrderBySubscribersCount[1]->title);
        $this->assertEquals(2, $postsOrderBySubscribersCount[1]->subscribers_count);
        $this->assertSame($post4->title, $postsOrderBySubscribersCount[2]->title);
        $this->assertEquals(1, $postsOrderBySubscribersCount[2]->subscribers_count);
        $this->assertSame($post3->title, $postsOrderBySubscribersCount[3]->title);
        $this->assertEquals(0, $postsOrderBySubscribersCount[3]->subscribers_count);

        $mostPopularPost = Post::orderBySubscribersCountDesc()->first();
        // same as:
        // $mostPopularPost = Post::withCount('subscribers')->orderByDesc('subscribers_count')->first();
        $this->assertSame($post1->title, $mostPopularPost->title);
        $this->assertEquals(3, $mostPopularPost->subscribers_count);
    }

    public function test_get_user_subscribed_items()
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user1 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user2 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user3 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user4 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|\Overtrue\LaravelSubscribe\Traits\Subscriber $user5 */
        $user1 = User::create(['name' => 'user1']);
        $user2 = User::create(['name' => 'user2']);

        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post1 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post2 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post3 */
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $post4 */
        $post1 = Post::create(['title' => 'post1']);
        $post2 = Post::create(['title' => 'post1']);
        $post3 = Post::create(['title' => 'post1']);
        $post4 = Post::create(['title' => 'post1']);

        $user1->subscribe($post1);
        $user1->subscribe($post2);
        $user1->subscribe($post3);
        $user2->subscribe($post4);

        // raw
        $user1SubscribedPosts = Post::whereHas('subscribers', fn ($q) => $q->where('user_id', $user1->id))->get();

        $this->assertCount(3, $user1SubscribedPosts);

        $this->assertSame($post1->id, $user1SubscribedPosts[0]->id);
        $this->assertSame($post2->id, $user1SubscribedPosts[1]->id);
        $this->assertSame($post3->id, $user1SubscribedPosts[2]->id);

        // scope
        $user1SubscribedPosts = Post::subscribedBy($user1)->get();

        $this->assertCount(3, $user1SubscribedPosts);

        $this->assertSame($post1->id, $user1SubscribedPosts[0]->id);
        $this->assertSame($post2->id, $user1SubscribedPosts[1]->id);
        $this->assertSame($post3->id, $user1SubscribedPosts[2]->id);

        // scope
        $posts = Post::hasSubscribers([$user1, $user2])->get();

        $this->assertCount(4, $posts);

        $this->assertSame($post1->id, $posts[0]->id);
        $this->assertSame($post2->id, $posts[1]->id);
        $this->assertSame($post3->id, $posts[2]->id);
        $this->assertSame($post4->id, $posts[3]->id);
        $this->assertSame($user2->id, $posts[3]->subscriptionsHistory[0]->user_id);
    }

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

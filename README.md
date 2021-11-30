Laravel Subscribe
---

:email: User subscribe/unsubscribe feature for Laravel Application.

![CI](https://github.com/overtrue/laravel-subscribe/workflows/CI/badge.svg)


[![Sponsor me](https://github.com/overtrue/overtrue/blob/master/sponsor-me-button-s.svg?raw=true)](https://github.com/sponsors/overtrue)

## Installing

```shell
$ composer require overtrue/laravel-subscribe -vvv
```

### Configuration

This step is optional

```php
$ php artisan vendor:publish --provider="Overtrue\\LaravelSubscribe\\SubscribeServiceProvider" --tag=config
```

### Migrations

**You need to publish the migration files for use the package:**

```php
$ php artisan vendor:publish --provider="Overtrue\\LaravelSubscribe\\SubscribeServiceProvider" --tag=migrations
```


## Usage

### Traits

#### `Overtrue\LaravelSubscribe\Traits\Subscriber`

```php

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Overtrue\LaravelSubscribe\Traits\Subscriber;

class User extends Authenticatable
{
    use Subscriber;
    
    <...>
}
```

#### `Overtrue\LaravelSubscribe\Traits\Subscribable`

```php
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Traits\Subscribable;

class Post extends Model
{
    use Subscribable;

    <...>
}
```

### API

```php
$user = User::find(1);
$post = Post::find(2);

$user->subscribe($post);
$user->unsubscribe($post);
$user->toggleSubscribe($post);

$user->hasSubscribed($post); 
$post->isSubscribedBy($user); 
```

##### Get object subscribers:

```php
foreach($post->subscribers as $user) {
    // echo $user->name;
}
```

##### Aggregations

```php
// all
$user->subscriptions()->count(); 

// with type
$user->subscriptions()->withType(Post::class)->count(); 

// subscribers count
$post->subscribers()->count();
```

List with `*_count` attribute:

```php
$users = User::withCount('subscriptions')->get();

foreach($users as $user) {
    echo $user->subscriptions_count;
}
```

### Order by subscribers count

You can query subscribable model order by subscribers count with following methods:

- `orderBySubscribersCountDesc()`
- `orderBySubscribersCountAsc()`
- `orderBySubscribersCount(string $direction = 'desc')`

example: 

```php
$posts = Post::orderBySubscribersCountDesc()->get();
$mostPopularPost = Post::orderBySubscribersCountDesc()->first();
```

### N+1 issue

To avoid the N+1 issue, you can use eager loading to reduce this operation to just 2 queries. When querying, you may specify which relationships should be eager loaded using the `with` method:

```php
// Subscriber
$users = App\User::with('subscriptions')->get();

foreach($users as $user) {
    $user->hasSubscribed($post);
}

// Subscribable
$posts = App\Post::with('subscriptions')->get();
// or 
$posts = App\Post::with('subscribers')->get();

foreach($posts as $post) {
    $post->isSubscribedBy($user);
}
```

### Attach the subscription status to subscribable collection

You can use `Subscriber::attachSubscriptionStatus(Collection $subscribeables)` to attach the user subscription status, it will set `has_subscribed` attribute to each model of `$subscribables`:

#### For model

```php
$user1 = User::find(1);

$user->attachSubscriptionStatus($user1);

// result
[
    "id" => 1
    "name" => "user1"
    "private" => false
    "created_at" => "2021-06-07T15:06:47.000000Z"
    "updated_at" => "2021-06-07T15:06:47.000000Z"
    "has_subscribed" => true  
  ]
```

#### For `Collection | Paginator | LengthAwarePaginator | array`:

```php
$user = auth()->user();

$posts = Post::oldest('id')->get();

$posts = $user->attachSubscriptionStatus($posts);

$posts = $posts->toArray();

// result
[
  [
    "id" => 1
    "title" => "title 1"
    "created_at" => "2021-06-07T15:06:47.000000Z"
    "updated_at" => "2021-06-07T15:06:47.000000Z"
    "has_subscribed" => true  
  ],
  [
    "id" => 2
    "title" => "title 2"
    "created_at" => "2021-06-07T15:06:47.000000Z"
    "updated_at" => "2021-06-07T15:06:47.000000Z"
    "has_subscribed" => true
  ],
  [
    "id" => 3
    "title" => "title 3"
    "created_at" => "2021-06-07T15:06:47.000000Z"
    "updated_at" => "2021-06-07T15:06:47.000000Z"
    "has_subscribed" => false
  ],
  [
    "id" => 4
    "title" => "title 4"
    "created_at" => "2021-06-07T15:06:47.000000Z"
    "updated_at" => "2021-06-07T15:06:47.000000Z"
    "has_subscribed" => false
  ],
]
```

#### For pagination

```php
$posts = Post::paginate(20);

$user->attachSubscriptionStatus($posts);
```

### Events

| **Event** | **Description** |
| --- | --- |
|  `Overtrue\LaravelSubscribe\Events\Subscribed` | Triggered when the relationship is created. |
|  `Overtrue\LaravelSubscribe\Events\Unsubscribed` | Triggered when the relationship is deleted. |

## Related packages

- Follow: [overtrue/laravel-follow](https://github.com/overtrue/laravel-follow)
- Like: [overtrue/laravel-like](https://github.com/overtrue/laravel-like)
- Favorite: [overtrue/laravel-favorite](https://github.com/overtrue/laravel-favorite)
- Subscribe: [overtrue/laravel-subscribe](https://github.com/overtrue/laravel-subscribe)
- Vote: [overtrue/laravel-vote](https://github.com/overtrue/laravel-vote)
- Bookmark: overtrue/laravel-bookmark (working in progress)

## :heart: Sponsor me 

[![Sponsor me](https://github.com/overtrue/overtrue/blob/master/sponsor-me.svg?raw=true)](https://github.com/sponsors/overtrue)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/overtrue)

## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/overtrue)


## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/overtrue/laravel-subscribes/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/overtrue/laravel-subscribes/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT

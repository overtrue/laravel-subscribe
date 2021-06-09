<?php

namespace Overtrue\LaravelSubscribe;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Overtrue\LaravelSubscribe\Events\Subscribed;
use Overtrue\LaravelSubscribe\Events\Unsubscribed;

/**
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $subscriber
 * @property \Illuminate\Database\Eloquent\Model $subscribable
 */
class Subscription extends Model
{
    protected $guarded = [];

    protected $dispatchesEvents = [
        'created' => Subscribed::class,
        'deleted' => Unsubscribed::class,
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = \config('subscribe.subscriptions_table');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function (Subscription $subscription) {
            $userForeignKey = \config('subscribe.user_foreign_key');
            $subscription->{$userForeignKey} = $subscription->{$userForeignKey} ?: auth()->id();

            if (\config('subscribe.uuids')) {
                $subscription->{$subscription->getKeyName()} = $subscription->{$subscription->getKeyName()} ?: (string) Str::orderedUuid();
            }
        });
    }

    public function subscribable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('subscribe.user_foreign_key'));
    }

    public function subscriber(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->user();
    }

    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('subscribable_type', app($type)->getMorphClass());
    }
}

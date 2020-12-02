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

    /**
     * @var string[]
     */
    protected $dispatchesEvents = [
        'created' => Subscribed::class,
        'deleted' => Unsubscribed::class,
    ];

    /**
     * @param array $attributes
     */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subscribable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('subscribe.user_foreign_key'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscriber()
    {
        return $this->user();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithType(Builder $query, string $type)
    {
        return $query->where('subscribable_type', app($type)->getMorphClass());
    }
}

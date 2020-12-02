<?php

namespace Overtrue\LaravelSubscribe\Traits;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Subscription;

/**
 * @property \Illuminate\Database\Eloquent\Collection $subscriptions
 * @property \Illuminate\Database\Eloquent\Collection $subscribers
 */
trait Subscribable
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isSubscribedBy(Model $user)
    {
        if (\is_a($user, \config('auth.providers.users.model'))) {
            if ($this->relationLoaded('subscribers')) {
                return $this->subscribers->contains($user);
            }

            return tap($this->relationLoaded('subscriptions') ? $this->subscriptions : $this->subscriptions())
                    ->where(\config('subscribe.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions()
    {
        return $this->morphMany(\config('subscribe.subscription_model'), 'subscribable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subscribers()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('subscribe.subscriptions_table'),
            'subscribable_id',
            config('subscribe.user_foreign_key')
        )
            ->where('subscribable_type', $this->getMorphClass());
    }
}

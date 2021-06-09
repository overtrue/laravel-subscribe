<?php

namespace Overtrue\LaravelSubscribe\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $subscriptions
 * @property \Illuminate\Database\Eloquent\Collection $subscribers
 */
trait Subscribable
{
    public function isSubscribedBy(Model $user): bool
    {
        if (\is_a($user, \config('auth.providers.users.model'))) {
            if ($this->relationLoaded('subscribers')) {
                return $this->subscribers->contains($user);
            }

            return !!$this->subscribers()->find($user->getKey());
        }

        return false;
    }

    public function subscribers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
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

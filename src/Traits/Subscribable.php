<?php

namespace Overtrue\LaravelSubscribe\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $subscribers
 *
 * @method static \Illuminate\Database\Eloquent\Builder orderBySubscribersCountDesc()
 * @method static \Illuminate\Database\Eloquent\Builder orderBySubscribersCountAsc()
 * @method static \Illuminate\Database\Eloquent\Builder orderBySubscribersCount(string $direction = 'desc')
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

    public function scopeHasSubscribers($query, array|int|Model $users)
    {
        return $this->scopeSubscribedBy($query, $users);
    }

    public function scopeSubscribedBy($query, array|int|Model $user)
    {
        $ids = [];
        foreach (\is_array($user) ? $user : [$user] as $u) {
            if (\is_a($u, \config('auth.providers.users.model'))) {
                $u = $u->getKey();
            }

            if (\is_string($u) || \is_int($u)) {
                $ids[] = $u;
            }
        }

        if (empty($ids)) {
            throw new \InvalidArgumentException('User must be an instance of '.\config('auth.providers.users.model').' or an integer');
        }

        return $query->with('subscriptionsHistory')->whereHas('subscriptionsHistory', fn ($q) => $q->whereIn('user_id', $ids));
    }

    public function scopeOrderBySubscribersCount($query, string $direction = 'desc')
    {
        return $query->withCount('subscribers')->orderBy('subscribers_count', $direction);
    }

    public function scopeOrderBySubscribersCountDesc($query)
    {
        return $this->scopeOrderBySubscribersCount($query, 'desc');
    }

    public function scopeOrderBySubscribersCountAsc($query)
    {
        return $this->scopeOrderBySubscribersCount($query, 'asc');
    }

    public function subscribers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('subscribe.subscriptions_table'),
            'subscribable_id',
            config('subscribe.user_foreign_key')
        )
            ->where('subscribable_type', $this->getMorphClass())
            ->withPivot(['subscribable_id', 'subscribable_type', 'user_id', 'created_at', 'updated_at']);
    }

    public function subscriptionsHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('subscribe.subscription_model'), 'subscribable_id')
            ->where('subscribable_type', $this->getMorphClass());
    }
}

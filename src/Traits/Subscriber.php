<?php

namespace Overtrue\LaravelSubscribe\Traits;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;

/**
 * @property \Illuminate\Database\Eloquent\Collection $subscriptions
 */
trait Subscriber
{
    public function subscribe(Model $object)
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|Model $object */
        if (! $this->hasSubscribed($object)) {
            $subscribe = app(config('subscribe.subscription_model'));
            $subscribe->{config('subscribe.user_foreign_key')} = $this->getKey();
            $subscribe->subscribable_id = $object->getKey();
            $subscribe->subscribable_type = $object->getMorphClass();

            $this->subscriptions()->save($subscribe);
        }
    }

    /**
     * @throws \Exception
     */
    public function unsubscribe(Model $object)
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable|Model $object */
        $relation = $this->subscriptions()
            ->where('subscribable_id', $object->getKey())
            ->where('subscribable_type', $object->getMorphClass())
            ->where(config('subscribe.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    /**
     * @throws \Exception
     */
    public function toggleSubscribe(Model $object)
    {
        $this->hasSubscribed($object) ? $this->unsubscribe($object) : $this->subscribe($object);
    }

    public function hasSubscribed(Model $object): bool
    {
        return tap($this->relationLoaded('subscriptions') ? $this->subscriptions : $this->subscriptions())
                ->where('subscribable_id', $object->getKey())
                ->where('subscribable_type', $object->getMorphClass())
                ->count() > 0;
    }

    public function attachSubscriptionStatus($subscribables, ?callable $resolver = null)
    {
        $returnFirst = false;

        switch (true) {
            case $subscribables instanceof Model:
                $returnFirst = true;
                $subscribables = \collect([$subscribables]);
                break;
            case $subscribables instanceof LengthAwarePaginator:
                $subscribables = $subscribables->getCollection();
                break;
            case $subscribables instanceof Paginator:
            case $subscribables instanceof CursorPaginator:
                $subscribables = \collect($subscribables->items());
                break;
            case $subscribables instanceof LazyCollection:
                $subscribables = \collect($subscribables->all());
                break;
            case \is_array($subscribables):
                $subscribables = \collect($subscribables);
                break;
        }

        \abort_if(! ($subscribables instanceof Enumerable), 422, 'Invalid $subscribables type.');

        $subscribed = $this->subscriptions()->get();

        $subscribables->map(
            function ($subscribable) use ($subscribed, $resolver) {
                $resolver = $resolver ?? fn ($m) => $m;
                $subscribable = $resolver($subscribable);

                if ((bool) $subscribable && \in_array(Subscribable::class, \class_uses($subscribable))) {
                    $subscribable->setAttribute(
                        'has_subscribed',
                        $subscribed->where('subscribable_id', $subscribable->getKey())
                            ->where('subscribable_type', $subscribable->getMorphClass())
                            ->count() > 0
                    );
                }
            }
        );

        return $returnFirst ? $subscribables->first() : $subscribables;
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(config('subscribe.subscription_model'), config('subscribe.user_foreign_key'), $this->getKeyName());
    }
}

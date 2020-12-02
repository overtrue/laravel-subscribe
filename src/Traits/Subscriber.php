<?php

namespace Overtrue\LaravelSubscribe\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Overtrue\LaravelSubscribe\Events\Subscribed;
use Overtrue\LaravelSubscribe\Events\UnSubscribed;

/**
 * @property \Illuminate\Database\Eloquent\Collection $subscriptions
 */
trait Subscriber
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function subscribe(Model $object)
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $object*/
        if (!$this->hasSubscribed($object)) {
            $subscribe = app(config('subscribe.subscription_model'));
            $subscribe->{config('subscribe.user_foreign_key')} = $this->getKey();

            $object->subscriptions()->save($subscribe);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @throws \Exception
     */
    public function unsubscribe(Model $object)
    {
        /* @var \Overtrue\LaravelSubscribe\Traits\Subscribable $object*/
        $relation = $object->subscriptions()
            ->where('subscribable_id', $object->getKey())
            ->where('subscribable_type', $object->getMorphClass())
            ->where(config('subscribe.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @throws \Exception
     */
    public function toggleSubscribe(Model $object)
    {
        $this->hasSubscribed($object) ? $this->unsubscribe($object) : $this->subscribe($object);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function hasSubscribed(Model $object)
    {
        return tap($this->relationLoaded('subscriptions') ? $this->subscriptions : $this->subscriptions())
            ->where('subscribable_id', $object->getKey())
            ->where('subscribable_type', $object->getMorphClass())
            ->count() > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subscriptions()
    {
        return $this->hasMany(config('subscribe.subscription_model'), config('subscribe.user_foreign_key'), $this->getKeyName());
    }
}

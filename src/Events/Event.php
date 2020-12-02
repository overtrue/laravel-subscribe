<?php

namespace Overtrue\LaravelSubscribe\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|\Overtrue\LaravelSubscribe\Subscription
     */
    public $subscription;

    /**
     * Event constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $subscription
     */
    public function __construct(Model $subscription)
    {
        $this->subscription = $subscription->refresh();
    }
}

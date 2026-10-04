<?php

namespace Overtrue\LaravelSubscribe\Events;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Subscription;

class Event
{
    /**
     * @var Model|Subscription
     */
    public $subscription;

    /**
     * Event constructor.
     */
    public function __construct(Model $subscription)
    {
        $this->subscription = $subscription->refresh();
    }
}

<?php

/*
 * This file is part of the overtrue/laravel-follow
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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

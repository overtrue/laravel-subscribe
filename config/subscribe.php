<?php

/*
 * This file is part of the overtrue/laravel-subscribe.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

return [
    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /*
     * Table name for subscriptions records.
     */
    'subscriptions_table' => 'subscriptions',

    /*
     * Model name for Subscribe record.
     */
    'subscription_model' => \Overtrue\LaravelSubscribe\Subscription::class,
];

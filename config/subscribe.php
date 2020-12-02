<?php

return [
    /**
     * Use uuid as primary key.
     */
    'uuids' => false,

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

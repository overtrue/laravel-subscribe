<?php

namespace Overtrue\LaravelSubscribe;

use Illuminate\Support\ServiceProvider;

class SubscribeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            \dirname(__DIR__).'/config/subscribe.php' => config_path('subscribe.php'),
        ], 'config');

        $this->publishes([
            \dirname(__DIR__).'/migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            \dirname(__DIR__).'/config/subscribe.php',
            'subscribe'
        );
    }
}

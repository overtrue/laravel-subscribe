<?php

namespace Overtrue\LaravelSubscribe;

use Illuminate\Support\ServiceProvider;

class SubscribeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            \dirname(__DIR__) . '/config/subscribe.php' => config_path('subscribe.php'),
        ], 'config');

        $this->publishes([
            \dirname(__DIR__) . '/migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            \dirname(__DIR__) . '/config/subscribe.php',
            'subscribe'
        );
    }
}

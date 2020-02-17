<?php

namespace Skybluesofa\Followers;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if (class_exists('CreateFollowersTable')) {
            return;
        }

        $stub      = __DIR__ . '/database/migrations/';
        $target    = database_path('migrations') . '/';

        $this->publishes([
            $stub . '2020_02_17_120000_create_followers_table.php'
                => $target . date('Y_m_d_His', time()) . '_create_followers_table.php',
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/config/followers.php' => config_path('followers.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}

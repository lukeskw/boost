<?php

namespace Laravel\Boost;

use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Server\Facades\Mcp;

class BoostServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/boost.php', 'boost'
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        Mcp::local('laravel-boost', Boost::class);

        $this->registerResources();
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'boost');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/boost.php' => config_path('boost.php'),
            ], 'boost-config');
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }
}

<?php

namespace Laravel\Boost;

use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Server\Facades\Mcp;
use Laravel\Roster\Roster;

class BoostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/boost.php', 'boost'
        );

        $this->app->singleton(Roster::class, function () {
            $composerLockPath = base_path('composer.lock');
            $packageLockPath = base_path('package-lock.json');

            $cacheKey = 'boost.roster.scan';
            $lastModified = max(
                file_exists($composerLockPath) ? filemtime($composerLockPath) : 0,
                file_exists($packageLockPath) ? filemtime($packageLockPath) : 0
            );

            $cached = cache()->get($cacheKey);
            if ($cached && isset($cached['timestamp']) && $cached['timestamp'] >= $lastModified) {
                return $cached['roster'];
            }

            $roster = Roster::scan(base_path());
            cache()->put($cacheKey, [
                'roster' => $roster,
                'timestamp' => time(),
            ], now()->addHours(24));

            return $roster;
        });
    }

    public function boot(): void
    {
        /* @phpstan-ignore-next-line */
        Mcp::local('laravel-boost', Boost::class);

        $this->registerPublishing();
        $this->registerCommands();
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/boost.php' => config_path('boost.php'),
            ], 'boost-config');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\StartCommand::class,
                Console\InstallCommand::class,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;
use Laravel\Boost\Middleware\InjectBoost;
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

    public function boot(Router $router): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        /* @phpstan-ignore-next-line */
        Mcp::local('laravel-boost', Boost::class);

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerBrowserLogger();
        $this->hookIntoResponses($router);
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
                Console\ExecuteToolCommand::class,
                Console\AssistCommand::class,
            ]);
        }
    }

    private function registerRoutes()
    {
        Route::post('/_boost/browser-logs', function (Request $request) {
            $logs = $request->input('logs', []);

            /** @var array{type: 'error'|'warn'|'info'|'log'|'table'|'window_error'|'uncaught_error'|'unhandled_rejection', timestamp: string, data: array, url:string, userAgent:string} $log */
            foreach ($logs as $log) {
                Log::channel('browser')->write(
                    level: $this->jsTypeToPsr3($log['type']),
                    message: $this->buildLogMessageFromData($log['data']),
                    context: [
                        'url' => $log['url'],
                        'user_agent' => $log['userAgent'] ?: null,
                        'timestamp' => $log['timestamp'] ?: now()->toIso8601String(),
                    ]
                );
            }

            return response()->json(['status' => 'logged']);
        })->name('boost.browser-logs')->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Build a string message for the log based on various input types. Single dimensional, and multi:
     * "data":[{"message":"Unhandled Promise Rejection","reason":{"name":"TypeError","message":"NetworkError when attempting to fetch resource.","stack":""}}]
     */
    protected function buildLogMessageFromData(array $data): string
    {
        $messages = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedMessage = $this->buildLogMessageFromData($value);
                if ($nestedMessage !== '') {
                    $messages[] = $nestedMessage;
                }
            } elseif (is_string($value) || is_numeric($value)) {
                $messages[] = (string) $value;
            } elseif (is_bool($value)) {
                $messages[] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $messages[] = 'null';
            } elseif (is_object($value)) {
                $messages[] = json_encode($value);
            }
        }

        return implode(' ', $messages);
    }

    protected function registerBrowserLogger(): void
    {
        // Register a custom log channel for browser logs
        config(['logging.channels.browser' => [
            'driver' => 'single',
            'path' => storage_path('logs/browser.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ]]);
    }

    private function jsTypeToPsr3(string $type): string
    {
        return match ($type) {
            'warn' => 'warning',
            'log' => 'debug',
            'table' => 'debug',
            'window_error' => 'error', // TODO: Manage the data differently
            'uncaught_error' => 'error', // TODO: Manage the data differently
            'unhandled_rejection' => 'error', // TODO: Manage the data differently
            default => $type
        };
    }

    private function hookIntoResponses(Router $router): void
    {
        if (config('boost.browser_logs', true) === false) {
            return;
        }

        $router->pushMiddlewareToGroup('web', InjectBoost::class);
    }
}

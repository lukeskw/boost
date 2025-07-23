<?php

declare(strict_types=1);

namespace Laravel\Boost;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
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
        if (! app()->environment('local', 'testing')) {
            return;
        }

        /* @phpstan-ignore-next-line */
        Mcp::local('laravel-boost', Boost::class);

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        //        $this->hookIntoResponses(); // TODO: Only if not disabled in config
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
                Console\ChatCommand::class,
            ]);
        }
    }

    private function registerRoutes()
    {
        Route::post('/_boost/browser-logs', function (Request $request) {
            Log::write($request->input('type'), $request->input('message'));

            return response()->json(['status' => 'logged']);
        })->name('boost.browser-logs');
    }

    private function hookIntoResponses()
    {
        Response::macro('injectBoostBrowserLogger', function () {
            $content = $this->getContent();

            if ($this->shouldInject($content)) {
                $injectedContent = $this->injectScript($content);
                $this->setContent($injectedContent);
            }

            return $this;
        });

        // Register response middleware

        app('router')->pushMiddlewareToGroup('web', function ($request, $next) {
            $response = $next($request);

            if (method_exists($response, 'injectBoostBrowserLogger')) {
                $response->injectBoostBrowserLogger();
            }

            return $response;
        });
    }

    private function shouldInject(string $content): bool
    {
        // Check if it's HTML
        if (! str_contains($content, '<html') && ! str_contains($content, '<head')) {
            return false;
        }

        // Check if already injected
        if (str_contains($content, 'browser-logger-active')) {
            return false;
        }

        return true;
    }

    private function injectScript(string $content): string
    {
        $script = $this->getBrowserLoggerScript();

        // Try to inject before closing </head>
        if (str_contains($content, '</head>')) {
            return str_replace('</head>', $script."\n</head>", $content);
        }

        // Fallback: inject before closing </body>
        if (str_contains($content, '</body>')) {
            return str_replace('</body>', $script."\n</body>", $content);
        }

        return $content.$script;
    }

    private function getBrowserLoggerScript(): string
    {
        $endpoint = route('boost.browser-logs');
        $csrfToken = csrf_token();

        return <<<HTML
<script id="browser-logger-active">
(function() {
    const ENDPOINT = '{$endpoint}';
    const CSRF_TOKEN = '{$csrfToken}';

    // [Same script content as before]

    console.log('🔍 Browser logger active (MCP server detected)');
})();
</script>
HTML;
    }
}

<?php

namespace Laravel\Boost\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Boost\Services\BrowserLogger;

class InjectBoost
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        if ($this->shouldInject($response->getContent())) {
            $injectedContent = $this->injectScript($response->getContent());
            $response->setContent($injectedContent);
        }

        return $response;
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
        $script = BrowserLogger::getScript();

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
}

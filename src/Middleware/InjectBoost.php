<?php

namespace Laravel\Boost\Middleware;

use Closure;
use Illuminate\Http\Request;

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

        return <<<HTML
<script id="browser-logger-active">
(function() {
    const ENDPOINT = '{$endpoint}';
    const logQueue = [];
    let flushTimeout = null;

    console.log('🔍 Browser logger active (MCP server detected). Posting to: ' + ENDPOINT);

    // Store original console methods
    const originalConsole = {
        log: console.log,
        info: console.info,
        error: console.error,
        warn: console.warn,
        table: console.table
    };

    // Helper to safely stringify values
    function safeStringify(obj) {
        const seen = new WeakSet();
        return JSON.stringify(obj, (key, value) => {
            if (typeof value === 'object' && value !== null) {
                if (seen.has(value)) return '[Circular]';
                seen.add(value);
            }
            if (value instanceof Error) {
                return {
                    name: value.name,
                    message: value.message,
                    stack: value.stack
                };
            }
            return value;
        });
    }

    // Batch and send logs
    function flushLogs() {
        if (logQueue.length === 0) return;

        const batch = logQueue.splice(0, logQueue.length);

        fetch(ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ logs: batch })
        }).catch(err => {
            // Silently fail to avoid infinite loops
            originalConsole.error('Failed to send logs:', err);
        });
    }

    // Debounced flush (100ms)
    function scheduleFlush() {
        if (flushTimeout) clearTimeout(flushTimeout);
        flushTimeout = setTimeout(flushLogs, 100);
    }

    // Intercept console methods
    ['log', 'info', 'error', 'warn', 'table'].forEach(method => {
        console[method] = function(...args) {
            // Call original method
            originalConsole[method].apply(console, args);

            // Capture log data
            try {
                logQueue.push({
                    type: method,
                    timestamp: new Date().toISOString(),
                    data: args.map(arg => {
                        try {
                            return typeof arg === 'object' ? JSON.parse(safeStringify(arg)) : arg;
                        } catch (e) {
                            return String(arg);
                        }
                    }),
                    url: window.location.href,
                    userAgent: navigator.userAgent
                });

                scheduleFlush();
            } catch (e) {
                // Fail silently
            }
        };
    });

    // Global error handlers for uncaught errors
    const originalOnError = window.onerror;
    window.onerror = function boostErrorHandler(errorMsg, url, lineNumber, colNumber, error) {
        console.log('window on error work please');
        try {
            logQueue.push({
                type: 'uncaught_error',
                timestamp: new Date().toISOString(),
                data: [{
                    message: errorMsg,
                    filename: url,
                    lineno: lineNumber,
                    colno: colNumber,
                    error: error ? {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    } : null
                }],
                url: window.location.href,
                userAgent: navigator.userAgent
            });

            scheduleFlush();
        } catch (e) {
            // Fail silently
        }

        // Call original handler if it exists
        if (originalOnError && typeof originalOnError === 'function') {
            return originalOnError(errorMsg, url, lineNumber, colNumber, error);
        }

        // Let the error continue to propagate
        return false;
    }
    window.addEventListener('error', (event) => {
        try {
            logQueue.push({
                type: 'window_error',
                timestamp: new Date().toISOString(),
                data: [{
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    error: event.error ? {
                        name: event.error.name,
                        message: event.error.message,
                        stack: event.error.stack
                    } : null
                }],
                url: window.location.href,
                userAgent: navigator.userAgent
            });

            scheduleFlush();
        } catch (e) {
            // Fail silently
        }

        // Let the error continue to propagate
        return false;
    });
    window.addEventListener('unhandledrejection', (event) => {
        try {
            logQueue.push({
                type: 'error',
                timestamp: new Date().toISOString(),
                data: [{
                    message: 'Unhandled Promise Rejection',
                    reason: event.reason instanceof Error ? {
                        name: event.reason.name,
                        message: event.reason.message,
                        stack: event.reason.stack
                    } : event.reason
                }],
                url: window.location.href,
                userAgent: navigator.userAgent
            });

            scheduleFlush();
        } catch (e) {
            // Fail silently
        }

        // Let the rejection continue to propagate
        return false;
    });

    // Flush on page unload
    window.addEventListener('beforeunload', () => {
        if (logQueue.length > 0) {
            navigator.sendBeacon(ENDPOINT, JSON.stringify({ logs: logQueue }));
        }
    });
})();
</script>
HTML;
    }
}

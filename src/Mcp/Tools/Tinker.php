<?php

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Throwable;

class Tinker extends Tool
{
    public function shouldRegister(): bool
    {
        return app()->environment() === 'local';
    }

    public function description(): string
    {
        return 'Execute PHP code in the Laravel application context, similar to artisan tinker. Most useful for debugging issues. Returns the output of the code, as well as whatever is "returned" using "return".';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('code')
            ->description('PHP code to execute (without opening <?php tags)')
            ->required()
            ->integer('timeout')
            ->description('Maximum execution time in seconds (default: 30)');
    }

    /**
     * @param  array<string|int>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $code = str_replace(['<?php', '?>'], '', (string) $arguments['code']);
        $timeout = 30;
        if (! empty($arguments['timeout']) && is_int($arguments['timeout'])) {
            $timeout = $arguments['timeout'];
        }
        $timeout = min(180, $timeout);

        // Set execution timeout
        set_time_limit($timeout);

        // Set memory limit for safety
        ini_set('memory_limit', '128M');

        // Use PCNTL alarm for additional timeout control if available (Unix only)
        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGALRM, function () {
                throw new \Exception('Code execution timed out');
            });
            pcntl_alarm($timeout);
        }

        // Start output buffering to capture any output
        ob_start();

        try {
            // Execute the code and capture the return value
            $result = eval($code);

            // Cancel alarm if set
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            // Get any output that was printed
            $output = ob_get_contents();

            // Clean the output buffer
            ob_end_clean();

            // Prepare the response
            $response = [
                'result' => $result,
                'output' => $output,
                'type' => gettype($result),
            ];

            // If result is an object, include class name
            if (is_object($result)) {
                $response['class'] = get_class($result);
            }

            return ToolResult::json($response);

        } catch (Throwable $e) {
            // Cancel alarm if set
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }

            // Clean the output buffer on error
            ob_end_clean();

            return ToolResult::json([
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp;

use Laravel\Mcp\Server\Tools\ToolResult;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ToolExecutor
{
    public function __construct()
    {
    }

    public function execute(string $toolClass, array $arguments = []): ToolResult
    {
        if (! ToolRegistry::isToolAllowed($toolClass)) {
            return ToolResult::error("Tool not registered or not allowed: {$toolClass}");
        }

        if ($this->shouldUseProcessIsolation()) {
            return $this->executeInProcess($toolClass, $arguments);
        }

        return $this->executeInline($toolClass, $arguments);
    }

    protected function executeInProcess(string $toolClass, array $arguments): ToolResult
    {
        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'boost:execute-tool',
            $toolClass,
            base64_encode(json_encode($arguments)),
        ];

        $process = new Process($command);
        $process->setTimeout($this->getTimeout());

        try {
            $process->mustRun();

            $output = $process->getOutput();
            $decoded = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ToolResult::error('Invalid JSON output from tool process: '.json_last_error_msg());
            }

            // Reconstruct ToolResult from the JSON output
            return $this->reconstructToolResult($decoded);

        } catch (ProcessTimedOutException $e) {
            $process->stop();

            return ToolResult::error("Tool execution timed out after {$this->getTimeout()} seconds");

        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput().$process->getOutput();

            return ToolResult::error("Process tool execution failed: {$errorOutput}");
        }
    }

    protected function executeInline(string $toolClass, array $arguments): ToolResult
    {
        try {
            /** @var \Laravel\Mcp\Server\Tool $tool */
            $tool = app($toolClass);

            return $tool->handle($arguments);
        } catch (\Throwable $e) {
            return ToolResult::error("Inline tool execution failed: {$e->getMessage()}");
        }
    }

    protected function shouldUseProcessIsolation(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return config('boost.process_isolation.enabled', true);
    }

    protected function getTimeout(): int
    {
        return config('boost.process_isolation.timeout', 180);
    }

    /**
     * Reconstruct a ToolResult from JSON data.
     *
     * @param array<string, mixed> $data
     */
    protected function reconstructToolResult(array $data): ToolResult
    {
        if (! isset($data['isError']) || ! isset($data['content'])) {
            return ToolResult::error('Invalid tool result format');
        }

        if ($data['isError']) {
            // Extract the actual text content from the content array
            $errorText = 'Unknown error';
            if (is_array($data['content']) && ! empty($data['content'])) {
                $firstContent = $data['content'][0] ?? [];
                if (is_array($firstContent)) {
                    $errorText = $firstContent['text'] ?? $errorText;
                }
            }

            return ToolResult::error($errorText);
        }

        // Handle successful responses - extract text content
        if (is_array($data['content']) && ! empty($data['content'])) {
            $firstContent = $data['content'][0] ?? [];

            if (is_array($firstContent)) {
                $text = $firstContent['text'] ?? '';

                // Try to detect if it's JSON
                $decoded = json_decode($text, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return ToolResult::json($decoded);
                }

                return ToolResult::text($text);
            }
        }

        return ToolResult::text('');
    }
}

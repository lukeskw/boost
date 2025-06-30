<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Mcp\Tools\Tool;
use Laravel\Mcp\Tools\ToolInputSchema;
use Laravel\Mcp\Tools\ToolResult;
use Laravel\Mcp\Tools\Annotations\IsReadOnly;

#[IsReadOnly()]
class LogReader extends Tool
{
    /**
     * A description of the tool.
     */
    public function description(): string
    {
        return 'Use this tool to tail and grep the local Laravel logs.';
    }

    /**
     * The input schema of the tool.
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->integer('lines')
            ->description('The number of lines to read from the end of the log.')
            ->required();

        $schema->string('log_path')
            ->description('Optional path to the log file. Defaults to storage/logs/laravel.log if not provided.');

        $schema->string('grep')
            ->description('Optional grep pattern to filter log entries. Leave empty to get all lines.');

        return $schema;
    }

    /**
     * Execute the tool call.
     */
    public function handle(array $arguments): ToolResult|Generator
    {
        $numberOfLines = $arguments['lines'];
        $grepPattern = $arguments['grep'] ?? null;

        $logPath = isset($arguments['log_path']) && $arguments['log_path'] !== ''
            ? $arguments['log_path']
            : storage_path('logs/laravel.log');

        if (! str_starts_with($logPath, '/')) {
            $logPath = base_path($logPath);
        }

        if (! $this->logFileExistsAndIsReadable($logPath)) {
            return ToolResult::error("Log file not found or is not readable: {$logPath}");
        }

        if ($grepPattern) {
            $command = ['sh', '-c', "grep ".escapeshellarg($grepPattern)." ".escapeshellarg($logPath)." | tail -n {$numberOfLines}"];
        } else {
            $command = ['tail', '-n', (string) $numberOfLines, $logPath];
        }

        $result = Process::run($command);

        if (! $result->successful()) {
            return ToolResult::error("Failed to read log file. Error: ".trim($result->errorOutput()));
        }

        $output = $result->output();

        if (trim($output) === '') {
            if ($grepPattern) {
                return ToolResult::error("No log entries found matching pattern: {$grepPattern}");
            } else {
                return ToolResult::error('Log file is empty or no entries found.');
            }
        }

        return ToolResult::text(trim($output));
    }

    /**
     * Check if the log file exists and is readable.
     */
    private function logFileExistsAndIsReadable(string $logPath): bool
    {
        return File::exists($logPath) && File::isReadable($logPath);
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Log;
use Laravel\Boost\Concerns\ReadsLogs;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly()]
class BrowserLogs extends Tool
{
    use ReadsLogs;

    public function description(): string
    {
        return 'Read the last N log entries from the BROWSER log. Very helpful for debugging the frontend and JS/Javascript';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->integer('entries')
            ->description('Number of log entries to return.')
            ->required();

        return $schema;
    }

    /**
     * @param  array<string>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $maxEntries = (int) $arguments['entries'];

        if ($maxEntries <= 0) {
            return ToolResult::error('The "entries" argument must be greater than 0.');
        }

        // Locate the correct log file using shared helper.
        $logFile = storage_path('logs/browser.log');

        if (! file_exists($logFile)) {
            return ToolResult::error('No log file found, probably means no logs yet.');
        }

        $entries = $this->readLastLogEntries($logFile, $maxEntries);

        if ($entries === []) {
            return ToolResult::error('Unable to retrieve log entries.');
        }

        return ToolResult::text(implode("\n\n", $entries));
    }
}

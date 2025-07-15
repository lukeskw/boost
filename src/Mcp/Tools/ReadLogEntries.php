<?php

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Boost\Concerns\ReadsLogs;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly]
class ReadLogEntries extends Tool
{
    use ReadsLogs;

    public function description(): string
    {
        return 'Read the last N log entries from the application log, correctly handling multi-line PSR-3 formatted logs. Only works for log files.';
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

        // Determine log file path via helper.
        $logFile = $this->resolveLogFilePath();

        if (! file_exists($logFile)) {
            return ToolResult::error("Log file not found at $logFile");
        }

        $entries = $this->readLastLogEntries($logFile, $maxEntries);

        if ($entries === []) {
            return ToolResult::error('Unable to retrieve log entries.');
        }

        return ToolResult::text(implode("\n\n", $entries));
    }

    // The isNewLogEntry and readLinesReverse helper methods are now provided by the ReadsLogs trait.
}

<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use Laravel\Mcp\Tools\ToolInputSchema;
use Laravel\Mcp\Tools\ToolResponse;
use Generator;

class LogReader extends Tool
{
    /**
     * The name of the tool.
     */
    public function name(): string
    {
        return 'Laravel Log Reader';
    }

    /**
     * A description of the tool.
     */
    public function description(): string
    {
        return 'Use this tool to access the Laravel log for the development environment.';
    }

    /**
     * The input schema of the tool.
     */
    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->string('number_of_lines')
            ->description('The number of lines to read from the end of the log.')
            ->required();

        return $schema;
    }

    /**
     * Execute the tool call.
     *
     * @return ToolResponse|Generator
     */
    public function handle(array $arguments): ToolResponse|Generator
    {
        $numberOfLines = (int) $arguments['number_of_lines'];
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath) || ! is_readable($logPath)) {
            return new ToolResponse('Log file not found or is not readable.');
        }

        $handle = fopen($logPath, 'rb');

        if (! $handle) {
            return new ToolResponse('Unable to open log file.');
        }

        try {
            $output = $this->tail($handle, $numberOfLines);
        } finally {
            fclose($handle);
        }

        return new ToolResponse($output);
    }

    /**
     * Efficiently read the last N lines from a file handle.
     *
     * @param  resource  $handle
     * @param  int  $lines
     * @return string
     */
    protected function tail($handle, int $lines): string
    {
        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);
        $output = '';
        $lineCount = 0;
        $bufferSize = 4096;

        while ($position > 0 && $lineCount <= $lines) {
            $seek = min($position, $bufferSize);
            fseek($handle, $position - $seek, SEEK_SET);
            $chunk = fread($handle, $seek);
            $output = $chunk.$output;
            $position -= $seek;
            $lineCount = substr_count($output, "\n");
        }

        return implode("\n", array_slice(explode("\n", $output), -$lines));
    }
}

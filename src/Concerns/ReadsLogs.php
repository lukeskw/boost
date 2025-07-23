<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Support\Facades\Config;

trait ReadsLogs
{
    /**
     * Regular expression fragments and default chunk-window sizes used when
     * scanning log files. Declaring them once keeps every consumer in sync.
     */
    private const TIMESTAMP_REGEX = '\\[\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\]';

    private const ENTRY_SPLIT_REGEX = '/(?='.self::TIMESTAMP_REGEX.')/';

    private const ERROR_ENTRY_REGEX = '/^'.self::TIMESTAMP_REGEX.'.*\\.ERROR:/';

    private const CHUNK_SIZE_START = 64 * 1024;       // 64 kB

    private const CHUNK_SIZE_MAX = 1 * 1024 * 1024; // 1 MB

    /**
     * Resolve the current log file path based on Laravel's logging configuration.
     */
    protected function resolveLogFilePath(): string
    {
        $channel = Config::get('logging.default');
        $channelConfig = Config::get("logging.channels.{$channel}");

        if (($channelConfig['driver'] ?? null) === 'daily') {
            return storage_path('logs/laravel-'.date('Y-m-d').'.log');
        }

        return storage_path('logs/laravel.log');
    }

    /**
     * Determine if the given line (or entry) is an ERROR log entry.
     */
    protected function isErrorEntry(string $line): bool
    {
        return preg_match(self::ERROR_ENTRY_REGEX, $line) === 1;
    }

    /**
     * Retrieve the last $count complete PSR-3 log entries from the log file using
     * chunked reading instead of character-by-character reverse scanning.
     *
     * @return string[]
     */
    protected function readLastLogEntries(string $logFile, int $count): array
    {
        $chunkSize = self::CHUNK_SIZE_START;

        do {
            $entries = $this->scanLogChunkForEntries($logFile, $chunkSize);

            if (count($entries) >= $count || $chunkSize >= self::CHUNK_SIZE_MAX) {
                break;
            }

            $chunkSize *= 2;
        } while (true);

        return array_slice($entries, -$count);
    }

    /**
     * Return the most recent ERROR log entry, or null if none exists within the
     * inspected window.
     */
    protected function readLastErrorEntry(string $logFile): ?string
    {
        $chunkSize = self::CHUNK_SIZE_START;

        do {
            $entries = $this->scanLogChunkForEntries($logFile, $chunkSize);

            for ($i = count($entries) - 1; $i >= 0; $i--) {
                if ($this->isErrorEntry($entries[$i])) {
                    return trim($entries[$i]);
                }
            }

            if ($chunkSize >= self::CHUNK_SIZE_MAX) {
                return null;
            }

            $chunkSize *= 2;
        } while (true);
    }

    /**
     * Scan the last $chunkSize bytes of the log file and return an array of
     * complete log entries (oldest ➜ newest).
     *
     * @return string[]
     */
    private function scanLogChunkForEntries(string $logFile, int $chunkSize): array
    {
        $fileSize = filesize($logFile);
        if ($fileSize === false) {
            return [];
        }

        $handle = fopen($logFile, 'r');
        if (! $handle) {
            return [];
        }

        try {
            $offset = max($fileSize - $chunkSize, 0);
            fseek($handle, $offset);

            // If we started mid-line, discard the partial line to align to next newline.
            if ($offset > 0) {
                fgets($handle);
            }

            $content = stream_get_contents($handle);
            if ($content === false) {
                return [];
            }

            // Split by beginning-of-entry look-ahead (PSR-3 timestamp pattern).
            $entries = preg_split(self::ENTRY_SPLIT_REGEX, $content, -1, PREG_SPLIT_NO_EMPTY);
            if (! $entries) {
                return [];
            }

            return $entries; // already in chronological order relative to chunk
        } finally {
            fclose($handle);
        }
    }
}

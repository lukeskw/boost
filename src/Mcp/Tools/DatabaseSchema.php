<?php

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Symfony\Component\Console\Output\BufferedOutput;

#[IsReadOnly()]
class DatabaseSchema extends Tool
{
    public function description(): string
    {
        return 'Read the database schema for this application, including table names, columns, data types, indexes, foreign keys, and more.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->string('database')
            ->description('Name of the database connection to dump (defaults to app\'s default connection, often not needed)')
            ->required(false);

        return $schema;
    }

    /**
     * @param  array<string>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $connection = $arguments['database'] ?? config('database.default');
        $cacheKey = "boost:mcp:database-schema:{$connection}";

        // We can't cache for long in case the user rolls back, edits a migration
        // then migrates, and gets the schema again
        $schema = Cache::remember($cacheKey, 20, function () use ($arguments) {
            $filename = 'tmp_'.Str::random(40).'.sql';
            $path = database_path("schema/{$filename}");

            $artisanArgs = ['--path' => $path];

            // Respect optional connection name
            if (! empty($arguments['database'])) {
                $artisanArgs['--database'] = $arguments['database'];
            }

            $output = new BufferedOutput;
            $result = Artisan::call('schema:dump', $artisanArgs, $output);
            if ($result !== Command::SUCCESS) {
                return ToolResult::error('Failed to dump database schema: '.$output->fetch());
            }

            $schemaContent = file_get_contents($path);

            // Clean up temp file
            unlink($path);

            return $schemaContent;
        });

        return ToolResult::text($schema);
    }
}

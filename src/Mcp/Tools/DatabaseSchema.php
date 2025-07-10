<?php

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly()]
class DatabaseSchema extends Tool
{
    public function description(): string
    {
        return 'Use this tool to read the database schema for the application.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    public function handle(array $arguments): ToolResult
    {
        $filename = 'tmp_'.Str::random(40).'.sql';
        $path = database_path("schema/{$filename}");

        Artisan::call('schema:dump', ['--path' => $path]);

        $schema = file_get_contents($path);

        unlink($path);

        return ToolResult::text($schema);
    }
}

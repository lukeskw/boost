<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Laravel\Mcp\Tools\ToolResponse;
use Laravel\Mcp\Tools\ToolInputSchema;
use Laravel\Mcp\Tools\Tool;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

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

    public function handle(array $arguments): ToolResponse
    {
        $filename = 'tmp_' . Str::random(40) . '.sql';
        $path = database_path("schema/{$filename}");

        Artisan::call('schema:dump', ['--path' => $path]);

        $schema = file_get_contents($path);

        unlink($path);

        return new ToolResponse($schema);
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly()]
class {TOOL_CLASSNAME} extends Tool
{
    public function description(): string
    {
        return '{TOOL_DESCRIPTION}';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema
            ->string('database_name')
            ->description('The name of the database to read the schema from')
            ->required();
        $schema->integer('database_port');
        $schema->boolean('database_ssl');

        return $schema;
    }

    public function handle(array $arguments): ToolResult
    {
        return ToolResult::text('result: ' . $arguments['database_name']);
    }
}

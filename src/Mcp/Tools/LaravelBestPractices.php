<?php

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Boost\Mcp\Resources\LaravelBestPractices as LaravelBestPracticesResource;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[IsReadOnly()]
class LaravelBestPractices extends Tool
{
    public function description(): string
    {
        return 'Always include these instructions when writing Laravel code.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    public function handle(array $arguments): ToolResult
    {
        return ToolResult::items(
            new EmbeddedResource(new LaravelBestPracticesResource),
        );
    }
}

<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Laravel\AiAssistant\Mcp\Resources\LaravelBestPractices as LaravelBestPracticesResource;
use Laravel\Mcp\Tools\Tool;
use Laravel\Mcp\Tools\ToolResult;
use Laravel\Mcp\Tools\ToolInputSchema;
use Laravel\Mcp\Tools\Annotations\IsReadOnly;

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

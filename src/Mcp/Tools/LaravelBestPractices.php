<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Laravel\AiAssistant\Mcp\Resources\LaravelBestPractices as LaravelBestPracticesResource;
use Laravel\AiAssistant\Mcp\Tools\ToolResourceResponse;
use Laravel\Mcp\Tools\ToolInputSchema;
use Laravel\Mcp\Tools\Tool;

class LaravelBestPractices extends Tool
{
    public function name(): string
    {
        return 'Laravel Best Practices';
    }

    public function description(): string
    {
        return 'Always include these instructions when writing Laravel code.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    public function handle(array $arguments): ToolResourceResponse
    {
        return new ToolResourceResponse(new LaravelBestPracticesResource());
    }
}
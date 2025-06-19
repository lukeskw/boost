<?php

namespace Laravel\AiAssistant\Mcp\Tools;

use Laravel\AiAssistant\Mcp\Resources\Resource;
use Laravel\Mcp\Tools\ToolResponse;

class ToolResourceResponse extends ToolResponse
{
    /**
     * Create a new tool resource response.
     */
    public function __construct(private Resource $resource) {}

    /**
     * Convert the response to an array.
     */
    public function toArray(): array
    {
        return [
            'contents' => [
                [
                    'type' => 'resource',
                    'resource' => [
                        'uri' => $this->resource->uri(),
                        'name' => $this->resource->name(),
                        'description' => $this->resource->description(),
                        'mimeType' => $this->resource->mimeType(),
                        'text' => $this->resource->read(),
                    ],
                ],
            ],
            'isError' => false,
        ];
    }
}

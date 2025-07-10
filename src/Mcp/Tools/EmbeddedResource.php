<?php

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Mcp\Server\Contracts\Tools\Content;
use Laravel\Mcp\Server\Resource;

class EmbeddedResource implements Content
{
    /**
     * Create a new text content item.
     */
    public function __construct(public readonly Resource $resource) {}

    /**
     * Convert the content to an array.
     */
    public function toArray(): array
    {
        return [
            'type' => 'resource',
            'resource' => [
                'uri' => $this->resource->uri(),
                'name' => $this->resource->name(),
                'description' => $this->resource->description(),
                'mimeType' => $this->resource->mimeType(),
                'text' => $this->resource->read(),
            ],
        ];
    }
}

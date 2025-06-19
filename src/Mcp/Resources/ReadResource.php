<?php

namespace Laravel\AiAssistant\Mcp\Resources;

use Laravel\Mcp\Contracts\Methods\Method;
use Laravel\Mcp\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;

class ReadResource implements Method
{
    public function __construct(
        protected array $resources,
    ) {}

    public function handle(JsonRpcRequest $request, ServerContext $context): JsonRpcResponse
    {
        $resource = collect($this->resources)->map(fn ($resource) => is_string($resource) ? new $resource : $resource)->firstOrFail(fn ($resource) => $resource->uri() === $request->params['uri']);

        return new JsonRpcResponse(
            $request->id,
            [
                'contents' => [[
                    'uri' => $resource->uri(),
                    'name' => $resource->name(),
                    'description' => $resource->description(),
                    'mimeType' => $resource->mimeType(),
                    'text' => $resource->read(),
                ]],
            ],
        );
    }
}

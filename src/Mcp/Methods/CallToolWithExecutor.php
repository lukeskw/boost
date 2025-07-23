<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Methods;

use Generator;
use Illuminate\Support\ItemNotFoundException;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Mcp\Server\Contracts\Methods\Method;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

class CallToolWithExecutor implements Method
{
    /**
     * Handle the JSON-RPC tool/call request with process isolation.
     *
     * @return JsonRpcResponse|Generator<JsonRpcNotification|JsonRpcResponse>
     */
    public function handle(JsonRpcRequest $request, ServerContext $context)
    {
        try {
            $tool = $context->tools()
                ->firstOrFail(fn ($tool) => $tool->name() === $request->params['name']);
        } catch (ItemNotFoundException $e) {
            return JsonRpcResponse::create(
                $request->id,
                ToolResult::error('Tool not found')
            );
        } catch (\Throwable $e) {
            return JsonRpcResponse::create(
                $request->id,
                ToolResult::error('Error finding tool: ' . $e->getMessage())
            );
        }

        try {
            // Use ToolExecutor instead of calling tool directly
            $executor = app(ToolExecutor::class);
            
            // Safely get arguments
            $arguments = [];
            if (isset($request->params['arguments']) && is_array($request->params['arguments'])) {
                $arguments = $request->params['arguments'];
            }
            
            $result = $executor->execute(get_class($tool), $arguments);

            return JsonRpcResponse::create($request->id, $result);
            
        } catch (\Throwable $e) {
            return JsonRpcResponse::create(
                $request->id,
                ToolResult::error('Tool execution error: ' . $e->getMessage())
            );
        }
    }
}
<?php

namespace Laravel\AiAssistant\Mcp;

use Laravel\AiAssistant\Mcp\Resources\ApplicationInfo;
use Laravel\AiAssistant\Mcp\Resources\LaravelBestPractices;
use Laravel\AiAssistant\Mcp\Resources\ListResources;
use Laravel\AiAssistant\Mcp\Resources\ReadResource;
use Laravel\AiAssistant\Mcp\Tools\LaravelBestPractices as LaravelBestPracticesTool;
use Laravel\AiAssistant\Mcp\Tools\LogReader;
use Laravel\AiAssistant\Mcp\Tools\DatabaseSchema;
use Laravel\Mcp\Server;

class AiAssistant extends Server
{
    /**
     * The server's display name.
     */
    public string $serverName = 'Laravel AI Assistant';

    /**
     * The server version.
     */
    public string $serverVersion = '0.0.1';

    /**
     * The instructions for the AI.
     */
    public string $instructions = 'An AI Assistant for Laravel to level up your AI agents.';

    /**
     * The available tools.
     */
    public array $tools = [
        LaravelBestPracticesTool::class,
        LogReader::class,
        DatabaseSchema::class,
    ];

    /**
     * The available resources.
     */
    public array $resources = [
        LaravelBestPractices::class,
        ApplicationInfo::class,
    ];

    public function boot()
    {
        $this->addMethod('resources/list', ListResources::class);
        app()->bind(ListResources::class, fn () => new ListResources($this->resources));

        $this->addMethod('resources/read', ReadResource::class);
        app()->bind(ReadResource::class, fn () => new ReadResource($this->resources));

        $this->addCapability('resources');
    }
}

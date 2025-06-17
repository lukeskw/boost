<?php

namespace Laravel\AiAssistant\Mcp;

use Laravel\AiAssistant\Mcp\Tools\LogReader;
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
        LogReader::class,
    ];
}

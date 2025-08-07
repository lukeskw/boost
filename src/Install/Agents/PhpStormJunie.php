<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

class PhpStormJunie extends FileMcpIde
{
    public function mcpPath(): string
    {
        return '.junie/mcp/mcp.json';
    }
}

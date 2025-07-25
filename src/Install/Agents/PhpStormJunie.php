<?php

namespace Laravel\Boost\Install\Agents;

class PhpStormJunie extends FileMcpIde
{
    public function mcpPath(): string
    {
        return '.junie/mcp/mcp.json';
    }
}

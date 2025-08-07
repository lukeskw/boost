<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

class VsCode extends FileMcpIde
{
    protected string $jsonMcpKey = 'servers';

    public function mcpPath(): string
    {
        return '.vscode/mcp.json';
    }
}

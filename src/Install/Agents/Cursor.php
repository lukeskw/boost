<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Cursor extends FileMcpIde implements Agent
{
    public function guidelinesPath(): string
    {
        return '.cursor/rules/laravel-boost.mdc';
    }

    public function mcpPath(): string
    {
        return '.cursor/mcp.json';
    }

    public function frontmatter(): bool
    {
        return true;
    }
}

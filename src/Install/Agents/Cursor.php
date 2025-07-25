<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\Ide;

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

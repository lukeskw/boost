<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Install\Agents\ShellMcpIde;

class ClaudeCode extends ShellMcpIde implements Agent
{
    protected string $shellCommand = 'claude mcp add laravel-boost {command} {args}';

    public function guidelinesPath(): string
    {
        return 'CLAUDE.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class ClaudeCode extends ShellMcpIde implements Agent
{
    protected string $shellCommand = 'claude mcp add {key} {command} {args}';

    public function guidelinesPath(): string
    {
        return 'CLAUDE.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

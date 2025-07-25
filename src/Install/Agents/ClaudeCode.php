<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\Ide;

class ClaudeCode implements Agent, Ide
{
    public function path(): string
    {
        return 'CLAUDE.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }

    public function installMcp(string $command, array $args): bool
    {
        return true;
    }
}

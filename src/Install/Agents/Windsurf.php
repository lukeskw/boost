<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Windsurf implements Agent
{
    public function guidelinesPath(): string
    {
        return '.windsurfrules.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

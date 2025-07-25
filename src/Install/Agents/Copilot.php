<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Copilot implements Agent
{
    public function path(): string
    {
        // VS Code supports multiple files in .github/instructions/
        // But, other IDEs don't
        return '.github/copilot-instructions.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

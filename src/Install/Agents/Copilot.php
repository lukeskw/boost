<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Copilot implements Agent
{
    public function guidelinesPath(): string
    {
        // VS Code supports multiple files in .github/instructions/
        // But, other IDEs don't
        return '.github/copilot-instructions.md';
    }

    public function frontmatter(): bool
    {
        // If we use the multi file approach we can use frontmatter
        // In VSCode at least https://docs.github.com/en/copilot/how-tos/configure-custom-instructions/add-repository-instructions?tool=vscode

        return false;
    }
}

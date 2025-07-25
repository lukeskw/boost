<?php

namespace Laravel\Boost\Contracts;

// We give Agents AI Rules
interface Agent
{
    public function guidelinesPath(): string;

    public function frontmatter(): bool;
}

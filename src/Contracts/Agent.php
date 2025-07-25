<?php

namespace Laravel\Boost\Contracts;

// We give Agents AI Rules
interface Agent
{
    public function path(): string;

    public function frontmatter(): bool;
}

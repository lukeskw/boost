<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Junie implements Agent
{
    public function path(): string
    {
        return '.junie/guidelines.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

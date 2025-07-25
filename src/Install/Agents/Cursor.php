<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Agent;

class Cursor implements Agent
{
    public function path(): string
    {
        return '.cursor/rules/laravel-boost.mdc';
    }

    public function frontmatter(): bool
    {
        return true;
    }
}

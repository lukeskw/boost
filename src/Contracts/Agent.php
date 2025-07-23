<?php

namespace Laravel\Boost\Contracts;

// We give Agents AI Rules
interface Agent
{
    // Things to note: supports multi-rule files?
    /**
     * Install the rules into the agent's rules if needed.
     * Should be safe to re-run without causing problems.
     * Should work well with others.
     */
    public function rules(string $rules): bool;
}

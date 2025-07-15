<?php

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:install', 'Install Laravel Boost MCP server')]
class InstallCommand extends Command
{
    public function handle(): void
    {
        // Detect IDE somehow
        // If we can't, ask
        // Put the appropriate mcp.json file in the appropriate place
        // If it already exists, we need to add ourselves (if we aren't already there)
        $this->info('Laravel Boost installed successfully.');
    }
}

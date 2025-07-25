<?php

namespace Laravel\Boost\Contracts;

// We install the MCP server into Ides
interface Ide
{
    // Things to note: supports relative (absolute path required)? global mcp only? Prefer local file, but if global only we have to add the project name to the server name

    /**
     * Install MCP server to this IDE.
     * Should be safe to re-run.
     * Should work well with others.
     */
    public function installMcp(string $command, array $args): bool;
}

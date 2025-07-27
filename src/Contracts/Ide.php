<?php

namespace Laravel\Boost\Contracts;

// We install the MCP server into Ides
interface Ide
{
    // Things to note: supports relative (absolute path required)? global mcp only? Prefer local file, but if global only we have to add the project name to the server name

    /**
     * @param array<int, string> $args
     * @param array<string, string> $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool;
}

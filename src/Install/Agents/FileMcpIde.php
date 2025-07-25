<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Ide;

abstract class FileMcpIde implements Ide
{
    protected string $jsonMcpKey = 'mcpServers';

    public function mcpPath(): string
    {
        throw new \Exception('Override me');
    }
}

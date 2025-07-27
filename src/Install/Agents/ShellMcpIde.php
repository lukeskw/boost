<?php

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\Ide;

abstract class ShellMcpIde implements Ide
{
    protected string $shellCommand = 'echo "{command} {args} {env}"';

    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        // -e, --env <env...>           Set environment variables (e.g. -e KEY=value)
        $envString = '';
        foreach ($env as $envKey => $value) {
            $envKey = strtoupper($envKey);
            $envString .= "-e {$envKey}=\"{$value}\" ";
        }

        $command = str_replace([
            '{key}',
            '{command}',
            '{args}',
            '{env}',
        ], [
            $key,
            $command,
            implode(' ', array_map(fn ($arg) => '"'.$arg.'"', $args)),
            trim($envString),
        ], $this->shellCommand);

        $result = Process::run($command);

        return $result->successful() || $result->seeInErrorOutput('already exists');
    }
}

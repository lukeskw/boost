<?php

namespace Laravel\Boost\Install\Agents;

use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\Ide;

abstract class ShellMcpIde implements Ide
{
    protected string $shellCommand = 'echo "{command} {args}"';

    public function installMcp(string $key, string $command, array $args = []): bool
    {
        $command = str_replace([
            '{key}',
            '{command}',
            '{args}',
        ], [
            $key, $command, implode(' ', $args),
        ], $this->shellCommand);

        $result = Process::run($command);

        return $result->successful() || $result->seeInErrorOutput('already exists');
    }
}

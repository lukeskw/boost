<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Ide;

abstract class ShellMcpIde implements Ide
{
    protected string $shellCommand = 'echo "{command} {args}"';
}

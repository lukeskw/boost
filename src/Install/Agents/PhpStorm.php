<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Ide;

class PhpStorm implements Ide
{
    public function installMcp(string $command, array $args): bool
    {
        return true;
    }
}

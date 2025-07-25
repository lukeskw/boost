<?php

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Ide;

class VsCode implements Ide
{

    public function installMcp(string $command, array $args): bool
    {
        return true;
    }
}

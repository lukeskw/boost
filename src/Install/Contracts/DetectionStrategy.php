<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Contracts;

use Laravel\Boost\Install\Enums\Platform;

interface DetectionStrategy
{
    /**
     * Detect if the application is installed on the machine.
     *
     * @param array $config
     * @param ?Platform $platform
     * @return bool
     */
    public function detect(array $config, ?Platform $platform = null): bool;
}

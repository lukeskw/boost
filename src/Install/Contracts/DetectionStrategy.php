<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Contracts;

interface DetectionStrategy
{
    /**
     * Detect if the application is installed on the machine.
     *
     * @param array $config
     * @param mixed $platform
     * @return void
     */
    public function detect(array $config, ?string $platform = null): bool;
}

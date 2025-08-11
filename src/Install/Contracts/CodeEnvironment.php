<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Contracts;

use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;

abstract class CodeEnvironment
{
    public function __construct(
        protected readonly DetectionStrategyFactory $strategyFactory
    ) {
    }

    /**
     * Get the internal identifier name for this code environment.
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * Get the human-readable display name for this code environment.
     *
     * @return string
     */
    abstract public function displayName(): string;

    /**
     * Get the detection configuration for system-wide installation detection.
     *
     * @param \Laravel\Boost\Install\Enums\Platform $platform
     * @return array
     */
    abstract public function systemDetectionConfig(Platform $platform): array;

    /**
     * Get the detection configuration for project-specific detection.
     *
     * @return array
     */
    abstract public function projectDetectionConfig(): array;

    /**
     * Determine if this code environment is installed on the system.
     *
     * @param \Laravel\Boost\Install\Enums\Platform $platform
     * @return bool
     */
    public function detectOnSystem(Platform $platform): bool
    {
        $config = $this->systemDetectionConfig($platform);
        $strategy = $this->strategyFactory->make($config['type']);

        return $strategy->detect($config, $platform->value);
    }

    /**
     * Determine if this code environment is being used in a specific project.
     *
     * @param string $basePath
     * @return bool
     */
    public function detectInProject(string $basePath): bool
    {
        $config = array_merge($this->projectDetectionConfig(), ['basePath' => $basePath]);
        $strategy = $this->strategyFactory->make($config['type']);

        return $strategy->detect($config);
    }
}

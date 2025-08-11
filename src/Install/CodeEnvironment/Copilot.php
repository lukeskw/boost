<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Install\Enums\DetectionType;
use Laravel\Boost\Install\Enums\Platform;

class Copilot extends CodeEnvironment
{
    public function name(): string
    {
        return 'copilot';
    }

    public function displayName(): string
    {
        return 'GitHub Copilot';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // Copilot doesn't have system-wide detection as it's an extension/feature
        return [
            'type' => DetectionType::File,
            'files' => [],
        ];
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['.github/copilot-instructions.md'],
            'type' => DetectionType::File,
        ];
    }

    public function detectOnSystem(Platform $platform): bool
    {
        return false;
    }

}

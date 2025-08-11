<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Install\Contracts\CodeEnvironment;
use Laravel\Boost\Install\Enums\DetectionType;
use Laravel\Boost\Install\Enums\Platform;

class Zed extends CodeEnvironment
{
    public function name(): string
    {
        return 'zed';
    }

    public function displayName(): string
    {
        return 'Zed';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Zed.app'],
                'type' => DetectionType::Directory,
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/zed',
                    '/usr/local/bin/zed',
                    '~/.local/bin/zed',
                ],
                'type' => DetectionType::Directory,
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Zed',
                    '%LOCALAPPDATA%\\Programs\\Zed',
                ],
                'type' => DetectionType::Directory,
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.zed'],
            'type' => DetectionType::Directory,
        ];
    }

}

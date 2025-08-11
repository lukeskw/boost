<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Install\Enums\Platform;

class Windsurf extends CodeEnvironment
{
    public function name(): string
    {
        return 'windsurf';
    }

    public function displayName(): string
    {
        return 'Windsurf';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Windsurf.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/windsurf',
                    '/usr/local/bin/windsurf',
                    '~/.local/bin/windsurf',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Windsurf',
                    '%ProgramFiles(x86)%\\Windsurf',
                    '%LOCALAPPDATA%\\Programs\\Windsurf',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'files' => ['.windsurfrules.md'],
        ];
    }

}

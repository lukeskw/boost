<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Install\Contracts\CodeEnvironment;
use Laravel\Boost\Install\Enums\DetectionType;
use Laravel\Boost\Install\Enums\Platform;

class VSCode extends CodeEnvironment
{
    public function name(): string
    {
        return 'vscode';
    }

    public function displayName(): string
    {
        return 'Visual Studio Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/Visual Studio Code.app'],
                'type' => DetectionType::Directory,
            ],
            Platform::Linux => [
                'command' => 'which code',
                'type' => DetectionType::Command,
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Microsoft VS Code',
                    '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
                ],
                'type' => DetectionType::Directory,
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.vscode'],
            'type' => DetectionType::Directory,
        ];
    }

}

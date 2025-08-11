<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Install\Enums\DetectionType;
use Laravel\Boost\Install\Enums\Platform;

class ClaudeCode extends CodeEnvironment
{
    public function name(): string
    {
        return 'claudecode';
    }

    public function displayName(): string
    {
        return 'Claude Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'which claude',
                'type' => DetectionType::Command,
            ],
            Platform::Windows => [
                'command' => 'where claude 2>null',
                'type' => DetectionType::Command,
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.claude'],
            'files' => ['CLAUDE.md'],
            'type' => [DetectionType::Directory, DetectionType::File],
        ];
    }
}

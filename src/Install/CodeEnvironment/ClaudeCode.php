<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

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
            ],
            Platform::Windows => [
                'command' => 'where claude 2>null',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.claude'],
            'files' => ['CLAUDE.md'],
        ];
    }
}

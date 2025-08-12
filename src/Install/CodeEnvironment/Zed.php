<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class Zed extends CodeEnvironment implements McpClient
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
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/zed',
                    '/usr/local/bin/zed',
                    '~/.local/bin/zed',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Zed',
                    '%LOCALAPPDATA%\\Programs\\Zed',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.zed'],
        ];
    }

    public function agentName(): ?string
    {
        return null;
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::File;
    }

    public function mcpConfigPath(): string
    {
        return '.zed/mcp.json';
    }
}

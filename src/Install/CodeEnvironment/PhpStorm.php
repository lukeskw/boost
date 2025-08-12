<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\Ide;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class PhpStorm extends CodeEnvironment implements Agent, Ide
{
    public function name(): string
    {
        return 'phpstorm';
    }

    public function displayName(): string
    {
        return 'PhpStorm';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin => [
                'paths' => ['/Applications/PhpStorm.app'],
            ],
            Platform::Linux => [
                'paths' => [
                    '/opt/phpstorm',
                    '/opt/PhpStorm*',
                    '/usr/local/bin/phpstorm',
                    '~/.local/share/JetBrains/Toolbox/apps/PhpStorm/ch-*',
                ],
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\JetBrains\\PhpStorm*',
                    '%LOCALAPPDATA%\\JetBrains\\Toolbox\\apps\\PhpStorm\\ch-*',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.idea', '.junie'],
        ];
    }

    public function agentName(): string
    {
        return 'junie';
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::File;
    }

    public function mcpConfigPath(): string
    {
        return '.junie/mcp/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return '.junie/guidelines.md';
    }

    public function frontmatter(): bool
    {
        return false;
    }
}

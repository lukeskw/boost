<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Facades\Process;

class ApplicationDetector
{
    /**
     * Application detection configurations for each platform.
     *
     * @var array<string, array<string, array<string, string|array<string>>>>
     */
    protected array $detectionConfig = [
        'darwin' => [
            'phpstorm' => [
                'paths' => ['/Applications/PhpStorm.app'],
                'type' => 'directory',
            ],
            'cursor' => [
                'paths' => ['/Applications/Cursor.app'],
                'type' => 'directory',
            ],
            'zed' => [
                'paths' => ['/Applications/Zed.app'],
                'type' => 'directory',
            ],
            'vscode' => [
                'paths' => ['/Applications/Visual Studio Code.app'],
                'type' => 'directory',
            ],
            'windsurf' => [
                'paths' => ['/Applications/Windsurf.app'],
                'type' => 'directory',
            ],
            'claudecode' => [
                'command' => 'which claude',
                'type' => 'command',
            ],
        ],
        'linux' => [
            'phpstorm' => [
                'paths' => [
                    '/opt/phpstorm',
                    '/opt/PhpStorm*',
                    '/usr/local/bin/phpstorm',
                    '~/.local/share/JetBrains/Toolbox/apps/PhpStorm/ch-*',
                ],
                'type' => 'directory',
            ],
            'vscode' => [
                'command' => 'which code',
                'type' => 'command',
            ],
            'cursor' => [
                'paths' => [
                    '/opt/cursor',
                    '/usr/local/bin/cursor',
                    '~/.local/bin/cursor',
                ],
                'type' => 'directory',
            ],
            'windsurf' => [
                'paths' => [
                    '/opt/windsurf',
                    '/usr/local/bin/windsurf',
                    '~/.local/bin/windsurf',
                ],
                'type' => 'directory',
            ],
            'claudecode' => [
                'command' => 'which claude',
                'type' => 'command',
            ],
        ],
        'windows' => [
            'phpstorm' => [
                'paths' => [
                    '%ProgramFiles%\\JetBrains\\PhpStorm*',
                    '%LOCALAPPDATA%\\JetBrains\\Toolbox\\apps\\PhpStorm\\ch-*',
                ],
                'type' => 'directory',
            ],
            'vscode' => [
                'paths' => [
                    '%ProgramFiles%\\Microsoft VS Code',
                    '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
                ],
                'type' => 'directory',
            ],
            'cursor' => [
                'paths' => [
                    '%ProgramFiles%\\Cursor',
                    '%LOCALAPPDATA%\\Programs\\Cursor',
                ],
                'type' => 'directory',
            ],
            'windsurf' => [
                'paths' => [
                    '%ProgramFiles%\\Windsurf',
                    '%ProgramFiles(x86)%\\Windsurf',
                    '%LOCALAPPDATA%\\Programs\\Windsurf',
                ],
                'type' => 'directory',
            ],
            'claudecode' => [
                'command' => 'where claude 2>nul',
                'type' => 'command',
            ],
        ],
    ];

    /**
     * Project-specific detection patterns.
     *
     * @var array<string, array<string, string|array<string>>>
     */
    protected array $projectDetectionConfig = [
        'phpstorm' => [
            'paths' => ['.idea', '.junie'],
            'type' => 'directory',
        ],
        'vscode' => [
            'paths' => ['.vscode'],
            'type' => 'directory',
        ],
        'cursor' => [
            'paths' => ['.cursor'],
            'type' => 'directory',
        ],
        'claudecode' => [
            'paths' => ['.claude'],
            'files' => ['CLAUDE.md'],
            'type' => 'mixed',
        ],
        'windsurf' => [
            'files' => ['.windsurfrules.md'],
            'type' => 'file',
        ],
        'copilot' => [
            'files' => ['.github/copilot-instructions.md'],
            'type' => 'file',
        ],
    ];

    /**
     * Detect installed applications on the current platform.
     *
     * @return array<string>
     */
    public function detectInstalled(): array
    {
        $platform = $this->getPlatform();
        $detected = [];

        if (! isset($this->detectionConfig[$platform])) {
            return [];
        }

        foreach ($this->detectionConfig[$platform] as $app => $config) {
            if ($this->isAppInstalled($config, $platform)) {
                $detected[] = $app;
            }
        }

        return array_unique($detected);
    }

    /**
     * Detect applications used in the current project.
     *
     * @return array<string>
     */
    public function detectInProject(string $basePath): array
    {
        $detected = [];

        foreach ($this->projectDetectionConfig as $app => $config) {
            if ($this->isAppUsedInProject($config, $basePath)) {
                $detected[] = $app;
            }
        }

        return array_unique($detected);
    }

    /**
     * Check if an application is installed based on its configuration.
     *
     * @param  array<string, string|array<string>>  $config
     */
    protected function isAppInstalled(array $config, string $platform): bool
    {
        if ($config['type'] === 'command') {
            return Process::run($config['command'])->successful();
        }

        if ($config['type'] === 'directory' && isset($config['paths'])) {
            foreach ($config['paths'] as $path) {
                $expandedPath = $this->expandPath($path, $platform);

                // Handle wildcards
                if (strpos($expandedPath, '*') !== false) {
                    $matches = glob($expandedPath, GLOB_ONLYDIR);
                    if (! empty($matches)) {
                        return true;
                    }
                } elseif (is_dir($expandedPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if an application is used in the current project.
     *
     * @param  array<string, string|array<string>>  $config
     */
    protected function isAppUsedInProject(array $config, string $basePath): bool
    {
        if ($config['type'] === 'directory' && isset($config['paths'])) {
            foreach ($config['paths'] as $path) {
                if (is_dir($basePath.DIRECTORY_SEPARATOR.$path)) {
                    return true;
                }
            }
        }

        if ($config['type'] === 'file' && isset($config['files'])) {
            foreach ($config['files'] as $file) {
                if (file_exists($basePath.DIRECTORY_SEPARATOR.$file)) {
                    return true;
                }
            }
        }

        if ($config['type'] === 'mixed') {
            if (isset($config['paths'])) {
                foreach ($config['paths'] as $path) {
                    if (is_dir($basePath.DIRECTORY_SEPARATOR.$path)) {
                        return true;
                    }
                }
            }
            if (isset($config['files'])) {
                foreach ($config['files'] as $file) {
                    if (file_exists($basePath.DIRECTORY_SEPARATOR.$file)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Expand environment variables and user home directory in paths.
     */
    protected function expandPath(string $path, string $platform): string
    {
        if ($platform === 'windows') {
            // Expand Windows environment variables
            $path = preg_replace_callback('/%([^%]+)%/', function ($matches) {
                return getenv($matches[1]) ?: $matches[0];
            }, $path);
        } else {
            // Expand Unix home directory
            if (strpos($path, '~') === 0) {
                $home = getenv('HOME');
                if ($home) {
                    $path = str_replace('~', $home, $path);
                }
            }
        }

        return $path;
    }

    /**
     * Get the current platform identifier.
     */
    protected function getPlatform(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'windows',
            'Darwin' => 'darwin',
            default => 'linux',
        };
    }

    /**
     * Add custom detection configuration for an application.
     *
     * @param  array<string, array<string, string|array<string>>>  $config
     */
    public function addDetectionConfig(string $app, array $config, ?string $platform = null): void
    {
        if ($platform === null) {
            // Add to all platforms
            foreach (['darwin', 'linux', 'windows'] as $p) {
                $this->detectionConfig[$p][$app] = $config[$p] ?? $config;
            }
        } else {
            $this->detectionConfig[$platform][$app] = $config;
        }
    }

    /**
     * Add custom project detection configuration for an application.
     *
     * @param  array<string, string|array<string>>  $config
     */
    public function addProjectDetectionConfig(string $app, array $config): void
    {
        $this->projectDetectionConfig[$app] = $config;
    }
}

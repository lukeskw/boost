<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\Ide;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
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
     * @param Platform $platform
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
     * @param Platform $platform
     * @return bool
     */
    public function detectOnSystem(Platform $platform): bool
    {
        $config = $this->systemDetectionConfig($platform);
        $strategy = $this->strategyFactory->makeFromConfig($config);

        return $strategy->detect($config, $platform);
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
        $strategy = $this->strategyFactory->makeFromConfig($config);

        return $strategy->detect($config);
    }

    /**
     * Override this method if the agent name is different from the environment name.
     *
     * @return ?string
     */
    public function agentName(): ?string
    {
        return $this->name();
    }

    /**
     * Override this method if the IDE name is different from the environment name.
     *
     * @return ?string
     */
    public function ideName(): ?string
    {
        return $this->name();
    }

    /**
     * Determine if this environment supports Agent/Guidelines functionality.
     *
     * @return bool
     */
    public function supportsAgent(): bool
    {
        return $this->agentName() !== null && $this instanceof Agent;
    }

    /**
     * Determine if this environment supports IDE/MCP functionality.
     *
     * @return bool
     */
    public function supportsIde(): bool
    {
        return $this->ideName() !== null && $this instanceof Ide;
    }

    /**
     * Get the MCP installation strategy for this environment.
     *
     * @return McpInstallationStrategy
     */
    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::None;
    }

    /**
     * Get the shell command for MCP installation (shell strategy).
     *
     * @return ?string
     */
    public function shellMcpCommand(): ?string
    {
        return null;
    }

    /**
     * Get the path to an MCP configuration file (file strategy).
     *
     * @return ?string
     */
    public function mcpConfigPath(): ?string
    {
        return null;
    }

    /**
     * Get the JSON key for MCP servers in the config file (file strategy).
     *
     * @return string
     */
    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    /**
     * Install MCP server using the appropriate strategy.
     *
     * @param string $key
     * @param string $command
     * @param array<int, string> $args
     * @param array<string, string> $env
     * @return bool
     *
     * @throws FileNotFoundException
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        return match($this->mcpInstallationStrategy()) {
            McpInstallationStrategy::Shell => $this->installShellMcp($key, $command, $args, $env),
            McpInstallationStrategy::File => $this->installFileMcp($key, $command, $args, $env),
            McpInstallationStrategy::None => false,
        };
    }

    /**
     * Install MCP server using a shell command strategy.
     *
     * @param string $key
     * @param string $command
     * @param array<int, string> $args
     * @param array<string, string> $env
     * @return bool
     */
    protected function installShellMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $shellCommand = $this->shellMcpCommand();
        if ($shellCommand === null) {
            return false;
        }

        // Build environment string
        $envString = '';
        foreach ($env as $envKey => $value) {
            $envKey = strtoupper($envKey);
            $envString .= "-e {$envKey}=\"{$value}\" ";
        }

        // Replace placeholders in shell command
        $command = str_replace([
            '{key}',
            '{command}',
            '{args}',
            '{env}',
        ], [
            $key,
            $command,
            implode(' ', array_map(fn ($arg) => '"'.$arg.'"', $args)),
            trim($envString),
        ], $shellCommand);

        $result = Process::run($command);

        return $result->successful() || str_contains($result->errorOutput(), 'already exists');
    }

    /**
     * Install MCP server using a file-based configuration strategy.
     *
     * @param string $key
     * @param string $command
     * @param array<int, string> $args
     * @param array<string, string> $env
     * @return bool
     *
     * @throws FileNotFoundException
     */
    protected function installFileMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $path = $this->mcpConfigPath();
        if (! $path) {
            return false;
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        // Load existing configuration
        $config = File::exists($path)
            ? json_decode(File::get($path), true) ?: []
            : [];

        $mcpKey = $this->mcpConfigKey();
        data_set($config, "{$mcpKey}.{$key}", collect([
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ])->filter()->toArray());

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $json && File::put($path, $json);
    }
}

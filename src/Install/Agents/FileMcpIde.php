<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\Ide;

abstract class FileMcpIde implements Ide
{
    protected string $jsonMcpKey = 'mcpServers';

    public function mcpPath(): string
    {
        throw new \Exception('Override me');
    }

    /**
     * @param array<int, string> $args
     * @param array<string, string> $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $path = $this->mcpPath();

        // Ensure directory exists
        $directory = dirname($path);
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true)) {
                return false;
            }
        }

        // Read existing configuration or create new one
        $config = [];
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $config = $decoded;
                }
            }
        }

        // Ensure mcpServers key exists
        if (! isset($config[$this->jsonMcpKey])) {
            $config[$this->jsonMcpKey] = [];
        }

        // Add or update laravel-boost server configuration
        $config[$this->jsonMcpKey][$key] = array_filter([
            'command' => $command,
            'args' => $args,
            'env' => $env,
        ]);

        // Write configuration back to file
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $result = file_put_contents($path, $json);

        return $result !== false;
    }
}

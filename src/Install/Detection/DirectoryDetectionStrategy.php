<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Detection;

use Laravel\Boost\Install\Contracts\DetectionStrategy;

class DirectoryDetectionStrategy implements DetectionStrategy
{
    public function detect(array $config, ?string $platform = null): bool
    {
        if (! isset($config['paths'])) {
            return false;
        }

        $basePath = $config['basePath'] ?? '';

        foreach ($config['paths'] as $path) {
            $expandedPath = $this->expandPath($path, $platform);

            // If basePath is provided, prepend it to relative paths
            if ($basePath && ! $this->isAbsolutePath($expandedPath)) {
                $expandedPath = $basePath.DIRECTORY_SEPARATOR.$expandedPath;
            }

            if (str_contains($expandedPath, '*')) {
                $matches = glob($expandedPath, GLOB_ONLYDIR);
                if (! empty($matches)) {
                    return true;
                }
            } elseif (is_dir($expandedPath)) {
                return true;
            }
        }

        return false;
    }

    private function expandPath(string $path, ?string $platform = null): string
    {
        if ($platform === 'windows') {
            return preg_replace_callback('/%([^%]+)%/', function ($matches) {
                return getenv($matches[1]) ?: $matches[0];
            }, $path);
        }

        if (str_starts_with($path, '~')) {
            $home = getenv('HOME');
            if ($home) {
                return str_replace('~', $home, $path);
            }
        }

        return $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') ||
               str_starts_with($path, '\\') ||
               (strlen($path) > 1 && $path[1] === ':'); // Windows C:
    }
}

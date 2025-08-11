<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Detection;

use Laravel\Boost\Install\Contracts\DetectionStrategy;

class FileDetectionStrategy implements DetectionStrategy
{
    public function detect(array $config, ?string $platform = null): bool
    {
        $basePath = $config['basePath'] ?? getcwd();

        if (isset($config['files'])) {
            foreach ($config['files'] as $file) {
                if (file_exists($basePath.DIRECTORY_SEPARATOR.$file)) {
                    return true;
                }
            }
        }

        return false;
    }
}

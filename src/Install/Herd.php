<?php

namespace Laravel\Boost\Install;

class Herd
{
    public function isInstalled(): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if (! $isWindows) {
            return file_exists('/Applications/Herd.app/Contents/MacOS/Herd');
        }

        return is_dir($this->getHomePath().'/.config/herd');
    }

    public function isMcpAvailable(): bool
    {
        return file_exists($this->mcpPath());
    }

    public function getHomePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if (! isset($_SERVER['HOME'])) {
                $_SERVER['HOME'] = $_SERVER['USERPROFILE'];
            }

            $_SERVER['HOME'] = str_replace('\\', '/', $_SERVER['HOME']);
        }

        return $_SERVER['HOME'];
    }

    public function mcpPath(): string
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            return $this->getHomePath().'/.config/herd/bin/herd-mcp.phar';
        }

        return $this->getHomePath().'/Library/Application Support/Herd/bin/herd-mcp.phar';
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Detection;

use Illuminate\Container\Container;
use InvalidArgumentException;
use Laravel\Boost\Install\Contracts\DetectionStrategy;

class DetectionStrategyFactory
{
    public function __construct(private readonly Container $container)
    {
    }

    public function make(string|array $type, array $config = []): DetectionStrategy
    {
        if (is_array($type)) {
            return new CompositeDetectionStrategy(
                array_map(fn ($singleType) => $this->make($singleType, $config), $type)
            );
        }

        return match ($type) {
            'directory' => $this->container->make(DirectoryDetectionStrategy::class),
            'command' => $this->container->make(CommandDetectionStrategy::class),
            'file' => $this->container->make(FileDetectionStrategy::class),
            default => throw new InvalidArgumentException("Unknown detection type: {$type}"),
        };
    }

    public function makeFromConfig(array $config): DetectionStrategy
    {
        $type = $this->inferTypeFromConfig($config);

        return $this->make($type, $config);
    }

    private function inferTypeFromConfig(array $config): string|array
    {
        $types = [];

        if (isset($config['files'])) {
            $types[] = 'file';
        }

        if (isset($config['paths'])) {
            $types[] = 'directory';
        }

        if (isset($config['command'])) {
            $types[] = 'command';
        }

        if (empty($types)) {
            throw new InvalidArgumentException('Cannot infer detection type from config keys. Expected one of: files, paths, command');
        }

        return count($types) === 1 ? $types[0] : $types;
    }
}

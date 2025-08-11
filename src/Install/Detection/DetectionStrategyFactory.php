<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Detection;

use Illuminate\Container\Container;
use InvalidArgumentException;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Enums\DetectionType;

class DetectionStrategyFactory
{
    public function __construct(private readonly Container $container)
    {
    }

    public function make(DetectionType|string|array $type): DetectionStrategy
    {
        if (is_array($type)) {
            return new CompositeDetectionStrategy(
                array_map(fn ($singleType) => $this->make($singleType), $type)
            );
        }

        $detectionType = $type instanceof DetectionType ? $type : DetectionType::from($type);

        return match ($detectionType) {
            DetectionType::Directory => $this->container->make(DirectoryDetectionStrategy::class),
            DetectionType::Command => $this->container->make(CommandDetectionStrategy::class),
            DetectionType::File => $this->container->make(FileDetectionStrategy::class),
            default => throw new InvalidArgumentException("Unknown detection type: {$detectionType->value}"),
        };
    }
}

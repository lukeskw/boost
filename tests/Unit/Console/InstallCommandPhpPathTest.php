<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('getPhpPathForMcpClient returns absolute PHP_BINARY for PhpStorm', function () {
    $installCommand = new InstallCommand;
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $phpStorm = new PhpStorm($strategyFactory);

    $phpPath = invokePrivateMethod($installCommand, 'getPhpPathForMcpClient', [$phpStorm]);

    expect($phpPath)->toBe(PHP_BINARY);
});

test('getPhpPathForMcpClient returns php string for Cursor', function () {
    $installCommand = new InstallCommand;
    $strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $cursor = new Cursor($strategyFactory);

    $phpPath = invokePrivateMethod($installCommand, 'getPhpPathForMcpClient', [$cursor]);

    expect($phpPath)->toBe('php');
});

function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}

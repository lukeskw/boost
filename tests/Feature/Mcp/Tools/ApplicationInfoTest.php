<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

test('it returns application info with packages', function () {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, '11.0.0'),
        new Package(Packages::PEST, '2.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $tool = new ApplicationInfo($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['isError'])->toBeFalse();

    $content = json_decode($data['content'][0]['text'], true);
    expect($content['php_version'])->toBe(PHP_VERSION);
    expect($content['laravel_version'])->toBe(app()->version());
    expect($content['database_engine'])->toBe(config('database.default'));
    expect($content['packages'])->toHaveCount(2);
    expect($content['packages'][0]['name'])->toBe('LARAVEL');
    expect($content['packages'][0]['version'])->toBe('11.0.0');
    expect($content['packages'][1]['name'])->toBe('PEST');
    expect($content['packages'][1]['version'])->toBe('2.0.0');
    expect($content['models'])->toBeArray();
});

test('it returns application info with no packages', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn(new PackageCollection([]));

    $tool = new ApplicationInfo($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    expect($result)->toBeInstanceOf(ToolResult::class);

    $data = $result->toArray();
    expect($data['isError'])->toBeFalse();

    $content = json_decode($data['content'][0]['text'], true);
    expect($content['php_version'])->toBe(PHP_VERSION);
    expect($content['laravel_version'])->toBe(app()->version());
    expect($content['database_engine'])->toBe(config('database.default'));
    expect($content['packages'])->toHaveCount(0);
    expect($content['models'])->toBeArray();
});

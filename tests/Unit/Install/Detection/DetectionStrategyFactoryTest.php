<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Laravel\Boost\Install\Detection\CommandDetectionStrategy;
use Laravel\Boost\Install\Detection\CompositeDetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Detection\DirectoryDetectionStrategy;
use Laravel\Boost\Install\Detection\FileDetectionStrategy;
use Laravel\Boost\Install\Enums\DetectionType;

beforeEach(function () {
    $this->container = new Container();
    $this->factory = new DetectionStrategyFactory($this->container);
});

test('creates directory strategy from enum', function () {
    $strategy = $this->factory->make(DetectionType::Directory);

    expect($strategy)->toBeInstanceOf(DirectoryDetectionStrategy::class);
});

test('creates directory strategy from string', function () {
    $strategy = $this->factory->make('directory');

    expect($strategy)->toBeInstanceOf(DirectoryDetectionStrategy::class);
});

test('creates file strategy from enum', function () {
    $strategy = $this->factory->make(DetectionType::File);

    expect($strategy)->toBeInstanceOf(FileDetectionStrategy::class);
});

test('creates file strategy from string', function () {
    $strategy = $this->factory->make('file');

    expect($strategy)->toBeInstanceOf(FileDetectionStrategy::class);
});

test('creates command strategy from enum', function () {
    $strategy = $this->factory->make(DetectionType::Command);

    expect($strategy)->toBeInstanceOf(CommandDetectionStrategy::class);
});

test('creates command strategy from string', function () {
    $strategy = $this->factory->make('command');

    expect($strategy)->toBeInstanceOf(CommandDetectionStrategy::class);
});

test('creates composite strategy from array of enums', function () {
    $strategy = $this->factory->make([
        DetectionType::Directory,
        DetectionType::File,
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('creates composite strategy from array of strings', function () {
    $strategy = $this->factory->make([
        'directory',
        'file',
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('creates composite strategy from mixed array', function () {
    $strategy = $this->factory->make([
        DetectionType::Directory,
        'file',
        DetectionType::Command,
    ]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

test('throws exception for unknown string type', function () {
    expect(fn () => $this->factory->make('unknown'))
        ->toThrow(\ValueError::class);
});

test('throws exception for invalid enum value', function () {
    expect(fn () => $this->factory->make('invalid_type'))
        ->toThrow(\ValueError::class);
});

test('empty array creates composite strategy', function () {
    $strategy = $this->factory->make([]);

    expect($strategy)->toBeInstanceOf(CompositeDetectionStrategy::class);
});

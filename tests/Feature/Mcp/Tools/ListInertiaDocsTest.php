<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Mcp\Tools\ListInertiaDocs;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

beforeEach(function () {
    Cache::flush();
});

test('it returns error when inertia is not installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn(null);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Inertia is not installed in this project.');
});

test('it fetches documentation list successfully', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $githubResponse = [
        [
            'type' => 'file',
            'name' => 'installation.jsx',
        ],
        [
            'type' => 'file',
            'name' => 'configuration.jsx',
        ],
        [
            'type' => 'dir',
            'name' => 'examples',
        ],
        [
            'type' => 'file',
            'name' => 'getting-started.jsx',
        ],
    ];

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages?ref=v2' => Http::response(json_encode($githubResponse)),
    ]);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);

    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toHaveCount(3);
    expect($content)->toContain('installation.jsx');
    expect($content)->toContain('configuration.jsx');
    expect($content)->toContain('getting-started.jsx');
    expect($content)->not->toContain('examples'); // Should filter out directories
});

test('it returns error when github api fails', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages?ref=v2' => Http::response(json_encode(['message' => 'Not found']), 404),
    ]);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Failed to fetch Inertia docs list');
});

test('it uses correct version ref based on major version', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '1.2.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $githubResponse = [
        [
            'type' => 'file',
            'name' => 'v1-installation.jsx',
        ],
    ];

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages?ref=v1' => Http::response(json_encode($githubResponse)),
    ]);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);

    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toHaveCount(1);
    expect($content)->toContain('v1-installation.jsx');
});

test('it filters out directories and only returns files', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $githubResponse = [
        [
            'type' => 'dir',
            'name' => 'subdirectory',
        ],
        [
            'type' => 'file',
            'name' => 'only-file.jsx',
        ],
        [
            'type' => 'dir',
            'name' => 'another-dir',
        ],
    ];

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages?ref=v2' => Http::response(json_encode($githubResponse)),
    ]);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);

    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toHaveCount(1);
    expect($content)->toContain('only-file.jsx');
    expect($content)->not->toContain('subdirectory');
    expect($content)->not->toContain('another-dir');
});

test('it returns empty array when no files found', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $githubResponse = [
        [
            'type' => 'dir',
            'name' => 'only-directories',
        ],
    ];

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages?ref=v2' => Http::response(json_encode($githubResponse)),
    ]);

    $tool = new ListInertiaDocs($roster);
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);

    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toHaveCount(0);
});

test('shouldRegister returns true when inertia is installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);

    $tool = new ListInertiaDocs($roster);

    expect($tool->shouldRegister())->toBe(true);
});

test('shouldRegister returns false when inertia is not installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(false);

    $tool = new ListInertiaDocs($roster);

    expect($tool->shouldRegister())->toBe(false);
});

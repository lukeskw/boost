<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Boost\Mcp\Tools\GetInertiaDoc;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

beforeEach(function () {
    Cache::flush();
});

test('it returns error when filename is missing', function () {
    $roster = Mockery::mock(Roster::class);
    $tool = new GetInertiaDoc($roster);

    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('The "filename" argument is required.');
});

test('it returns error when filename is empty', function () {
    $roster = Mockery::mock(Roster::class);
    $tool = new GetInertiaDoc($roster);

    $result = $tool->handle(['filename' => '']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('The "filename" argument is required.');
});

test('it returns error when filename does not end with jsx', function () {
    $roster = Mockery::mock(Roster::class);
    $tool = new GetInertiaDoc($roster);

    $result = $tool->handle(['filename' => 'installation.md']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('The "filename" argument must end with ".jsx".');
});

test('it returns error when filename has invalid characters', function () {
    $roster = Mockery::mock(Roster::class);
    $tool = new GetInertiaDoc($roster);

    $result = $tool->handle(['filename' => 'installation_INVALID.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('The "filename" argument must be a valid filename');
});

test('it returns error when inertia is not installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn(null);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Inertia is not installed in this project.');
});

test('it fetches documentation successfully', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $docContent = 'This is the installation documentation';
    $base64Content = base64_encode($docContent);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages/installation.jsx?ref=v2' => Http::response(json_encode([
            'type' => 'file',
            'content' => $base64Content,
        ])),
    ]);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);
    expect($data['content'][0]['text'])->toBe($docContent);
});

test('it returns error when github api fails', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages/installation.jsx?ref=v2' => Http::response(json_encode(['message' => 'Not found']), 404),
    ]);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Failed to fetch Inertia doc');
});

test('it returns error when response structure is unexpected', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages/installation.jsx?ref=v2' => Http::response(json_encode([
            'type' => 'dir', // Should be 'file'
        ])),
    ]);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Unexpected response structure');
});

test('it returns error when base64 decode fails', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '2.1.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages/installation.jsx?ref=v2' => Http::response(json_encode([
            'type' => 'file',
            'content' => 'invalid-base64!!!',
        ])),
    ]);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(true);
    expect($data['content'][0]['text'])->toContain('Failed to decode Inertia doc content');
});

test('it uses correct version ref based on major version', function () {
    $package = new Package(Packages::INERTIA_LARAVEL, '1.2.0');
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('package')->with(Packages::INERTIA_LARAVEL)->andReturn($package);

    $docContent = 'Version 1 documentation';
    $base64Content = base64_encode($docContent);

    Http::fake([
        'https://api.github.com/repos/inertiajs/inertiajs.com/contents/resources/js/Pages/installation.jsx?ref=v1' => Http::response(json_encode([
            'type' => 'file',
            'content' => $base64Content,
        ])),
    ]);

    $tool = new GetInertiaDoc($roster);
    $result = $tool->handle(['filename' => 'installation.jsx']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);
    expect($data['content'][0]['text'])->toBe($docContent);
});

test('shouldRegister returns true when inertia is installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(true);

    $tool = new GetInertiaDoc($roster);

    expect($tool->shouldRegister())->toBe(true);
});

test('shouldRegister returns false when inertia is not installed', function () {
    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('uses')->with(Packages::INERTIA_LARAVEL)->andReturn(false);

    $tool = new GetInertiaDoc($roster);

    expect($tool->shouldRegister())->toBe(false);
});

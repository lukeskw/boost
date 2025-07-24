<?php

declare(strict_types=1);

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Laravel\Boost\Mcp\Tools\SearchDocs;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Package;
use Laravel\Roster\PackageCollection;
use Laravel\Roster\Roster;

test('it searches documentation successfully', function () {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::PEST, 'pestphp/pest', '2.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn([
        'results' => [
            ['content' => 'Laravel documentation content'],
            ['content' => 'Pest documentation content'],
        ]
    ]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'authentication, testing']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    
    $data = $result->toArray();
    expect($data['isError'])->toBeFalse();
    
    $content = json_decode($data['content'][0]['text'], true);
    expect($content['knowledge_count'])->toBe(2);
    expect($content['knowledge'])->toContain('Laravel documentation content');
    expect($content['knowledge'])->toContain('Pest documentation content');
    expect($content['knowledge'])->toContain('---');
});

test('it handles API error response', function () {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(false);
    $mockResponse->shouldReceive('status')->andReturn(500);
    $mockResponse->shouldReceive('body')->andReturn('API Error');

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'authentication']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    
    $data = $result->toArray();
    expect($data['isError'])->toBeTrue();
    expect($data['content'][0]['text'])->toBe('Failed to search documentation: API Error');
});

test('it filters empty queries', function () {
    $packages = new PackageCollection([]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn(['results' => []]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->withArgs(function($url, $payload) {
        return $url === 'https://boost.laravel.com/api/docs' &&
               $payload['queries'] === ['test'] && 
               empty($payload['packages']) && 
               $payload['token_limit'] === 10000;
    })->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'test###  ###*### ']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    
    $data = $result->toArray();
    expect($data['isError'])->toBeFalse();
});

test('it formats package data correctly', function () {
    $packages = new PackageCollection([
        new Package(Packages::LARAVEL, 'laravel/framework', '11.0.0'),
        new Package(Packages::LIVEWIRE, 'livewire/livewire', '3.5.1'),
    ]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn(['results' => []]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->with(
        'https://boost.laravel.com/api/docs',
        Mockery::on(function ($payload) {
            return $payload['packages'] === [
                ['name' => 'laravel/framework', 'version' => '11.x'],
                ['name' => 'livewire/livewire', 'version' => '3.x']
            ] && $payload['token_limit'] === 10000;
        })
    )->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'test']);

    expect($result)->toBeInstanceOf(ToolResult::class);
});

test('it handles empty results', function () {
    $packages = new PackageCollection([]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn(['results' => []]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'nonexistent']);

    expect($result)->toBeInstanceOf(ToolResult::class);
    
    $data = $result->toArray();
    expect($data['isError'])->toBeFalse();
    
    $content = json_decode($data['content'][0]['text'], true);
    expect($content['knowledge_count'])->toBe(0);
    expect($content['knowledge'])->toBe('');
});

test('it uses custom token_limit when provided', function () {
    $packages = new PackageCollection([]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn(['results' => []]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->with(
        'https://boost.laravel.com/api/docs',
        Mockery::on(function ($payload) {
            return $payload['token_limit'] === 5000;
        })
    )->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'test', 'token_limit' => 5000]);

    expect($result)->toBeInstanceOf(ToolResult::class);
});

test('it caps token_limit at maximum of 1000000', function () {
    $packages = new PackageCollection([]);

    $roster = Mockery::mock(Roster::class);
    $roster->shouldReceive('packages')->andReturn($packages);

    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('successful')->andReturn(true);
    $mockResponse->shouldReceive('json')->andReturn(['results' => []]);

    $mockClient = Mockery::mock(PendingRequest::class);
    $mockClient->shouldReceive('asJson')->andReturnSelf();
    $mockClient->shouldReceive('post')->with(
        'https://boost.laravel.com/api/docs',
        Mockery::on(function ($payload) {
            return $payload['token_limit'] === 1000000; // Should be capped at 1M
        })
    )->andReturn($mockResponse);

    $tool = Mockery::mock(SearchDocs::class, [$roster])->makePartial();
    $tool->shouldReceive('client')->andReturn($mockClient);

    $result = $tool->handle(['queries' => 'test', 'token_limit' => 2000000]); // Request 2M but get capped at 1M

    expect($result)->toBeInstanceOf(ToolResult::class);
});
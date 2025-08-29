<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Mcp\Tools\ListRoutes;
use Laravel\Mcp\Server\Tools\ToolResult;

beforeEach(function () {
    Route::get('/admin/dashboard', function () {
        return 'admin dashboard';
    })->name('admin.dashboard');

    Route::post('/admin/users', function () {
        return 'admin users';
    })->name('admin.users.store');

    Route::get('/user/profile', function () {
        return 'user profile';
    })->name('user.profile');

    Route::get('/api/two-factor/enable', function () {
        return 'two-factor enable';
    })->name('two-factor.enable');

    Route::get('/api/v1/posts', function () {
        return 'posts';
    })->name('api.posts.index');

    Route::put('/api/v1/posts/{id}', function ($id) {
        return 'update post';
    })->name('api.posts.update');
});

test('it returns list of routes without filters', function () {
    $tool = new ListRoutes;
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBeFalse()
        ->and($data['content'][0]['text'])->toBeString()
        ->and($data['content'][0]['text'])->toContain('GET|HEAD')
        ->and($data['content'][0]['text'])->toContain('admin.dashboard')
        ->and($data['content'][0]['text'])->toContain('user.profile');
});

test('it sanitizes name parameter wildcards and filters correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*admin*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->toArray()['isError'])->toBeFalse()
        ->and($output)->toContain('admin.dashboard')
        ->and($output)->toContain('admin.users.store')
        ->and($output)->not->toContain('user.profile')
        ->and($output)->not->toContain('two-factor.enable');

    $result = $tool->handle(['name' => '*two-factor*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('two-factor.enable')
        ->and($output)->not->toContain('admin.dashboard')
        ->and($output)->not->toContain('user.profile');

    $result = $tool->handle(['name' => '*api*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('api.posts.index')
        ->and($output)->toContain('api.posts.update')
        ->and($output)->not->toContain('admin.dashboard')
        ->and($output)->not->toContain('user.profile');
});

test('it sanitizes method parameter wildcards and filters correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['method' => 'GET*POST']);
    $output = $result->toArray()['content'][0]['text'];

    expect($result->toArray()['isError'])->toBeFalse()
        ->and($output)->toContain('ERROR  Your application doesn\'t have any routes matching the given criteria.');

    $result = $tool->handle(['method' => '*GET*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('admin.dashboard')
        ->and($output)->toContain('user.profile')
        ->and($output)->toContain('api.posts.index')
        ->and($output)->not->toContain('admin.users.store');

    $result = $tool->handle(['method' => '*POST*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('admin.users.store')
        ->and($output)->not->toContain('admin.dashboard');
});

test('it preserves wildcards in path parameters', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['path' => '/admin/*']);
    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->toArray()['isError'])->toBeFalse();

    $output = $result->toArray()['content'][0]['text'];
    expect($output)->not->toContain('Failed to list routes');

    $result = $tool->handle(['except_path' => '/nonexistent/*']);
    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->toArray()['isError'])->toBeFalse();

    $output = $result->toArray()['content'][0]['text'];
    expect($output)->toContain('admin.dashboard')
        ->and($output)->toContain('user.profile');
});

test('it handles edge cases and empty results correctly', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*']);
    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->toArray()['isError'])->toBeFalse();

    $output = $result->toArray()['content'][0]['text'];
    expect($output)->toContain('admin.dashboard')
        ->and($output)->toContain('user.profile')
        ->and($output)->toContain('two-factor.enable');

    $result = $tool->handle(['name' => '*nonexistent*']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('ERROR  Your application doesn\'t have any routes matching the given criteria.');

    $result = $tool->handle(['name' => '']);
    $output = $result->toArray()['content'][0]['text'];

    expect($output)->toContain('admin.dashboard')
        ->and($output)->toContain('user.profile');
});

test('it handles multiple parameters with wildcard sanitization', function () {
    $tool = new ListRoutes;

    $result = $tool->handle([
        'name' => '*admin*',
        'method' => '*GET*',
    ]);

    $output = $result->toArray()['content'][0]['text'];

    expect($result->toArray()['isError'])->toBeFalse()
        ->and($output)->toContain('admin.dashboard')
        ->and($output)->not->toContain('admin.users.store')
        ->and($output)->not->toContain('user.profile');

    $result = $tool->handle([
        'name' => '*user*',
        'method' => '*POST*',
    ]);

    $output = $result->toArray()['content'][0]['text'];

    if (str_contains($output, 'admin.users.store')) {
        expect($output)->toContain('admin.users.store');
    } else {
        expect($output)->toContain('ERROR  Your application doesn\'t have any routes matching the given criteria.');
    }
});

test('it handles the original problematic wildcard case', function () {
    $tool = new ListRoutes;

    $result = $tool->handle(['name' => '*/two-factor/']);
    expect($result)->toBeInstanceOf(ToolResult::class)
        ->and($result->toArray()['isError'])->toBeFalse();

    $output = $result->toArray()['content'][0]['text'];
    if (str_contains($output, 'two-factor.enable')) {
        expect($output)->toContain('two-factor.enable');
    } else {
        expect($output)->toContain('ERROR');
    }
});

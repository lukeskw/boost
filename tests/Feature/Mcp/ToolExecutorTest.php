<?php

use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\Tools\ApplicationInfo;
use Laravel\Mcp\Server\Tools\ToolResult;

test('can execute tool inline', function () {
    // Disable process isolation for this test
    config(['boost.process_isolation.enabled' => false]);

    $executor = app(ToolExecutor::class);
    $result = $executor->execute(ApplicationInfo::class, []);

    expect($result)->toBeInstanceOf(ToolResult::class);
});

test('rejects unregistered tools', function () {
    $executor = app(ToolExecutor::class);
    $result = $executor->execute('NonExistentToolClass', []);

    expect($result)->toBeInstanceOf(ToolResult::class);
    expect($result->isError)->toBeTrue();
});

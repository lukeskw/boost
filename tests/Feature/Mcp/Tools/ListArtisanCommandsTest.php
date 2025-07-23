<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\ListArtisanCommands;
use Laravel\Mcp\Server\Tools\ToolResult;

test('it returns list of artisan commands', function () {
    $tool = new ListArtisanCommands;
    $result = $tool->handle([]);

    expect($result)->toBeInstanceOf(ToolResult::class);
    $data = $result->toArray();
    expect($data['isError'])->toBe(false);

    $content = json_decode($data['content'][0]['text'], true);
    expect($content)->toBeArray();
    expect($content)->not->toBeEmpty();

    // Check that it contains some basic Laravel commands
    $commandNames = array_column($content, 'name');
    expect($commandNames)->toContain('migrate');
    expect($commandNames)->toContain('make:model');
    expect($commandNames)->toContain('route:list');

    // Check the structure of each command
    foreach ($content as $command) {
        expect($command)->toHaveKey('name');
        expect($command)->toHaveKey('description');
        expect($command['name'])->toBeString();
        expect($command['description'])->toBeString();
    }

    // Check that commands are sorted alphabetically
    $sortedNames = $commandNames;
    sort($sortedNames);
    expect($commandNames)->toBe($sortedNames);
});

<?php

namespace Laravel\Boost\Mcp\Tools;

use Generator;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

class SearchDocs extends Tool
{
    public function description(): string
    {
        return 'Search for up-to-date version-specific documentation related to this project and its packages. You must pass packages found from the application-info tool';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('queries')
            ->description('Comma separated list of queries to perform')->required()
            ->string('packages')
            ->description('Comma separated list of `packageName#packageVersion` from application-info tool')
            ->required()
            ->string('library')
            ->description('Specify a particular doc library to filter to when you know the exact library this is for i.e. inertia-laravel, laravel, pest, ')
            ->optional();
    }

    // TODO: Add tool to get a list of available libraries
    public function handle(array $arguments): ToolResult|Generator
    {
        $apiUrl = config('boost.hosted.api_url', 'https://boost.laravel.com').'/api/docs';
        $queries = array_map('trim', explode(',', $arguments['queries']));
        $packages = array_map(function (string $packageString) {
            $parts = explode('#', trim($packageString));

            return ['name' => $parts[0], 'version' => $parts[1]];
        }, explode(',', $arguments['packages']));
        $library = $arguments['library'] ?? null;

        return ToolResult::text('howdy');
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

// TODO: Allow package filtering? So we can search all docs at once, or allow the client to filter packages on 'inertia' or 'laravel' or 'pennant', etc..

use Generator;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;

class SearchDocs extends Tool
{
    use MakesHttpRequests;

    public function __construct(protected Roster $roster) {}

    public function description(): string
    {
        return 'Search for up-to-date version-specific documentation related to this project and its packages. This tool will search Laravel hosted documentation based on the packages installed and is perfect for all Laravel related packages. Laravel, inertia, pest, livewire, nova, nightwatch, and more.'.PHP_EOL.'You must use this tool to search for Laravel-ecosystem docs before using other approaches.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('queries')
            ->description('### separated list of queries to perform. Useful to pass multiple if you aren\'t sure if it is "toggle" or "switch, or "infinite scroll" or "infinite load", for example.')->required()
            ->integer('token_limit')
            ->description('Maximum number of tokens to return in the response. Defaults to 10,000 tokens, maximum 1,000,000 tokens.');
    }

    public function handle(array $arguments): ToolResult|Generator
    {
        $apiUrl = config('boost.hosted.api_url', 'https://boost.laravel.com').'/api/docs';

        $queries = array_filter(
            array_map('trim', explode('###', $arguments['queries'])),
            fn ($query) => $query !== '' && $query !== '*'
        );

        try {
            $packagesCollection = $this->roster->packages();

            $packages = $packagesCollection->map(function (Package $package) {
                $name = $package->rawName();
                $version = $package->majorVersion().'.x';

                return [
                    'name' => $name,
                    'version' => $version,
                ];
            })->values()->toArray();
        } catch (\Throwable $e) {
            return ToolResult::error('Failed to get packages: '.$e->getMessage());
        }

        $tokenLimit = $arguments['token_limit'] ?? 10000;
        $tokenLimit = min($tokenLimit, 1000000); // Cap at 1M tokens

        $payload = [
            'queries' => $queries,
            'packages' => $packages,
            'token_limit' => $tokenLimit,
        ];
        try {
            $response = $this->client()->asJson()->post($apiUrl, $payload);

            if (! $response->successful()) {
                return ToolResult::error('Failed to search documentation: '.$response->body());
            }
        } catch (\Throwable $e) {
            return ToolResult::error('HTTP request failed: '.$e->getMessage());
        }

        $data = $response->json();
        $results = $data['results'] ?? [];

        $concatenatedKnowledge = collect($results)
            ->map(fn ($result) => $result['content'] ?? '')
            ->filter()
            ->join("\n\n---\n\n");

        return ToolResult::json([
            'knowledge_count' => count($results),
            'knowledge' => $concatenatedKnowledge,
        ]);
    }
}

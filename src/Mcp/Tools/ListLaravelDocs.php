<?php

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Throwable;

#[IsReadOnly()]
class ListLaravelDocs extends Tool
{
    use MakesHttpRequests;

    public function description(): string
    {
        return 'List all documentation files available for the currently installed major Laravel framework version.'.PHP_EOL.
            'It\'s critical you use this and the get-laravel-doc tool to get the correct documentation for this application.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        // No inputs required.
        return $schema;
    }

    /**
     * @param  array<string>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        // Determine the major version (e.g. 12.x)
        $version = Application::VERSION; // e.g. "12.6.0" or "12.x-dev"
        $major = Str::before($version, '.');
        $ref = $major.'.x';

        $cacheKey = "boost:mcp:laravel-docs-list:{$ref}";

        $fetchDocs = function () use ($ref) {
            $url = "https://api.github.com/repos/laravel/docs/contents/?ref={$ref}";

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->client()->get($url);

            if (! $response->successful()) {
                return ToolResult::error('Failed to fetch Laravel docs list: '.$response->body());
            }

            /** @var array<int,array<string,mixed>> $data */
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            // Extract only file names (exclude directories)
            return collect($data)
                ->filter(fn ($item) => ($item['type'] ?? '') === 'file')
                ->pluck('name')
                ->values()
                ->all();
        };

        try {
            /** @var array<int,string>|ToolResult $result */
            $result = Cache::remember($cacheKey, now()->addWeek(), $fetchDocs);
        } catch (Throwable $e) {
            // Cache store failed (e.g. misconfigured driver). Fallback to direct fetch with no caching.
            $result = $fetchDocs();
        }

        // If the cache stored an error ToolResult (cache not written when returning early), just return it.
        if ($result instanceof ToolResult) {
            return $result;
        }

        return ToolResult::json($result);
    }
}

<?php

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Cache;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Throwable;

#[IsReadOnly()]
class ListInertiaDocs extends Tool
{
    use MakesHttpRequests;

    public function __construct(protected Roster $roster) {}

    public function description(): string
    {
        return 'List all Inertia documentation files available for the installed major Inertia version.'.PHP_EOL.
            'It\'s critical you use this and the get-inertia-doc tool to get the correct documentation for this application.';
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
        [$ref, $shouldProceed] = $this->determineVersionRef();
        if (! $shouldProceed) {
            return ToolResult::error('Inertia is not installed in this project.');
        }

        $cacheKey = "boost:mcp:inertia-docs-list:{$ref}";

        $fetchDocs = function () use ($ref) {
            $path = 'resources/js/Pages';
            $url = "https://api.github.com/repos/inertiajs/inertiajs.com/contents/{$path}?ref={$ref}";

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->client()->get($url);

            if (! $response->successful()) {
                return ToolResult::error('Failed to fetch Inertia docs list: '.$response->body());
            }

            /** @var array<int,array<string,mixed>> $data */
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

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
            $result = $fetchDocs();
        }

        if ($result instanceof ToolResult) {
            return $result;
        }

        return ToolResult::json($result);
    }

    /**
     * Determine the Inertia version ref (v1, v2, etc.) and whether the tool should proceed.
     *
     * @return array{string,bool} [ref, shouldProceed]
     */
    private function determineVersionRef(): array
    {
        $package = $this->roster->package(Packages::INERTIA_LARAVEL);

        if (is_null($package)) {
            return ['v1', false];
        }

        return ['v'.$package->majorVersion(), true];
    }

    public function shouldRegister(): bool
    {
        return $this->roster->uses(Packages::INERTIA_LARAVEL);
    }
}

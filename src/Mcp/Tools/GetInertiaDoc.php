<?php

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Boost\Concerns\MakesHttpRequests;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Throwable;

#[IsReadOnly()]
class GetInertiaDoc extends Tool
{
    use MakesHttpRequests;

    public function __construct(protected Roster $roster)
    {
    }

    public function description(): string
    {
        return 'Fetch the contents of a single Inertia documentation file matching the installed major Inertia version.' . PHP_EOL .
            'It\'s critical you use this and the list-inertia-docs tool to get the correct documentation for this application.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->string('filename')
            ->description('The filename (e.g. "installation.md") within the Inertia docs repository to fetch.')
            ->required();

        return $schema;
    }

    /**
     * @param array<string> $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $filename = $arguments['filename'] ?? null;
        if ($filename === null || $filename === '') {
            return ToolResult::error('The "filename" argument is required.');
        }

        if (!Str::endsWith($filename, '.jsx')) {
            return ToolResult::error('The "filename" argument must end with ".jsx".');
        }

        if (!preg_match('/^[a-z0-9-]+\.jsx$/', $filename)) {
            return ToolResult::error('The "filename" argument must be a valid filename (e.g. "installation.jsx").');
        }

        [$ref, $shouldProceed] = $this->determineVersionRef();
        if (!$shouldProceed) {
            return ToolResult::error('Inertia is not installed in this project.');
        }

        $cacheKey = "boost:mcp:inertia-doc:{$ref}:{$filename}";

        $fetchDoc = function () use ($ref, $filename) {
            $path = "resources/js/Pages/{$filename}";
            $url = "https://api.github.com/repos/inertiajs/inertiajs.com/contents/{$path}?ref={$ref}";

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->client()->get($url);

            if (!$response->successful()) {
                return ToolResult::error('Failed to fetch Inertia doc: ' . $response->body());
            }

            /** @var array<string,mixed> $data */
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            if (($data['type'] ?? '') !== 'file' || !isset($data['content'])) {
                return ToolResult::error('Unexpected response structure when fetching Inertia doc.');
            }

            $base64 = str_replace("\n", '', $data['content']);
            $decoded = base64_decode($base64, true);

            if ($decoded === false) {
                return ToolResult::error('Failed to decode Inertia doc content.');
            }

            return $decoded;
        };

        try {
            /** @var string|ToolResult $result */
            $result = Cache::remember($cacheKey, now()->addWeek(), $fetchDoc);
        } catch (Throwable $e) {
            $result = $fetchDoc();
        }

        if ($result instanceof ToolResult) {
            return $result;
        }

        return ToolResult::text($result);
    }

    /**
     * Determine version ref and registration
     *
     * @return array{string,bool} [ref, shouldProceed]
     */
    private function determineVersionRef(): array
    {
        $package = $this->roster->package(Packages::INERTIA_LARAVEL);

        if (is_null($package)) {
            return ['v1', false];
        }

        return ['v' . $package->majorVersion(), true];
    }

    public function shouldRegister(): bool
    {
        return $this->roster->uses(Packages::INERTIA_LARAVEL);
    }
}

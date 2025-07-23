<?php

declare(strict_types=1);

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
class GetLaravelDoc extends Tool
{
    use MakesHttpRequests;

    public function description(): string
    {
        return 'Fetch the contents of a single Laravel documentation file matching the currently installed major framework version.'.PHP_EOL.
            'It\'s critical you use this and the list-laravel-docs tool to get the correct documentation for this application.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        $schema->string('filename')
            ->description('The filename (e.g. "installation.md") within the Laravel docs repository to fetch.')
            ->required();

        return $schema;
    }

    /**
     * @param  array<string>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $filename = $arguments['filename'] ?? null;
        if ($filename === null || $filename === '') {
            return ToolResult::error('The "filename" argument is required.');
        }

        if (! Str::endsWith($filename, '.md')) {
            return ToolResult::error('The "filename" argument must end with ".md".');
        }

        if (! preg_match('/^[a-z0-9-]+\.md$/', $filename)) {
            return ToolResult::error('The "filename" argument must be a valid filename (e.g. "installation.md").');
        }

        // Determine the major version (e.g. 12.x)
        $version = Application::VERSION; // e.g. "12.6.0" or "12.x-dev"
        $major = Str::before($version, '.');
        $ref = $major.'.x';

        $cacheKey = "boost:mcp:laravel-doc:{$ref}:{$filename}";

        $fetchDoc = function () use ($ref, $filename) {
            // Use GitHub API to retrieve the raw file contents
            $url = "https://api.github.com/repos/laravel/docs/contents/{$filename}?ref={$ref}";

            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->client()->get($url);

            if (! $response->successful()) {
                return ToolResult::error('Failed to fetch Laravel doc: '.$response->body());
            }

            /** @var array<string,mixed> $data */
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

            if (($data['type'] ?? '') !== 'file' || ! isset($data['content'])) {
                return ToolResult::error('Unexpected response structure when fetching Laravel doc.');
            }

            // GitHub API returns base64-encoded content (with newlines). Remove newlines before decoding.
            $base64 = str_replace("\n", '', $data['content']);
            $decoded = base64_decode($base64, true);

            if ($decoded === false) {
                return ToolResult::error('Failed to decode Laravel doc content.');
            }

            return $decoded;
        };

        try {
            /** @var string|ToolResult $result */
            $result = Cache::remember($cacheKey, now()->addWeek(), $fetchDoc);
        } catch (Throwable $e) {
            // Cache store failed (e.g., misconfigured driver). Fallback to direct fetch with no caching.
            $result = $fetchDoc();
        }

        if ($result instanceof ToolResult) {
            return $result;
        }

        // Return content as text (Markdown content as plain text)
        return ToolResult::text($result);
    }
}

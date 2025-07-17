<?php

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Roster;

#[IsReadOnly]
class ApplicationInfo extends Tool
{
    public function __construct(protected Roster $roster) {}

    public function description(): string
    {
        return 'Get comprehensive application information including PHP version, Laravel version, database engine, and all installed packages with their versions. You should use this tool on each new chat, and use the package & version data to write version specific code for the packages that exist.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    /**
     * @param  array<string>  $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        $packages = [];
        foreach ($this->roster->packages() as $package) {
            $packages[] = [
                'name' => $package->name(),
                'version' => $package->version(),
            ];
        }

        return ToolResult::json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => config('database.default'),
            'packages' => $packages,
        ]);
    }
}

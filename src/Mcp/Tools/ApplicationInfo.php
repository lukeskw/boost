<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use Laravel\Roster\Package;
use Laravel\Roster\Roster;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

#[IsReadOnly]
class ApplicationInfo extends Tool
{
    public function __construct(protected Roster $roster)
    {
    }

    public function description(): string
    {
        return 'Get comprehensive application information including PHP version, Laravel version, database engine, all installed packages with their versions, and all Eloquent models in the application. You should use this tool on each new chat, and use the package & version data to write version specific code for the packages that exist.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema;
    }

    /**
     * @param array<string> $arguments
     */
    public function handle(array $arguments): ToolResult
    {
        return ToolResult::json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => config('database.default'),
            'packages' => $this->roster->packages()->map(fn (Package $package) => ['roster_name' => $package->name(), 'version' => $package->version(), 'package_name' => $package->rawName()]),
            'models' => $this->discoverModels(),
        ]);
    }

    /**
     * Discover all Eloquent models in the application.
     *
     * @return array<string, string>
     */
    private function discoverModels(): array
    {
        $models = [];
        $appPath = app_path();

        if (! is_dir($appPath)) {
            return ['app-path-isnt-a-directory' => $appPath];
        }

        $finder = Finder::create()
            ->in($appPath)
            ->files()
            ->name('*.php');

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $namespace = app()->getNamespace();
            $className = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            try {
                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    if ($reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract()) {
                        $models[$className] = $appPath.DIRECTORY_SEPARATOR.$relativePath;
                    }
                }
            } catch (\Throwable) {
                // Ignore exceptions and errors from class loading/reflection
            }
        }

        return $models;
    }
}

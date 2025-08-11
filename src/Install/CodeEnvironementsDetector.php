<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Laravel\Boost\Install\CodeEnvironment\ClaudeCode;
use Laravel\Boost\Install\CodeEnvironment\CodeEnvironment;
use Laravel\Boost\Install\CodeEnvironment\Copilot;
use Laravel\Boost\Install\CodeEnvironment\Cursor;
use Laravel\Boost\Install\CodeEnvironment\PhpStorm;
use Laravel\Boost\Install\CodeEnvironment\VSCode;
use Laravel\Boost\Install\CodeEnvironment\Windsurf;
use Laravel\Boost\Install\CodeEnvironment\Zed;
use Laravel\Boost\Install\Enums\Platform;

class CodeEnvironementsDetector
{
    /** @var array<string, class-string<CodeEnvironment>> */
    private array $programs = [
        'phpstorm' => PhpStorm::class,
        'vscode' => VSCode::class,
        'cursor' => Cursor::class,
        'windsurf' => Windsurf::class,
        'claudecode' => ClaudeCode::class,
        'zed' => Zed::class,
        'copilot' => Copilot::class,
    ];

    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * Detect installed applications on the current platform.
     *
     * @return array<string>
     */
    public function discoverSystemInstalledCodeEnvironements(): array
    {
        $platform = Platform::current();

        return $this->getAllPrograms()
            ->filter(fn (CodeEnvironment $program) => $program->detectOnSystem($platform))
            ->map(fn (CodeEnvironment $program) => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Detect applications used in the current project.
     *
     * @return array<string>
     */
    public function discoverProjectInstalledCodeEnvironements(string $basePath): array
    {
        return $this->getAllPrograms()
            ->filter(fn ($program) => $program->detectInProject($basePath))
            ->map(fn ($program) => $program->name())
            ->values()
            ->toArray();
    }

    /**
     * Get all registered programs.
     *
     * @return Collection<string, CodeEnvironment>
     */
    private function getAllPrograms(): Collection
    {
        return collect($this->programs)->map(
            fn (string $className) => $this->container->make($className)
        );
    }
}

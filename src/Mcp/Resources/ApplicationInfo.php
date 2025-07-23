<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\Resource;

class ApplicationInfo extends Resource
{
    public function name(): string
    {
        return 'Application Info';
    }

    public function description(): string
    {
        return 'Learn about the technical details of the application.';
    }

    public function uri(): string
    {
        return 'file://instructions/application-info.md';
    }

    public function mimeType(): string
    {
        return 'text/markdown';
    }

    public function read(): string
    {
        $laravelVersion = app()->version();
        $phpVersion = PHP_VERSION;

        $frontendFramework = $this->guessFrontendFramework();
        $cssFramework = $this->guessCssFramework();
        $testingFramework = $this->guessTestingFramework();

        return <<<EOT
        Laravel Version: {$laravelVersion}
        PHP Version: {$phpVersion}

        Frontend Framework: {$frontendFramework}
        CSS Framework: {$cssFramework}

        Testing Framework: {$testingFramework}

        EOT;
    }

    private function guessFrontendFramework(): string
    {
        $composerJson = File::json(base_path('composer.json'));
        $packageJson = File::json(base_path('package.json'));

        if (isset($composerJson['require']['livewire/livewire'])) {
            $version = $composerJson['require']['livewire/livewire'];

            return "Livewire ({$version})";
        }

        $version = $packageJson['dependencies']['vue'] ?? $packageJson['devDependencies']['vue'] ?? null;

        if ($version) {
            return "Vue ({$version})";
        }

        $version = $packageJson['dependencies']['react'] ?? $packageJson['devDependencies']['react'] ?? null;

        if ($version) {
            return "React ({$version})";
        }

        return 'Blade';
    }

    private function guessCssFramework(): string
    {
        $packageJson = File::json(base_path('package.json'));

        $version = $packageJson['dependencies']['tailwindcss'] ?? $packageJson['devDependencies']['tailwindcss'] ?? null;

        if ($version) {
            return "Tailwind ({$version})";
        }

        $version = $packageJson['dependencies']['bootstrap'] ?? $packageJson['devDependencies']['bootstrap'] ?? null;

        if ($version) {
            return "Bootstrap ({$version})";
        }

        return 'None';
    }

    private function guessTestingFramework(): string
    {
        $composerJson = File::json(base_path('composer.json'));

        if (isset($composerJson['require-dev']['pestphp/pest'])) {
            return 'Pest';
        }

        return 'PHPUnit';
    }
}

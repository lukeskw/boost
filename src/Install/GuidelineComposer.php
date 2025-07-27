<?php

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Laravel\Roster\Roster;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class GuidelineComposer
{
    // TODO: Import the user's guidelines from base_path('.ai/')

    protected string $userGuidelineDir = '.ai/guidelines';

    /** @var Collection<string, string> */
    protected Collection $guidelines;

    protected GuidelineConfig $config;

    public function __construct(protected Roster $roster, protected Herd $herd)
    {
        $this->config = new GuidelineConfig;
    }

    public function config(GuidelineConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Auto discovers the guideline files and composes them into one string
     */
    public function compose(): string
    {
        return $this->guidelines()
            ->map(fn ($content, $key) => "# {$key}\n{$content}\n")
            ->join("\n\n====\n\n");
    }

    /**
     * @return string[]
     */
    public function used(): array
    {
        return $this->guidelines()->keys()->toArray();
    }

    /**
     * @return Collection<string, string>
     */
    public function guidelines(): Collection
    {
        if (! empty($this->guidelines)) {
            return $this->guidelines;
        }

        return $this->guidelines = $this->find();
    }

    /**
     * Key is the 'guideline key' and value is the rendered blade
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    protected function find(): Collection
    {
        $guidelines = collect(['boost/core' => $this->guideline('core')]);
        // TODO: Add package info, php version, laravel version, existing approaches, directory structure, models? General Laravel guidance that applies to all projects somehow? 'Follow existing conventions - if you are creating or editing a file, check sibling files for structure/approach/naming
        // TODO: Add project structure / relevant models / etc.. ? Kind of like Claude's /init, but for every Laravel developer regardless of IDE ? But if they already have that in Claude.md then that's gonna be doubling up and wasting tokens

        // This might be the wrong PHP version to give. This should be based on the version specified in composer.json as the version they want to support right?
        // **Target version vs runtime version**
        // Should Roster return TARGET_PHP

        $phpMajorMinor = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $guidelines->put('php/core', $this->guideline('php/core'));
        $guidelines->put('php/v'.$phpMajorMinor, $this->guidelinesDir('php/'.$phpMajorMinor));

        if (str_contains(config('app.url'), '.test') && $this->herd->isInstalled()) {
            $guidelines->put('herd/core', $this->guideline('herd/core'));
        }

        if ($this->config->laravelStyle) {
            $guidelines->put('laravel/style', $this->guideline('laravel/style'));
        }

        if ($this->config->hasAnApi) {
            $guidelines->put('laravel/api', $this->guideline('laravel/api'));
        }

        if ($this->config->caresAboutLocalization) {
            $guidelines->put('laravel/localization', $this->guideline('laravel/localization'));
            // In future, if using NextJS localization/etc.. then have a diff. rule here
        }

        // Add all core and version specific docs for Roster supported packages
        // We don't add guidelines for packages unsupported by Roster right now
        foreach ($this->roster->packages() as $package) {
            $guidelineDir = str_replace('_', '-', strtolower($package->name()));

            $guidelines->put(
                $guidelineDir.'/core',
                $this->guideline($guidelineDir.'/core')
            ); // Always add package core

            $guidelines->put(
                $guidelineDir.'/v'.$package->majorVersion(),
                $this->guidelinesDir($guidelineDir.'/'.$package->majorVersion())
            );
        }

        if ($this->config->enforceTests) {
            $guidelines->put('tests', $this->guideline('enforce-tests'));
        }

        $userGuidelines = $this->guidelineFilesInDir(base_path($this->userGuidelineDir));

        foreach ($userGuidelines as $guideline) {
            $guidelineKey = '.ai/'.$guideline->getBasename('.blade.php');
            $guidelines->put($guidelineKey, $this->guideline($guideline->getPathname()));
        }

        return $guidelines
            ->whereNotNull();
    }

    /**
     * @return Collection<string, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function guidelineFilesInDir(string $dirPath): Collection
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
        }

        try {
            return collect(iterator_to_array(Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.blade.php')));
        } catch (DirectoryNotFoundException $e) {
            return collect();
        }
    }

    protected function guidelinesDir(string $dirPath): ?string
    {
        if (! is_dir($dirPath)) {
            $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
        }

        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.blade.php');
        } catch (DirectoryNotFoundException $e) {
            return null;
        }

        $guidelines = '';
        foreach ($finder as $file) {
            $guidelines .= $this->guideline($file->getRealPath()) ?? '';
            $guidelines .= PHP_EOL;
        }

        return $guidelines;
    }

    protected function guideline(string $path): ?string
    {
        if (! file_exists($path)) {
            $path = preg_replace('/\.blade\.php$/', '', $path);
            $path = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$path.'.blade.php');
        }

        if (! file_exists($path)) {
            return null;
        }

        // Read the file content
        $content = file_get_contents($path);

        // Temporarily replace backticks with placeholders before Blade processing so we support inline code
        $placeholders = [
            '`' => '___SINGLE_BACKTICK___',
        ];

        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        $rendered = Blade::render($content);
        $rendered = str_replace(array_values($placeholders), array_keys($placeholders), $rendered);

        return trim($rendered);
    }
}

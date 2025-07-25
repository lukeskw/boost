<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand('boost:install', 'Install Laravel Boost')]
class InstallCommand extends Command
{
    private $colors;

    // Used for nicer install experience
    protected string $projectName;

    // Used as part of AI Guidelines
    protected string $projectPurpose = '';

    /** @var string[] */
    protected array $installedIdes = [];

    protected array $detectedProjectIdes = [];

    protected bool $enforceTests = true;

    protected array $idesToInstallTo = ['other'];

    protected array $boostToInstall = [];

    protected array $boostToolsToDisable = [];

    protected array $detectedProjectAgents = [];

    /** @var Collection<int, \Laravel\Boost\Contracts\Agent> */
    protected Collection $agentsToInstallTo;

    protected Roster $roster;

    public function handle(Roster $roster): void
    {
        $this->agentsToInstallTo = collect();
        $this->roster = $roster;
        $this->colors = new class {
            use Colors;
        };

        $this->projectName = basename(base_path());

        $this->intro();
        $this->detect();
        // TODO: We see these packages installed, we have rules for X, so we'll add them
        $this->query();
        $this->enact();
    }

    protected function detect()
    {
        $this->installedIdes = $this->detectInstalledIdes();
        $this->detectedProjectIdes = $this->detectIdesUsedInProject();
        //        $this->detectedProjectAgents = $this->detectProjectAgents(); // TODO: Roo, Cline, Copilot
    }

    protected function query()
    {
        // Which parts of boost should we install
        $this->boostToInstall = $this->boostToInstall();
        //        $this->boostToolsToDisable = $this->boostToolsToDisable(); // Not useful to start

        $this->projectPurpose = $this->projectPurpose();
        $this->enforceTests = $this->shouldEnforceTests(ask: false);

        $this->idesToInstallTo = $this->idesToInstallTo(); // To add boost:mcp to the correct file
        $this->agentsToInstallTo = $this->agentsToInstallTo(); // AI Guidelines, which file do they go, are they separated, or all in one file?
    }

    protected function enact()
    {
        if ($this->installingGuidelines() && !empty($this->agentsToInstallTo)) {
            $this->enactGuidelines($this->compose());
        }

        if ($this->installingMcp() && !empty($this->idesToInstallTo)) {
            echo "\ninstalling mcps now to: ";
            dump($this->idesToInstallTo);
        }

        if ($this->installingHerdMcp() && !empty($this->idesToInstallTo)) {
            echo "\ninstalling herd mcp now to: ";
            dump($this->idesToInstallTo);
        }


        if (in_array('other', $this->idesToInstallTo)) {
            $this->newLine();
            $this->line('Add to your mcp file: ./artisan boost:mcp'); // some ides require absolute
        }
    }

    protected function compose(): string
    {
        // TODO: Just move to blade views and compact public properties?
        $composed = collect(['core' => $this->guideline('core.md', [
            '{project.purpose}' => $this->projectPurpose,
            // TODO: Add package info, php version, laravel version, existing approaches, directory structure, models? General Laravel guidance that applies to all projects somehow? 'Follow existing conventions - if you are creating or editing a file, check sibling files for structure/approach/naming
//            TODO: Add project structure / relevant models / etc.. ? Kind of like Claude's /init, but for every Laravel developer regardless of IDE ? But if they already have that in Claude.md then that's gonna be doubling up and wasting tokens
        ])]);

        if (str_contains(config('app.url'), '.test') && $this->isHerdInstalled()) {
            $composed->put('herd/core', $this->guideline('herd/core.md', [
                '{app.url}' => url('/'),
            ]));
        }

        if ($this->installingStyleGuidelines()) {
            $composed->put('laravel/style', $this->guideline('laravel/style.md'));
        }

        // Add all core.md and version specific docs for Roster supported packages
        // We don't add guidelines for packages not supported by Roster right now
        foreach ($this->roster->packages() as $package) {
            $guidelineDir = str_replace('_', '-', strtolower($package->name()));
            $coreGuidelines = $this->guideline($guidelineDir . '/core.md'); // Add core
            if ($coreGuidelines) {
                $composed->put($guidelineDir . '/core', $coreGuidelines);
            }

            $composed->put(
                $guidelineDir . '/v' . $package->majorVersion(),
                $this->guidelines($guidelineDir . '/' . $package->majorVersion())
            );
        }

        if ($this->enforceTests) {
            $composed->put('tests', $this->guideline('enforce-tests.md'));
        }

        return $composed->whereNotNull()->map(fn($content, $key) => "# {$key}\n{$content}\n")
            ->join("\n\n====\n\n");
    }

    protected function guidelines(string $dirPath, array $replacements = []): ?string
    {
        $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/../../.ai/' . $dirPath);
        try {
            $finder = Finder::create()
                ->files()
                ->in($dirPath)
                ->name('*.md');
        } catch (DirectoryNotFoundException $e) {
            return null;
        }

        $guidelines = '';
        foreach ($finder as $file) {
            $guidelines .= $this->guideline($file->getRealPath(), $replacements) ?? '';
        }

        return $guidelines;
    }

    protected function guideline(string $path, array $replacements = []): ?string
    {
        if (!file_exists($path)) {
            $path = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/../../.ai/' . $path);
        }

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        return trim(str_replace(array_keys($replacements), array_values($replacements), $contents));
    }

    /**
     * Which IDEs are installed on this developer's machine?
     */
    protected function detectInstalledIdes(): array
    {
        $detected = [];

        if (PHP_OS_FAMILY !== 'Windows') {
            $macDetect = [
                'phpstorm' => '/Applications/PhpStorm.app',
                'cursor' => '/Applications/Cursor.app',
                'zed' => '/Applications/Zed.app',
            ];

            foreach ($macDetect as $ideKey => $path) {
                if (is_dir($path)) {
                    $detected[] = $ideKey;
                }
            }

            if (Process::run('which claude')->successful()) {
                $detected[] = 'claude_code';
            }
        }

        return $detected;
    }

    /**
     * Specifically want to detect what's in use in _this_ project.
     * Just because they have claude code installed doesn't mean they're using it.
     */
    protected function detectIdesUsedInProject(): array
    {
        $detected = [];
        if (is_dir(base_path('.idea')) || is_dir(base_path('.junie'))) {
            $detected[] = 'phpstorm';
        }

        if (is_dir(base_path('.vscode'))) {
            $detected[] = 'vscode';
        }

        if (is_dir(base_path('.cursor'))) {
            $detected[] = 'cursor';
        }

        if (file_exists(base_path('CLAUDE.md')) || is_dir(base_path('.claude'))) {
            $detected[] = 'claude_code';
        }

        $detected[] = 'other';

        return $detected;
    }

    protected function discoverTools(): array
    {
        $tools = [];
        $toolDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Mcp', 'Tools']);
        $finder = Finder::create()
            ->in($toolDir)
            ->files()
            ->name('*.php');

        foreach ($finder as $toolFile) {
            $fqdn = 'Laravel\\Boost\\Mcp\\Tools\\' . $toolFile->getBasename('.php');
            if (class_exists($fqdn)) {
                $tools[$fqdn] = Str::headline($toolFile->getBasename('.php'));
            }
        }

        ksort($tools);

        return $tools;
    }

    public function getHomePath(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            if (!isset($_SERVER['HOME'])) {
                $_SERVER['HOME'] = $_SERVER['USERPROFILE'];
            }

            $_SERVER['HOME'] = str_replace('\\', '/', $_SERVER['HOME']);
        }

        return $_SERVER['HOME'];
    }

    protected function isHerdInstalled(): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if (!$isWindows) {
            return file_exists('/Applications/Herd.app/Contents/MacOS/Herd');
        }

        return is_dir($this->getHomePath() . '/.config/herd');
    }

    protected function isHerdMCPAvailable(): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            return file_exists($this->getHomePath() . '/.config/herd/bin/herd-mcp.phar');
        }

        return file_exists($this->getHomePath() . '/Library/Application Support/Herd/bin/herd-mcp.phar');
    }

    /*
     * {
  "command": "php",
  "args": [
    PATH_TO_HERD_MCP_PHAR
  ],
  "env": {
    "SITE_PATH": BASE_PATH_OF_LARAVEL_APP,
  }
}
     */
    private function intro()
    {
        $this->newline();
        $this->line(<<<'HEADER'
 ██████╗   ██████╗   ██████╗  ███████╗ ████████╗
 ██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝
 ██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║
 ██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║
 ██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║
 ╚═════╝   ╚═════╝   ╚═════╝  ╚══════╝    ╚═╝
HEADER
        );
        intro('✦ Laravel Boost :: Install :: We Must Ship ✦');
        $this->line(' Let\'s give ' . $this->colors->bgYellow($this->colors->black($this->projectName)) . ' a Boost');
    }

    protected function projectPurpose(): string
    {
        return text(
            label: sprintf('What does %s project do? (optional)', $this->projectName),
            placeholder: 'i.e. SaaS platform selling concert tickets, integrates with Stripe and Twilio, lots of CS using Nova backend',
            hint: 'This helps guides AI. How would you explain it to a new developer?'
        );
    }

    /**
     * We shouldn't add an AI guideline enforcing tests if they don't have a basic test setup.
     * This would likely just create headaches for them, or be a waste of time as they
     * won't have the CI setup to make use of them anyway, so we're just wasting their
     * tokens/money by enforcing them.
     */
    protected function shouldEnforceTests(bool $ask = true): bool
    {
        $enforce = Finder::create()
                ->in(base_path('tests'))
                ->files()
                ->name('*.php')
                ->count() > 6;

        if ($enforce === false && $ask === true) {
            $enforce = select(
                    label: 'Should AI always create tests?',
                    options: ['Yes', 'No'],
                    default: 'Yes'
                ) === 'Yes';
        }

        return $enforce;
    }

    protected function idesToInstallTo(): array
    {
        // Limit our surface area for launch. We can support more after
        $ideOptions = [
            'claude_code' => 'Claude Code',
            'cursor' => 'Cursor',
            'phpstorm' => 'PHPStorm',
            'vscode' => 'VSCode',
            'other' => 'Other',
        ];

        // Tell API which ones?
        $autoDetectedIdesString = Arr::join(array_map(fn(string $ideKey) => $ideOptions[$ideKey] ?? '', $this->detectedProjectIdes), ', ', ' & ');

        return multiselect(
            label: sprintf('Which IDEs do you use in %s? (space to select)', $this->projectName),
            options: $ideOptions,
            default: $this->detectedProjectIdes,
            scroll: 5,
            required: true,
            hint: sprintf('Auto-detected %s for you', $autoDetectedIdesString)
        );
    }

    protected function boostToInstall(): array
    {
        $defaultToInstallOptions = ['mcp_server', 'ai_guidelines'];
        $toInstallOptions = [
            'mcp_server' => 'Boost MCP Server',
            'ai_guidelines' => 'Package AI Guidelines (i.e. Framework, Inertia, Pest)',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ];

        if ($this->isHerdMCPAvailable()) {
            $toInstallOptions['herd_mcp'] = 'Herd MCP';
            $defaultToInstallOptions[] = 'herd_mcp';
        }

        return multiselect(
            label: 'What shall we install?',
            options: $toInstallOptions,
            default: $defaultToInstallOptions,
            required: true,
            hint: 'Style guidelines are best for new projects',
        );
    }

    protected function boostToolsToDisable(): array
    {
        return multiselect(
            label: 'Do you need to disable any Boost provided tools?',
            options: $this->discoverTools(),
            scroll: 4,
            hint: 'You can exclude or include them later in the config file',
        );
    }

    protected function detectProjectAgents(): array
    {
        return [];
    }

    /**
     * @return Collection<int, \Laravel\Boost\Contracts\Agent>
     */
    protected function agentsToInstallTo(): Collection
    {
        $agents = [];
        if (!$this->installingGuidelines()) {
            return collect();
        }

        $agentDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Install', 'Agents']);

        $finder = Finder::create()
            ->in($agentDir)
            ->files()
            ->name('*.php');

        foreach ($finder as $agentFile) {
            $className = 'Laravel\\Boost\\Install\\Agents\\' . $agentFile->getBasename('.php');

            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);

                if ($reflection->implementsInterface(\Laravel\Boost\Contracts\Agent::class)) {
                    $agents[$className] = Str::headline($agentFile->getBasename('.php'));
                }
            }
        }

        ksort($agents);

        $selectedAgentClasses = collect(multiselect(
            label: sprintf('Which agents need AI guidelines for %s?', $this->projectName),
            options: $agents,
            default: ['Laravel\\Boost\\Install\\Agents\\ClaudeCode'],//array_keys($agents),
            scroll: 4, // TODO: use detection to auto-select
        ));

        return $selectedAgentClasses->map(fn($agentClass) => new $agentClass());
    }

    protected function enactGuidelines(string $composedAiGuidelines): void
    {
        if (!$this->installingGuidelines()) {
            return;
        }

        if ($this->agentsToInstallTo->isEmpty()) {
            $this->info('No agents selected for guideline installation.');
            return;
        }

        $this->newLine();
        $this->info('Installing AI guidelines to selected agents...');
        $this->newLine();

        $successful = [];
        $failed = [];

        foreach ($this->agentsToInstallTo as $agent) {
            $agentName = class_basename($agent);
            $this->output->write("  {$agentName}... ");

            try {
                $guidelineWriter = new \Laravel\Boost\Install\GuidelineWriter($agent);
                $guidelineWriter->write($composedAiGuidelines);

                $successful[] = $agentName;
                $this->line('✓');
            } catch (\Exception $e) {
                $failed[$agentName] = $e->getMessage();
                $this->line('✗');
            }
        }

        $this->newLine();

        if (count($successful) > 0) {
            $this->info(sprintf('✓ Successfully installed guidelines to %d agent%s',
                count($successful),
                count($successful) === 1 ? '' : 's'
            ));
        }

        if (count($failed) > 0) {
            $this->error(sprintf('✗ Failed to install guidelines to %d agent%s:',
                count($failed),
                count($failed) === 1 ? '' : 's'
            ));
            foreach ($failed as $agentName => $error) {
                $this->line("  - {$agentName}: {$error}");
            }
        }
    }

    protected function installingGuidelines(): bool
    {
        return in_array('ai_guidelines', $this->boostToInstall, true);
    }

    protected function installingStyleGuidelines(): bool
    {
        return in_array('style_guidelines', $this->boostToInstall, true);
    }

    protected function installingMcp(): bool
    {
        return in_array('mcp_server', $this->boostToInstall, true);
    }

    protected function installingHerdMcp(): bool
    {
        return in_array('herd_mcp', $this->boostToInstall, true);
    }
}

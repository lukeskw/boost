<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Boost\Install\Cli\DisplayHelper;
use Laravel\Boost\Install\GuidelineComposer;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Boost\Install\GuidelineWriter;
use Laravel\Boost\Install\Herd;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Laravel\Roster\Roster;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
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

    /** @var Collection<int, \Laravel\Boost\Contracts\Ide> */
    protected Collection $idesToInstallTo;

    protected array $boostToInstall = [];

    protected array $boostToolsToDisable = [];

    protected array $detectedProjectAgents = [];

    /** @var Collection<int, \Laravel\Boost\Contracts\Agent> */
    protected Collection $agentsToInstallTo;

    protected Roster $roster;

    protected Herd $herd;

    private string $greenTick;

    private string $redCross;

    private Terminal $terminal;

    public function handle(Roster $roster, Herd $herd): void
    {
        $this->terminal = new Terminal;
        $this->terminal->initDimensions();
        $this->agentsToInstallTo = collect();
        $this->idesToInstallTo = collect();
        $this->roster = $roster;
        $this->herd = $herd;

        $this->colors = new class
        {
            use Colors;
        };
        $this->greenTick = $this->colors->green('✓');
        $this->redCross = $this->colors->red('✗');

        $this->projectName = basename(base_path());

        $this->intro();
        $this->detect();
        $this->query();
        $this->enact();
        $this->outro();
    }

    protected function detect()
    {
        $this->installedIdes = $this->detectInstalledIdes();
        $this->detectedProjectIdes = $this->detectIdesUsedInProject();
        //        $this->detectedProjectAgents = $this->detectProjectAgents(); // TODO: Roo, Cline, Copilot
        // TODO: Should we create all agents to start, add a 'detected' prop to them that's set on construct
        // Maybe add a trait 'DetectsInstalled' and 'DetectsUsed' (in this project)
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

    protected function enact(): void
    {
        if ($this->installingGuidelines() && ! empty($this->agentsToInstallTo)) {
            $this->enactGuidelines();
        }

        if (($this->installingMcp() || $this->installingHerdMcp()) && $this->idesToInstallTo->isNotEmpty()) {
            $this->enactMcpServers();
        }

        // Check if any of the selected IDEs is an "other" type IDE
        $hasOtherIde = true;

        if ($hasOtherIde) {
            $this->newLine();
            $this->line('Add Boost MCP manually if needed:'); // some ides require absolute
            DisplayHelper::datatable([['Command', base_path('artisan')], ['Args', 'boost:mcp']], $this->terminal->cols());
        }

        $this->publishAndUpdateConfig();
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
            $detected[] = 'claudecode';
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
            $fqdn = 'Laravel\\Boost\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
            if (class_exists($fqdn)) {
                $tools[$fqdn] = Str::headline($toolFile->getBasename('.php'));
            }
        }

        ksort($tools);

        return $tools;
    }

    private function intro()
    {
        $this->newline();
        $header = <<<HEADER
 \e[38;2;234;31;11m██████╗   ██████╗   ██████╗  ███████╗ ████████╗
 \e[38;2;234;38;18m██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝
 \e[38;2;234;45;25m██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║
 \e[38;2;234;52;32m██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║
 \e[38;2;234;59;39m██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║
 \e[38;2;234;66;46m╚═════╝   ╚═════╝   ╚═════╝  ╚══════╝    ╚═╝\e[0m
HEADER;
        foreach (explode(PHP_EOL, $header) as $i => $line) {
            echo "{$line}\n";
        }

        intro('✦ Laravel Boost :: Install :: We Must Ship ✦');
        $this->line(' Let\'s give '.$this->colors->bgYellow($this->colors->black($this->projectName)).' a Boost');
    }

    private function outro()
    {
        outro('All done. Enjoy the boost 🚀');
        outro('Get the most out of Boost by visiting https://boost.laravel.com/installed'); // TODO: Pass info on what we did so it can show specific help
    }

    protected function projectPurpose(): string
    {
        return text(
            label: sprintf('What does the %s project do? (optional)', $this->projectName),
            placeholder: 'i.e. SaaS platform selling concert tickets, integrates with Stripe and Twilio, lots of CS using Nova backend',
            default: config('boost.project_purpose') ?? '',
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

    protected function boostToInstall(): array
    {
        $defaultToInstallOptions = ['mcp_server', 'ai_guidelines'];
        $toInstallOptions = [
            'mcp_server' => 'Boost MCP Server',
            'ai_guidelines' => 'Package AI Guidelines (i.e. Framework, Inertia, Pest)',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ];

        if ($this->herd->isMcpAvailable()) {
            $toInstallOptions['herd_mcp'] = 'Herd MCP Server';
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
     * @return Collection<int, \Laravel\Boost\Contracts\Ide>
     */
    protected function idesToInstallTo(): Collection
    {
        $ides = [];
        if (! $this->installingMcp() && ! $this->installingHerdMcp()) {
            return collect();
        }

        $agentDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Install', 'Agents']);

        $finder = Finder::create()
            ->in($agentDir)
            ->files()
            ->name('*.php');

        foreach ($finder as $ideFile) {
            $className = 'Laravel\\Boost\\Install\\Agents\\'.$ideFile->getBasename('.php');

            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);

                if ($reflection->implementsInterface(\Laravel\Boost\Contracts\Ide::class) && ! $reflection->isAbstract()) {
                    $ides[$className] = Str::headline($ideFile->getBasename('.php'));
                }
            }
        }

        ksort($ides);
        //        $ides['other'] = 'Other'; // TODO: Make 'Other' work now we are working with classes not strings

        // Map detected IDE keys to class names
        $detectedClasses = [];
        foreach ($this->detectedProjectIdes as $ideKey) {
            foreach ($ides as $className => $displayName) {
                if (strtolower($ideKey) === strtolower(class_basename($className))) {
                    $detectedClasses[] = $className;
                    break;
                }
            }
        }

        $selectedIdeClasses = collect(multiselect(
            label: sprintf('Which IDEs do you use in %s? (space to select)', $this->projectName),
            options: $ides,
            default: $detectedClasses,
            scroll: 5,
            required: true,
            hint: sprintf('Auto-detected %s for you', Arr::join(array_map(fn ($c) => class_basename($c), $detectedClasses), ', ', ' & '))
        ));

        return $selectedIdeClasses->map(fn ($ideClass) => new $ideClass);
    }

    /**
     * @return Collection<int, \Laravel\Boost\Contracts\Agent>
     */
    protected function agentsToInstallTo(): Collection
    {
        $agents = [];
        if (! $this->installingGuidelines()) {
            return collect();
        }

        $agentDir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Install', 'Agents']);

        $finder = Finder::create()
            ->in($agentDir)
            ->files()
            ->name('*.php');

        foreach ($finder as $agentFile) {
            $className = 'Laravel\\Boost\\Install\\Agents\\'.$agentFile->getBasename('.php');

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
            default: ['Laravel\\Boost\\Install\\Agents\\ClaudeCode'],// array_keys($agents),
            scroll: 4, // TODO: use detection to auto-select
        ));

        return $selectedAgentClasses->map(fn ($agentClass) => new $agentClass);
    }

    protected function enactGuidelines(): void
    {
        if (! $this->installingGuidelines()) {
            return;
        }

        if ($this->agentsToInstallTo->isEmpty()) {
            $this->info('No agents selected for guideline installation.');

            return;
        }

        $guidelineConfig = new GuidelineConfig;
        $guidelineConfig->enforceTests = $this->enforceTests;
        $guidelineConfig->laravelStyle = $this->installingStyleGuidelines();

        $composer = app(GuidelineComposer::class)->config($guidelineConfig);
        $guidelines = $composer->guidelines();

        $this->newLine();
        $this->info(sprintf('Adding %d guidelines to your selected agents', $guidelines->count()));
        DisplayHelper::grid($guidelines->keys()->toArray(), $this->terminal->cols());
        $this->newLine();

        $failed = [];
        $composedAiGuidelines = $composer->compose();

        $longestAgentName = max(1, ...$this->agentsToInstallTo->map(fn ($agent) => Str::length(class_basename($agent)))->toArray());
        foreach ($this->agentsToInstallTo as $agent) {
            $agentName = class_basename($agent);
            $displayAgentName = str_pad($agentName, $longestAgentName, ' ', STR_PAD_RIGHT);
            $this->output->write("  {$displayAgentName}... ");

            try {
                (new GuidelineWriter($agent))
                    ->write($composedAiGuidelines);

                $this->line($this->greenTick);
            } catch (\Exception $e) {
                $failed[$agentName] = $e->getMessage();
                $this->line($this->redCross);
            }
        }

        $this->newLine();

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

    protected function publishAndUpdateConfig(): void
    {
        $configPath = config_path('boost.php');

        // Publish config if it doesn't exist
        if (! file_exists($configPath)) {
            $this->newLine();
            $this->info('Publishing Boost configuration file...');

            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\\Boost\\BoostServiceProvider',
                '--tag' => 'boost-config',
                '--force' => false,
            ]);

            $this->line('  Configuration published '.$this->greenTick);
        }

        $updated = $this->updateProjectPurposeInConfig($configPath, $this->projectPurpose);
    }

    protected function updateProjectPurposeInConfig(string $configPath, ?string $purpose): bool
    {
        if (empty($purpose) || $purpose === config('boost.project_purpose', '')) {
            return false;
        }

        $content = file_get_contents($configPath);
        if ($content === false) {
            return false;
        }

        $purposeExists = preg_match('/\'project_purpose\'\s+\=\>\s+(.+),/', $content, $matches);

        if (! $purposeExists) { // This shouldn't be possible
            // TODO: Add the line to after the `return [` line, gets a bit dicey here though if people don't use short array syntax for example
            return false;
        }

        $newPurpose = addcslashes($purpose, "'");
        $newPurposeLine = "'project_purpose' => '{$newPurpose}',";
        $content = str_replace($matches[0], $newPurposeLine, $content);

        return file_put_contents($configPath, $content) !== false;
    }

    protected function enactMcpServers(): void
    {
        $this->newLine();
        $this->info('Installing MCP servers to your selected IDEs');
        $this->newLine();

        $failed = [];
        $longestIdeName = max(1, ...$this->idesToInstallTo->map(fn ($ide) => Str::length(class_basename($ide)))->toArray());

        foreach ($this->idesToInstallTo as $ide) {
            $ideName = class_basename($ide);
            $ideDisplay = str_pad($ideName, $longestIdeName, ' ', STR_PAD_RIGHT);
            $this->output->write("  {$ideDisplay}... ");
            $results = [];

            // Install Laravel Boost MCP if enabled
            if ($this->installingMcp()) {
                try {
                    $result = $ide->installMcp('laravel-boost', base_path('artisan'), ['boost:mcp']);

                    if ($result) {
                        $results[] = $this->greenTick.' Boost';
                    } else {
                        $results[] = $this->redCross.' Boost';
                        $failed[$ideName]['boost'] = 'Failed to write configuration';
                    }
                } catch (\Exception $e) {
                    $results[] = $this->redCross.' Boost';
                    $failed[$ideName]['boost'] = $e->getMessage();
                }
            }

            // Install Herd MCP if enabled
            if ($this->installingHerdMcp()) {
                try {
                    // TODO: SET ENV for site path!
                    $result = $ide->installMcp('herd', PHP_BINARY, [$this->herd->mcpPath()]);

                    if ($result) {
                        $results[] = $this->greenTick.' Herd';
                    } else {
                        $results[] = $this->redCross.' Herd';
                        $failed[$ideName]['herd'] = 'Failed to write configuration';
                    }
                } catch (\Exception $e) {
                    $results[] = $this->redCross.' Herd';
                    $failed[$ideName]['herd'] = $e->getMessage();
                }
            }

            $this->line(implode(' ', $results));
        }

        $this->newLine();

        if (count($failed) > 0) {
            $this->error(sprintf('✗ Some MCP servers failed to install:'));
            foreach ($failed as $ideName => $errors) {
                foreach ($errors as $server => $error) {
                    $this->line("  - {$ideName} ({$server}): {$error}");
                }
            }
        }
    }
}

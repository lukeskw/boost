<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\Ide;
use Laravel\Boost\Install\Cli\DisplayHelper;
use Laravel\Boost\Install\CodeEnvironmentsDetector;
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
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;

#[AsCommand('boost:install', 'Install Laravel Boost')]
class InstallCommand extends Command
{
    use Colors;

    private CodeEnvironmentsDetector $codeEnvironmentsDetector;

    private Herd $herd;

    private Roster $roster;

    private Terminal $terminal;

    /** @var Collection<int, Agent> */
    private Collection $agentsToInstallTo;

    /** @var Collection<int, Ide> */
    private Collection $idesToInstallTo;

    private Collection $boostToInstall;

    private string $projectName;

    /** @var array<non-empty-string> */
    private array $systemInstalledCodeEnvironments = [];

    private array $projectInstalledCodeEnvironments = [];

    private bool $enforceTests = true;

    private array $projectInstalledAgents = [];

    private string $greenTick;

    private string $redCross;

    public function handle(CodeEnvironmentsDetector $codeEnvironmentsDetector, Herd $herd, Roster $roster, Terminal $terminal): void
    {
        $this->bootstrapBoost($codeEnvironmentsDetector, $herd, $roster, $terminal);

        $this->displayBoostHeader();
        $this->discoverEnvironment();
        $this->collectInstallationPreference();
        $this->enact();
        $this->outro();
    }

    private function bootstrapBoost(CodeEnvironmentsDetector $codeEnvironmentsDetector, Herd $herd, Roster $roster, Terminal $terminal): void
    {
        $this->codeEnvironmentsDetector = $codeEnvironmentsDetector;
        $this->herd = $herd;
        $this->roster = $roster;
        $this->terminal = $terminal;

        $this->terminal->initDimensions();
        $this->greenTick = $this->green('✓');
        $this->redCross = $this->red('✗');

        $this->agentsToInstallTo = collect();
        $this->idesToInstallTo = collect();

        $this->projectName = basename(base_path());
    }

    private function displayBoostHeader(): void
    {
        note($this->boostLogo());
        intro('✦ Laravel Boost :: Install :: We Must Ship ✦');
        note("Let's give {$this->bgYellow($this->black($this->bold($this->projectName)))} a Boost");
    }

    private function boostLogo(): string
    {
        return
         <<<'HEADER'
        ██████╗   ██████╗   ██████╗  ███████╗ ████████╗
        ██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝
        ██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║
        ██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║
        ██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║
        ╚═════╝   ╚═════╝   ╚═════╝  ╚══════╝    ╚═╝
        HEADER;
    }

    private function discoverEnvironment(): void
    {
        $this->systemInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverSystemInstalledCodeEnvironments();
        $this->projectInstalledCodeEnvironments = $this->codeEnvironmentsDetector->discoverProjectInstalledCodeEnvironments(base_path());
        $this->projectInstalledAgents = $this->codeEnvironmentsDetector->discoverProjectInstalledCodeEnvironments(base_path());
    }

    private function collectInstallationPreference(): void
    {
        $this->boostToInstall = $this->selectBoostFeatures();
        $this->enforceTests = $this->determineTestEnforcement(ask: false);
        $this->idesToInstallTo = $this->selectTargetIdes();
        $this->agentsToInstallTo = $this->selectTargetAgents();
    }

    private function enact(): void
    {
        if ($this->installingGuidelines() && ! empty($this->agentsToInstallTo)) {
            $this->enactGuidelines();
        }

        usleep(750000);

        if (($this->installingMcp() || $this->installingHerdMcp()) && $this->idesToInstallTo->isNotEmpty()) {
            $this->enactMcpServers();
        }
    }

    private function discoverTools(): array
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

    private function outro(): void
    {
        $label = 'https://boost.laravel.com/installed';

        // Build install data - CSV format with type prefixes
        $data = [];

        $ideNames = $this->idesToInstallTo->map(fn ($ide) => 'i:'.class_basename($ide))->toArray();
        $agentNames = $this->agentsToInstallTo->map(fn ($agent) => 'a:'.class_basename($agent))->toArray();
        $boostFeatures = $this->boostToInstall->map(fn ($feature) => 'b:'.$feature)->toArray();

        // Guidelines installed (prefix: g)
        $guidelines = [];
        if ($this->installingGuidelines()) {
            $guidelines[] = 'g:ai';
        }

        if ($this->installingStyleGuidelines()) {
            $guidelines[] = 'g:style';
        }

        // Combine all data
        $allData = array_merge($ideNames, $agentNames, $boostFeatures, $guidelines);

        // Create compact CSV string and base64 encode
        $trackingData = base64_encode(implode(',', $allData));

        $link = $this->hyperlink($label, 'https://boost.laravel.com/installed/?d='.$trackingData);

        $text = 'Enjoy the boost 🚀 ';
        $paddingLength = (int) (floor(($this->terminal->cols() - mb_strlen($text.$label)) / 2)) - 2;

        echo "\033[42m\033[2K".str_repeat(' ', $paddingLength); // Make the entire line have a green background
        echo $this->black($this->bold($text.$link)).PHP_EOL;
    }

    private function hyperlink(string $label, string $url): string
    {
        return "\033]8;;{$url}\007{$label}\033]8;;\033\\";
    }

    /**
     * We shouldn't add an AI guideline enforcing tests if they don't have a basic test setup.
     * This would likely just create headaches for them or be a waste of time as they
     * won't have the CI setup to make use of them anyway, so we're just wasting their
     * tokens/money by enforcing them.
     *
     * @param bool $ask
     * @return bool
     */
    protected function determineTestEnforcement(bool $ask = true): bool
    {
        $hasMinimumTests = Finder::create()
            ->in(base_path('tests'))
            ->files()
            ->name('*.php')
            ->count() > 6;

        if (! $hasMinimumTests && ! $ask) {
            $hasMinimumTests = select(
                label: 'Should AI always create tests?',
                options: ['Yes', 'No'],
                default: 'Yes'
            ) === 'Yes';
        }

        return $hasMinimumTests;
    }

    /**
     * @return Collection<int, string>
     */
    private function selectBoostFeatures(): Collection
    {
        $defaultToInstallOptions = ['mcp_server', 'ai_guidelines'];
        $toInstallOptions = [
            'mcp_server' => 'Boost MCP Server',
            'ai_guidelines' => 'Package AI Guidelines (i.e. Framework, Inertia, Pest)',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ];

        if ($this->herd->isMcpAvailable()) {
            $toInstallOptions['herd_mcp'] = 'Herd MCP Server';
        }

        return collect(multiselect(
            label: 'What shall we install?',
            options: $toInstallOptions,
            default: $defaultToInstallOptions,
            required: true,
            hint: 'Style guidelines are best for new projects',
        ));
    }

    /**
     * @return array<int, string>
     */
    protected function boostToolsToDisable(): array
    {
        return multiselect(
            label: 'Do you need to disable any Boost provided tools?',
            options: $this->discoverTools(),
            scroll: 4,
            hint: 'You can exclude or include them later in the config file',
        );
    }

    /**
     * @return array<int, string>
     */
    private function discoverProjectAgents(): array
    {
        $agents = [];
        $projectAgents = $this->codeEnvironmentsDetector->discoverProjectInstalledCodeEnvironments(base_path());

        // Map IDE detections to their corresponding agents
        $ideToAgentMap = [
            'phpstorm' => 'junie',
            'claudecode' => 'claudecode',
            'cursor' => 'cursor',
            'copilot' => 'copilot',
        ];

        foreach ($projectAgents as $app) {
            if (isset($ideToAgentMap[$app])) {
                $agents[] = $ideToAgentMap[$app];
            }
        }

        // Also check installed IDEs that might not have project files yet
        foreach ($this->systemInstalledCodeEnvironments as $ide) {
            if (isset($ideToAgentMap[$ide]) && ! in_array($ideToAgentMap[$ide], $agents)) {
                $agents[] = $ideToAgentMap[$ide];
            }
        }

        return array_unique($agents);
    }

    /**
     * @return Collection<int, Ide>
     */
    private function selectTargetIdes(): Collection
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

                if ($reflection->implementsInterface(Ide::class) && ! $reflection->isAbstract()) {
                    $ides[$className] = Str::headline($ideFile->getBasename('.php'));
                }
            }
        }

        ksort($ides);

        // Map detected IDE keys to class names
        $detectedClasses = [];
        foreach ($this->projectInstalledCodeEnvironments as $ideKey) {
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
        ))->sort();

        return $selectedIdeClasses->map(fn ($ideClass) => new $ideClass);
    }

    /**
     * @return Collection<int, Agent>
     */
    private function selectTargetAgents(): Collection
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

                if ($reflection->implementsInterface(Agent::class)) {
                    $agents[$className] = Str::headline($agentFile->getBasename('.php'));
                }
            }
        }

        ksort($agents);

        // Map detected agent keys to class names
        $detectedClasses = [];
        foreach ($this->projectInstalledAgents as $agentKey) {
            foreach ($agents as $className => $displayName) {
                if (strtolower($agentKey) === strtolower(class_basename($className))) {
                    $detectedClasses[] = $className;
                    break;
                }
            }
        }

        $selectedAgentClasses = collect(multiselect(
            label: sprintf('Which agents need AI guidelines for %s?', $this->projectName),
            options: $agents,
            default: $detectedClasses,
            scroll: 4,
        ))->sort();

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
        $guidelineConfig->caresAboutLocalization = $this->detectLocalization();
        $guidelineConfig->hasAnApi = false;

        $composer = app(GuidelineComposer::class)->config($guidelineConfig);
        $guidelines = $composer->guidelines();

        $this->newLine();
        $this->info(sprintf(' Adding %d guidelines to your selected agents', $guidelines->count()));
        DisplayHelper::grid($guidelines->keys()->sort()->toArray(), $this->terminal->cols());
        $this->newLine();
        usleep(750000);

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
        return $this->boostToInstall->contains('ai_guidelines');
    }

    protected function installingStyleGuidelines(): bool
    {
        return $this->boostToInstall->contains('style_guidelines');
    }

    protected function installingMcp(): bool
    {
        return $this->boostToInstall->contains('mcp_server');
    }

    protected function installingHerdMcp(): bool
    {
        return $this->boostToInstall->contains('herd_mcp');
    }

    protected function publishAndUpdateConfig(): void
    {
        $configPath = config_path('boost.php');

        // Publish config if it doesn't exist
        if (! file_exists($configPath)) {
            $this->newLine();
            $this->info(' Publishing Boost configuration file...');

            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\\Boost\\BoostServiceProvider',
                '--tag' => 'boost-config',
                '--force' => false,
            ]);

            $this->line('  Configuration published '.$this->greenTick);
            $this->newLine();
        }

        //        $updated = $this->updateProjectPurposeInConfig($configPath, $this->projectPurpose);
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
        $this->info(' Installing MCP servers to your selected IDEs');
        $this->newLine();

        usleep(750000);

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
                    $result = $ide->installMcp(
                        key: 'herd',
                        command: PHP_BINARY,
                        args: [$this->herd->mcpPath()],
                        env: ['SITE_PATH' => base_path()]
                    );

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
            $this->error(sprintf('%s Some MCP servers failed to install:', $this->redCross));
            foreach ($failed as $ideName => $errors) {
                foreach ($errors as $server => $error) {
                    $this->line("  - {$ideName} ({$server}): {$error}");
                }
            }
        }
    }

    /**
     * Is the project actually using localization for their new features?
     */
    protected function detectLocalization(): bool
    {
        $actuallyUsing = false;

        /** @phpstan-ignore-next-line  */
        return is_dir(base_path('lang')) && $actuallyUsing;
    }
}

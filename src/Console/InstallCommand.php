<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
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

    // Used as part of AI Rules
    protected string $projectPurpose = '';

    /** @var string[] */
    protected array $installedIdes = [];

    protected array $detectedProjectIdes = [];

    protected bool $enforceTests = true;

    protected array $idesToInstallTo = ['other'];

    protected array $boostToInstall = [];

    protected array $boostToolsToDisable = [];

    protected array $detectedProjectAgents = [];

    protected array $agentsToInstallTo = [];

    public function handle(): void
    {
        $this->colors = new class
        {
            use Colors;
        };

        $this->projectName = basename(base_path());

        $this->intro();
        $this->detect();
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
        $this->projectPurpose = $this->projectPurpose();
        $this->enforceTests = $this->shouldEnforceTests(ask: false); // TODO: Only add 'all new code must have a test' rule if enforced
        $this->idesToInstallTo = $this->idesToInstallTo(); // To add boost:mcp to the correct file
        $this->agentsToInstallTo = $this->agentsToInstallTo(); // AI Rules, which file do they go, are they separated, or all in one file?

        // Which parts of boost should we install
        $this->boostToInstall = $this->boostToInstall();
        //        $this->boostToolsToDisable = $this->boostToolsToDisable(); // Not useful to start

    }

    protected function enact()
    {
        // AI rules, for now we're only going to support one file, not multiple. Composable AI Rule _file_
        // For most files we'll wrao in <laravel-boost-injected-rules>, for Cursor we can do .cursor/rules/laravel-boost.mdc

        if (in_array('other', $this->idesToInstallTo)) {
            $this->line('Add to your mcp file: ./artisan boost:mcp'); // some ides require absolute
        }
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
            $fqdn = 'Laravel\\Boost\\Mcp\\Tools\\'.$toolFile->getBasename('.php');
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
            if (! isset($_SERVER['HOME'])) {
                $_SERVER['HOME'] = $_SERVER['USERPROFILE'];
            }

            $_SERVER['HOME'] = str_replace('\\', '/', $_SERVER['HOME']);
        }

        return $_SERVER['HOME'];
    }

    protected function isHerdInstalled(): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if (! $isWindows) {
            return file_exists('/Applications/Herd.app/Contents/MacOS/Herd');
        }

        return is_dir($this->getHomePath().'/.config/herd');
    }

    protected function isHerdMCPAvailable(): bool
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            return file_exists($this->getHomePath().'/.config/herd/bin/herd-mcp.phar');
        }

        return file_exists($this->getHomePath().'/Library/Application Support/Herd/bin/herd-mcp.phar');
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
        $this->line(' Let\'s setup Laravel Boost in your IDEs for '.$this->colors->bgYellow($this->colors->black($this->projectName)));
    }

    protected function projectPurpose(): string
    {
        return text(
            label: 'What does this project do? (optional)',
            placeholder: 'i.e. SaaS platform selling concert tickets, integrates with Stripe and Twilio, lots of CS using Nova backend',
            hint: 'This helps guides AI. How would you explain it to a new developer?'
        );
    }

    /**
     * We shouldn't add an AI rule enforcing tests if they don't have a basic test setup.
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
        $autoDetectedIdesString = Arr::join(array_map(fn (string $ideKey) => $ideOptions[$ideKey] ?? '', $this->detectedProjectIdes), ', ', ' & ');

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
        $defaultToInstallOptions = ['mcp_server', 'ai_rules'];
        $toInstallOptions = [
            'mcp_server' => 'Boost MCP Server',
            'ai_rules' => 'Package AI Rules (i.e. Framework, Inertia, Pest)',
            'style_rules' => 'Laravel Style AI Rules',
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
            hint: 'Style rules are best for new projects',
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

    protected function detectProjectAgents(): array {}

    protected function agentsToInstallTo(): array
    {
        return [];
        // multi select ask
    }
}

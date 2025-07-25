<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Terminal;
use Laravel\Roster\Enums\Packages;
use Laravel\Roster\Roster;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
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

    private string $greenTick;

    private string $redCross;

    private Terminal $terminal;

    public function handle(Roster $roster): void
    {
        $this->terminal = new Terminal;
        $this->terminal->initDimensions();
        $this->agentsToInstallTo = collect();
        $this->idesToInstallTo = collect();
        $this->roster = $roster;
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
            $this->enactGuidelines($this->findGuidelines());
        }

        if (($this->installingMcp() || $this->installingHerdMcp()) && $this->idesToInstallTo->isNotEmpty()) {
            $this->enactMcpServers();
        }

        // Check if any of the selected IDEs is an "other" type IDE
        $hasOtherIde = true;

        if ($hasOtherIde) {
            $this->newLine();
            $this->line('Add Boost MCP manually if needed:'); // some ides require absolute
            $this->datatable([['Command', base_path('artisan')], ['Args', 'boost:mcp']]);
        }
    }

    protected function findGuidelines(): Collection
    {
        // TODO: Just move to blade views and compact public properties?
        $composed = collect(['core' => $this->guideline('core.md', [
            '{project.purpose}' => $this->projectPurpose ?: 'Unknown',
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
        // We don't add guidelines for packages unsupported by Roster right now
        foreach ($this->roster->packages() as $package) {
            $guidelineDir = str_replace('_', '-', strtolower($package->name()));

            $composed->put($guidelineDir.'/core', $this->guideline($guidelineDir.'/core.md')); // Add core
            $composed->put(
                $guidelineDir.'/v'.$package->majorVersion(),
                $this->guidelines($guidelineDir.'/'.$package->majorVersion())
            );
        }

        if ($this->enforceTests) {
            $composed->put('tests', $this->guideline('enforce-tests.md'));
        }

        return $composed
            ->whereNotNull();
    }

    protected function compose(Collection $composed): string
    {
        return $composed
            ->map(fn ($content, $key) => "# {$key}\n{$content}\n")
            ->join("\n\n====\n\n");
    }

    protected function guidelines(string $dirPath, array $replacements = []): ?string
    {
        $dirPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$dirPath);
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
        if (! file_exists($path)) {
            $path = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../.ai/'.$path);
        }

        if (! file_exists($path)) {
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
        return file_exists($this->herdMcpPath());
    }

    protected function herdMcpPath(): string
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            return $this->getHomePath().'/.config/herd/bin/herd-mcp.phar';
        }

        return $this->getHomePath().'/Library/Application Support/Herd/bin/herd-mcp.phar';
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
        $header = <<<HEADER
 \e[38;2;234;36;16m██████╗   ██████╗   ██████╗  ███████╗ ████████╗
 \e[38;2;234;42;22m██╔══██╗ ██╔═══██╗ ██╔═══██╗ ██╔════╝ ╚══██╔══╝
 \e[38;2;234;48;28m██████╔╝ ██║   ██║ ██║   ██║ ███████╗    ██║
 \e[38;2;234;54;34m██╔══██╗ ██║   ██║ ██║   ██║ ╚════██║    ██║
 \e[38;2;234;60;40m██████╔╝ ╚██████╔╝ ╚██████╔╝ ███████║    ██║
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

    protected function enactGuidelines(Collection $composed): void
    {
        if (! $this->installingGuidelines()) {
            return;
        }

        if ($this->agentsToInstallTo->isEmpty()) {
            $this->info('No agents selected for guideline installation.');

            return;
        }

        $this->newLine();
        $this->info(sprintf('Adding %d guidelines to your selected agents', $composed->count()));
        $this->grid($composed->keys()->toArray());
        $this->newLine();

        $successful = [];
        $failed = [];
        $composedAiGuidelines = $this->compose($composed);

        $longestAgentName = max(1, ...$this->agentsToInstallTo->map(fn ($agent) => Str::length(class_basename($agent)))->toArray());
        foreach ($this->agentsToInstallTo as $agent) {
            $agentName = class_basename($agent);
            $displayAgentName = str_pad($agentName, $longestAgentName, ' ', STR_PAD_RIGHT);
            $this->output->write("  {$displayAgentName}... ");

            try {
                $guidelineWriter = new \Laravel\Boost\Install\GuidelineWriter($agent);
                $guidelineWriter->write($composedAiGuidelines);

                $successful[] = $agentName;
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

    protected function datatable(array $data): void
    {
        if (empty($data)) {
            return;
        }

        // Calculate column widths
        $columnWidths = [];
        foreach ($data as $row) {
            $colIndex = 0;
            foreach ($row as $cell) {
                $length = mb_strlen((string) $cell);
                if (! isset($columnWidths[$colIndex]) || $length > $columnWidths[$colIndex]) {
                    $columnWidths[$colIndex] = $length;
                }
                $colIndex++;
            }
        }

        // Add padding
        $columnWidths = array_map(fn ($width) => $width + 2, $columnWidths);

        // Unicode box drawing characters
        $topLeft = '╭';
        $topRight = '╮';
        $bottomLeft = '╰';
        $bottomRight = '╯';
        $horizontal = '─';
        $vertical = '│';
        $cross = '┼';
        $topT = '┬';
        $bottomT = '┴';
        $leftT = '├';
        $rightT = '┤';

        // Draw top border
        $topBorder = $topLeft;
        foreach ($columnWidths as $index => $width) {
            $topBorder .= str_repeat($horizontal, $width);
            if ($index < count($columnWidths) - 1) {
                $topBorder .= $topT;
            }
        }
        $topBorder .= $topRight;
        $this->line($topBorder);

        // Draw rows
        $rowCount = 0;
        foreach ($data as $row) {
            $line = $vertical;
            $colIndex = 0;
            foreach ($row as $cell) {
                $cellStr = ($colIndex === 0) ? "\e[1m".$cell."\e[0m" : $cell;
                $padding = $columnWidths[$colIndex] - mb_strlen($cell);
                $line .= ' '.$cellStr.str_repeat(' ', $padding - 1).$vertical;
                $colIndex++;
            }
            $this->line($line);

            // Draw separator between rows (except after last row)
            if ($rowCount < count($data) - 1) {
                $separator = $leftT;
                foreach ($columnWidths as $index => $width) {
                    $separator .= str_repeat($horizontal, $width);
                    if ($index < count($columnWidths) - 1) {
                        $separator .= $cross;
                    }
                }
                $separator .= $rightT;
                $this->line($separator);
            }
            $rowCount++;
        }

        // Draw bottom border
        $bottomBorder = $bottomLeft;
        foreach ($columnWidths as $index => $width) {
            $bottomBorder .= str_repeat($horizontal, $width);
            if ($index < count($columnWidths) - 1) {
                $bottomBorder .= $bottomT;
            }
        }
        $bottomBorder .= $bottomRight;
        $this->line($bottomBorder);
    }

    protected function grid(array $items): void
    {
        if (empty($items)) {
            return;
        }

        // Get terminal width
        $terminalWidth = $this->terminal->cols() ?? 80;

        // Calculate the longest item length
        $maxItemLength = max(array_map('mb_strlen', $items));

        // Add padding (2 spaces on each side + 1 for border)
        $cellWidth = $maxItemLength + 4;

        // Calculate how many cells can fit per row
        $cellsPerRow = max(1, (int) floor(($terminalWidth - 1) / ($cellWidth + 1)));

        // Unicode box drawing characters
        $topLeft = '╭';
        $topRight = '╮';
        $bottomLeft = '╰';
        $bottomRight = '╯';
        $horizontal = '─';
        $vertical = '│';
        $cross = '┼';
        $topT = '┬';
        $bottomT = '┴';
        $leftT = '├';
        $rightT = '┤';

        // Group items into rows
        $rows = array_chunk($items, $cellsPerRow);

        // Draw top border
        $topBorder = $topLeft;
        for ($i = 0; $i < $cellsPerRow; $i++) {
            $topBorder .= str_repeat($horizontal, $cellWidth);
            if ($i < $cellsPerRow - 1) {
                $topBorder .= $topT;
            }
        }
        $topBorder .= $topRight;
        $this->line($topBorder);

        // Draw rows
        $rowCount = 0;
        foreach ($rows as $row) {
            $line = $vertical;
            for ($i = 0; $i < $cellsPerRow; $i++) {
                if (isset($row[$i])) {
                    $item = $row[$i];
                    $padding = $cellWidth - mb_strlen($item) - 2;
                    $line .= ' '.$item.str_repeat(' ', $padding + 1).$vertical;
                } else {
                    // Empty cell
                    $line .= str_repeat(' ', $cellWidth).$vertical;
                }
            }
            $this->line($line);

            // Draw separator between rows (except after last row)
            if ($rowCount < count($rows) - 1) {
                $separator = $leftT;
                for ($i = 0; $i < $cellsPerRow; $i++) {
                    $separator .= str_repeat($horizontal, $cellWidth);
                    if ($i < $cellsPerRow - 1) {
                        $separator .= $cross;
                    }
                }
                $separator .= $rightT;
                $this->line($separator);
            }
            $rowCount++;
        }

        // Draw bottom border
        $bottomBorder = $bottomLeft;
        for ($i = 0; $i < $cellsPerRow; $i++) {
            $bottomBorder .= str_repeat($horizontal, $cellWidth);
            if ($i < $cellsPerRow - 1) {
                $bottomBorder .= $bottomT;
            }
        }
        $bottomBorder .= $bottomRight;
        $this->line($bottomBorder);
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
                    $result = $ide->installMcp('herd', PHP_BINARY, [$this->herdMcpPath()]);

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

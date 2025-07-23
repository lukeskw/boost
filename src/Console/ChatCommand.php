<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Mcp\ToolExecutor;
use Laravel\Boost\Mcp\ToolRegistry;
use Laravel\Mcp\Server\Tools\ToolResult;

class ChatCommand extends Command
{
    protected $signature = 'boost:chat 
                           {--tool= : Execute a specific tool}
                           {--list-tools : List all available tools}
                           {--args= : JSON arguments for the tool}';

    protected $description = 'Interactive chat interface using Boost MCP tools';

    public function handle(): int
    {
        if ($this->option('list-tools')) {
            return $this->listTools();
        }

        if ($this->option('tool')) {
            return $this->executeTool();
        }

        return $this->interactiveChat();
    }

    protected function listTools(): int
    {
        $tools = ToolRegistry::getToolNames();
        
        $this->info('Available Boost MCP Tools:');
        $this->newLine();
        
        foreach ($tools as $name => $class) {
            $this->line("  <fg=green>{$name}</> - {$class}");
        }
        
        $this->newLine();
        $this->info('Usage: php artisan boost:chat --tool=ToolName --args=\'{"key":"value"}\'');
        
        return 0;
    }

    protected function executeTool(): int
    {
        $toolName = $this->option('tool');
        $tools = ToolRegistry::getToolNames();
        
        if (!isset($tools[$toolName])) {
            $this->error("Tool '{$toolName}' not found.");
            $this->info('Available tools: ' . implode(', ', array_keys($tools)));
            return 1;
        }
        
        $toolClass = $tools[$toolName];
        $args = [];
        
        if ($this->option('args')) {
            $args = json_decode($this->option('args'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON arguments: ' . json_last_error_msg());
                return 1;
            }
        }
        
        $this->info("Executing tool: {$toolName}");
        if (!empty($args)) {
            $this->info('Arguments: ' . json_encode($args, JSON_PRETTY_PRINT));
        }
        $this->newLine();
        
        $executor = app(ToolExecutor::class);
        $result = $executor->execute($toolClass, $args);
        
        $this->displayResult($result);
        
        return 0;
    }

    protected function interactiveChat(): int
    {
        $this->info('🚀 Boost Interactive Chat');
        $this->info('Type a tool name to execute it, or "help" for commands.');
        $this->newLine();
        
        while (true) {
            $input = $this->ask('boost> ');
            
            if (empty($input)) {
                continue;
            }
            
            if (in_array(strtolower($input), ['exit', 'quit', 'q'])) {
                $this->info('Goodbye! 👋');
                break;
            }
            
            if (strtolower($input) === 'help') {
                $this->showHelp();
                continue;
            }
            
            if (strtolower($input) === 'tools') {
                $this->listTools();
                continue;
            }
            
            // Try to execute as a tool name
            $tools = ToolRegistry::getToolNames();
            
            if (isset($tools[$input])) {
                $this->executeInteractiveTool($tools[$input]);
            } else {
                $this->error("Unknown command or tool: {$input}");
                $this->info('Type "help" for available commands or "tools" to list available tools.');
            }
            
            $this->newLine();
        }
        
        return 0;
    }

    protected function executeInteractiveTool(string $toolClass): void
    {
        // For now, execute with empty arguments
        // In the future, this could prompt for arguments interactively
        $executor = app(ToolExecutor::class);
        $result = $executor->execute($toolClass, []);
        
        $this->displayResult($result);
    }

    protected function displayResult(ToolResult $result): void
    {
        if ($result->isError) {
            $this->error('Error: ' . $this->extractTextFromContent($result->content));
            return;
        }

        $text = $this->extractTextFromContent($result->content);
        
        // Try to detect if it's JSON
        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->info('Result (JSON):');
            $this->line(json_encode($decoded, JSON_PRETTY_PRINT));
        } else {
            $this->info('Result:');
            $this->line($text);
        }
    }

    protected function extractTextFromContent(array $content): string
    {
        if (empty($content)) {
            return '';
        }

        $firstContent = $content[0] ?? [];
        return $firstContent['text'] ?? '';
    }

    protected function showHelp(): void
    {
        $this->info('Available commands:');
        $this->line('  help     - Show this help message');
        $this->line('  tools    - List all available tools');
        $this->line('  [tool]   - Execute a tool by name');
        $this->line('  exit     - Exit the chat');
        $this->newLine();
        $this->info('You can also use command line options:');
        $this->line('  --tool=ToolName --args=\'{"key":"value"}\'');
    }
}
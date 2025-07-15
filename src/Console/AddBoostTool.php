<?php

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:add-boost-tool {toolName}', 'Add Boost MCP tool')]
class AddBoostTool extends Command
{
    public function handle(): int
    {
        $toolName = $this->argument('toolName');

        if (! $toolName) {
            $toolName = $this->ask('What is the name of the tool?', 'ClearViewCache');
        }

        $toolName = Str::studly($toolName);
        $stubPath = realpath(__DIR__.'/../../stubs/tool.stub.php');
        if (! $stubPath) {
            $this->error('Stub file not found at '.$stubPath);

            return self::FAILURE;
        }

        $stub = file_get_contents($stubPath);
        if (! $stub) {
            $this->error('Stub file unreadable at '.$stubPath);

            return self::FAILURE;
        }
        $stub = str_replace('{TOOL_CLASSNAME}', $toolName, $stub);
        $stub = str_replace('{TOOL_DESCRIPTION}', $toolName.' tool description must be clear and descriptive to be used by the client', $stub);

        $toolPath = realpath(__DIR__.'/../Mcp/Tools/').$toolName.'.php';
        file_put_contents($toolPath, $stub);

        $this->info('Tool added successfully: '.$toolPath);

        return self::SUCCESS;
    }
    // from the stub in ../stubs/tool.stub.php
    // If toolName isn't passed in, ask for it
    // If toolname
}

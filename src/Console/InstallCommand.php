<?php

namespace Laravel\AiAssistant\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-assistant:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install AiAssistant';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('AiAssistant installed successfully.');
    }
}

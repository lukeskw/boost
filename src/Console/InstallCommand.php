<?php

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boost:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel Boost';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Laravel Boost installed successfully.');
    }
}

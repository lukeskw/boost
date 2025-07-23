<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        // Set environment to local so commands are registered
        $app['env'] = 'local';

        Artisan::call('vendor:publish', ['--tag' => 'boost-assets']);

        $app->singleton('mcp', Registrar::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [BoostServiceProvider::class];
    }
}

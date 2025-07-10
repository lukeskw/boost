<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Boost\Boost;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        Artisan::call('vendor:publish', ['--tag' => 'boost-assets']);

        $app->singleton('mcp', Registrar::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Boost::$authUsing = function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Boost::$authUsing = null;
    }

    protected function getPackageProviders($app)
    {
        return [BoostServiceProvider::class];
    }
}

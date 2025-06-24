<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\AiAssistant\AiAssistant;
use Laravel\AiAssistant\AiAssistantServiceProvider;
use Laravel\Mcp\Registrar;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function defineEnvironment($app)
    {
        Artisan::call('vendor:publish', ['--tag' => 'ai-assistant-assets']);

        $app->singleton('mcp', Registrar::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        AiAssistant::$authUsing = function () {
            return true;
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        AiAssistant::$authUsing = null;
    }

    protected function getPackageProviders($app)
    {
        return [AiAssistantServiceProvider::class];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Laravel\Boost\Middleware\InjectBoost;
use Tests\TestCase;

class InjectBoostTest extends TestCase
{
    public function test_it_preserves_the_original_view_response_type(): void
    {
        $this->app['view']->addNamespace('test', __DIR__.'/../../fixtures');

        Route::get('injection-test', function () {
            return view('test::injection-test');
        })->middleware(InjectBoost::class);

        $response = $this->get('injection-test');

        $response->assertViewIs('test::injection-test')
            ->assertSee('browser-logger-active')
            ->assertSee('Browser logger active (MCP server detected).');
    }
}

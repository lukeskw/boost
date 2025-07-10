<?php

namespace Laravel\Boost\Mcp;

use Laravel\Boost\Mcp\Resources\ApplicationInfo;
use Laravel\Boost\Mcp\Resources\LaravelBestPractices;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;
use Laravel\Boost\Mcp\Tools\LaravelBestPractices as LaravelBestPracticesTool;
use Laravel\Boost\Mcp\Tools\LogReader;
use Laravel\Mcp\Server;

class Boost extends Server
{
    public string $serverName = 'Laravel Boost';

    public string $serverVersion = '0.0.1';

    public string $instructions = 'Laravel AI Assistant to give you a boost';

    public array $tools = [
        // AddRules::class,??
        // ArtisanHelp
        // ApplicationInfo::class, ?
        // telescope?
        // browser extension, console.log

        // laravel new project --rules (composer)
        // Inertia
        // React
        // Tailwind - does laravel maintain that separately? Or partnership?
        // Maybe shadcn/radix ui now?
        // composer require usecroft/laravel
        LaravelBestPracticesTool::class,
        LogReader::class,
        DatabaseSchema::class,
    ];

    public array $resources = [
        LaravelBestPractices::class,
        ApplicationInfo::class,
    ];
}

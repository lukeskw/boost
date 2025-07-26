<?php

declare(strict_types=1);

return [
    // How would you explain this project's goal/approach/purpose to a new developer?
    'project_purpose' => null,
    'hosted' => [
        'api_url' => env('BOOST_HOSTED_API_URL', 'https://boost.laravel.com'),
        'token' => env('BOOST_HOSTED_TOKEN'),
    ],
    'chat' => [
        'model' => 'gpt-4o-mini', // recommend gpt-4o
        'openai_api_key' => env('BOOST_OPENAI_API_KEY', env('OPENAI_API_KEY')),
    ],
    'mcp' => [
        'tools' => [
            'exclude' => [  // Exclude built-in tools
                //                \Laravel\Boost\Mcp\Tools\LastError::class,
            ],
            'include' => [ // Include your own tools
                //                \Laravel\Boost\Mcp\Tools\LastError::class,
            ],
        ],
        'resources' => [
            'exclude' => [],
            'include' => [],
        ],
        'prompts' => [
            'exclude' => [],
            'include' => [],
        ],
    ],
    'process_isolation' => [
        'enabled' => env('BOOST_PROCESS_ISOLATION', true), // Enable by default for development
        'timeout' => env('BOOST_PROCESS_TIMEOUT', 180), // 3 minutes
        'max_concurrent' => env('BOOST_PROCESS_MAX_CONCURRENT', 5),
    ],
];

<?php

return [
    'hosted' => [
        'token' => env('BOOST_HOSTED_TOKEN'),
    ],
    'mcp' => [
        'tools' => [
            'exclude' => [  // Exclude built-in tools
                \Laravel\Boost\Mcp\Tools\LastError::class,
            ],
            'include' => [ // Include your own tools
                \Laravel\Boost\Mcp\Tools\LastError::class,
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
];

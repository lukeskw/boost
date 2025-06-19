<?php

namespace Laravel\AiAssistant\Mcp\Resources;

class LaravelBestPractices extends Resource
{
    public function name(): string
    {
        return 'Laravel Best Practices';
    }

    public function description(): string
    {
        return 'Always include these instructions when writing Laravel code.';
    }

    public function uri(): string
    {
        return 'file://instructions/laravel-best-practices.md';
    }

    public function mimeType(): string
    {
        return 'text/markdown';
    }

    public function read(): string
    {
        return file_get_contents(__DIR__.'/../../../resources/mcp_resources/laravel-best-practices.md');
    }
}

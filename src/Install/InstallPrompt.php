<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Prompts\Prompt;

class InstallPrompt extends Prompt
{
    public function __construct()
    {
        static::$themes['default'][InstallPrompt::class] = InstallRenderer::class;
    }

    protected function render(): void
    {
        $this->state = 'submit';

        parent::render();
    }

    public function value(): mixed
    {
        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Laravel\Prompts\Themes\Default\Renderer;

class InstallRenderer extends Renderer
{
    protected InstallPrompt $install;

    public function __invoke(InstallPrompt $prompt): mixed
    {
        $this->install = $prompt;

        return 'hey'.PHP_EOL;
    }
}

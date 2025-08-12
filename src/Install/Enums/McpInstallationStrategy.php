<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Enums;

enum McpInstallationStrategy: string
{
    case Shell = 'shell';
    case File = 'file';
    case None = 'none';
}
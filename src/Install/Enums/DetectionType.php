<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Enums;

enum DetectionType: string
{
    case Directory = 'directory';
    case Command = 'command';
    case File = 'file';
}

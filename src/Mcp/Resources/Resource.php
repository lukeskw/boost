<?php

namespace Laravel\AiAssistant\Mcp\Resources;

abstract class Resource
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function uri(): string;

    abstract public function mimeType(): string;

    abstract public function read(): string;
}

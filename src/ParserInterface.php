<?php

namespace Zendrop\Data;

interface ParserInterface
{
    public function canHandle(mixed $value): bool;

    public function handle(mixed $value): mixed;
}

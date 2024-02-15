<?php

namespace Zendrop\Data\Mappers;

interface NameMapperInterface
{
    public function map(string|int $name): string|int;
}

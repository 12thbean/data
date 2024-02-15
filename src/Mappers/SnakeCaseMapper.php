<?php

namespace Zendrop\Data\Mappers;

use Illuminate\Support\Str;

class SnakeCaseMapper implements NameMapperInterface
{
    public function map(int|string $name): string|int
    {
        if (!is_string($name)) {
            return $name;
        }

        return Str::snake($name);
    }
}

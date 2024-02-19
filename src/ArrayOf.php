<?php

namespace Zendrop\Data;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ArrayOf
{
    public function __construct(
        public readonly string $type
    ) {
    }
}

<?php

namespace Zendrop\Data;

interface DataInterface
{
    public static function from(array $payload, bool $useStrictKeyMatching = false): static;
}

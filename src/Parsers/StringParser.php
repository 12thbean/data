<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\ParserInterface;

class StringParser implements ParserInterface
{
    public function canHandle(mixed $value): bool
    {
        return in_array(gettype($value), ['string', 'integer', 'double']);
    }

    public function handle(mixed $value): mixed
    {
        return (string) $value;
    }
}

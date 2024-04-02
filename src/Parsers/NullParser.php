<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\ParameterType;
use Zendrop\Data\ParserInterface;

class NullParser implements ParserInterface
{
    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function canHandle(mixed $value, array $acceptableTypes): bool
    {
        if (!is_null($value)) {
            return false;
        }

        foreach ($acceptableTypes as $type) {
            if ($type->isNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     * @return null
     */
    public function handle(mixed $value, array $acceptableTypes): mixed
    {
        return null;
    }
}

<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\ParameterType;
use Zendrop\Data\ParserInterface;

class GenericParser implements ParserInterface
{
    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function canHandle(mixed $value, array $acceptableTypes): bool
    {
        return true;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function handle(mixed $value, array $acceptableTypes): mixed
    {
        if ($this->isBoolAcceptableType($acceptableTypes)) {
            return $this->convertToBool($value);
        }

        return $value;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    private function isBoolAcceptableType(array $acceptableTypes): bool
    {
        foreach ($acceptableTypes as $acceptableType) {
            if ($acceptableType->isBool()) {
                return true;
            }
        }

        return false;
    }

    private function convertToBool(mixed $value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return (null !== $result)
            ? $result
            : (bool) $result; // fallback
    }
}

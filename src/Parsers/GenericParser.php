<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\ParameterType;
use Zendrop\Data\ParserInterface;

class GenericParser implements ParserInterface
{
    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     */
    public function canHandle(mixed $value, array $acceptableTypes, array $attributes): bool
    {
        foreach ($acceptableTypes as $type) {
            if ($type->isString() || $type->isInt() || $type->isFloat() || $type->isBool() || $type->isArray()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     */
    public function handle(mixed $value, array $acceptableTypes, array $attributes): mixed
    {
        if ($this->isNullAcceptableType($acceptableTypes) && null === $value) {
            return null;
        }

        if ($this->isArrayAcceptableType($acceptableTypes) && is_array($value)) {
            return $value;
        }

        if ($this->isBoolAcceptableType($acceptableTypes)) {
            return $this->convertToBool($value);
        }

        return $value;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    private function isNullAcceptableType(array $acceptableTypes): bool
    {
        foreach ($acceptableTypes as $acceptableType) {
            if ($acceptableType->isNull()) {
                return true;
            }
        }

        return false;
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

    /**
     * @param ParameterType[] $acceptableTypes
     */
    private function isArrayAcceptableType(array $acceptableTypes): bool
    {
        foreach ($acceptableTypes as $acceptableType) {
            if ($acceptableType->isArray()) {
                return true;
            }
        }

        return false;
    }

    private function convertToBool(mixed $value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return null !== $result ? $result : (bool) $result;
    }
}

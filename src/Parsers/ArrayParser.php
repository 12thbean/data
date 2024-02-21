<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\DataInterface;
use Zendrop\Data\Exceptions\InvalidAttributeException;
use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\UnsupportedValueArrayOfException;
use Zendrop\Data\ParameterType;
use Zendrop\Data\ParserInterface;

class ArrayParser implements ParserInterface
{
    public function __construct(
        private readonly ObjectParser $objectParser
    ) {
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function canHandle(mixed $value, array $acceptableTypes): bool
    {
        if (null === $this->findTypeArrayOf($acceptableTypes)) {
            return false;
        }

        return true;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function handle(mixed $value, array $acceptableTypes): array
    {
        $typeArrayOf = $this->findTypeArrayOf($acceptableTypes);

        return $this->parseValues($value, $typeArrayOf);
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    private function validateArrayTypeIsAcceptable(array $acceptableTypes): void
    {
        foreach ($acceptableTypes as $acceptableType) {
            if ($acceptableType->isArray() || $acceptableType->isMixed()) {
                return;
            }
        }

        throw new InvalidAttributeException('Array type must be acceptable.');
    }

    /**
     * @param array<int, int|float|string|array> $value
     *
     * @return array<int, int|float|string|DataInterface|\BackedEnum>
     *
     * @throws InvalidValueException
     * @throws UnsupportedValueArrayOfException
     */
    private function parseValues(array $value, ParameterType $type): array
    {
        $result = [];

        if ($type->isObject()) {
            foreach ($value as $item) {
                $result[] = $this->objectParser->handle(
                    value: $item,
                    acceptableTypes: [$type],
                );
            }

            return $result;
        }

        $filter = match ($type->type) {
            'int' => FILTER_VALIDATE_INT,
            'float' => FILTER_VALIDATE_FLOAT,
            'string' => FILTER_DEFAULT,
            default => throw new UnsupportedValueArrayOfException("Unsupported type '{$type->type}' encountered in ArrayOf attribute. Only 'int', 'float' and 'string' types are supported.")
        };

        foreach ($value as $item) {
            $filtered = filter_var($item, $filter, FILTER_NULL_ON_FAILURE);
            if (null === $filtered) {
                $providedType = gettype($item);
                throw new InvalidValueException("Invalid value encountered in the array. Expected a value of type '{$type->type}', but an incompatible value `{$providedType}` type was found.");
            }
            $result[] = $filtered;
        }

        return $result;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     */
    private function findTypeArrayOf(array $acceptableTypes)
    {
        foreach ($acceptableTypes as $type) {
            if ($type->isList()) {
                return $type;
            }
        }

        return null;
    }
}

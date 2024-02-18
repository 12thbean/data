<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\ArrayOf;
use Zendrop\Data\Data;
use Zendrop\Data\Exceptions\InvalidArgumentException;
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
     * @param \Attribute[]    $attributes
     */
    public function canHandle(mixed $value, array $acceptableTypes, array $attributes): bool
    {
        if (null === $this->findAttributeArrayOf($attributes)) {
            return false;
        }

        $this->validateArrayTypeIsAcceptable($acceptableTypes);

        return true;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     */
    public function handle(mixed $value, array $acceptableTypes, array $attributes): array
    {
        if (null === $value) {
            return [];
        }

        $attributeArrayOf = $this->findAttributeArrayOf($attributes);

        if (null === $attributeArrayOf) {
            throw new InvalidArgumentException(
                sprintf('Argument `attributes` must contain at least one instance of the `%s` attribute.', ArrayOf::class)
            );
        }

        return $this->parseValues($value, $attributeArrayOf);
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
     * @param \Attribute[] $attributes
     */
    private function findAttributeArrayOf(array $attributes): ?ArrayOf
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof ArrayOf) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * @param array<int, int|float|string|array> $value
     *
     * @return array<int, int|float|string|Data|\BackedEnum>
     *
     * @throws InvalidValueException
     * @throws UnsupportedValueArrayOfException
     */
    private function parseValues(array $value, ArrayOf $attributeArrayOf): array
    {
        $result = [];

        if (class_exists($attributeArrayOf->type)) {
            foreach ($value as $item) {
                $result[] = $this->objectParser->handle(
                    value: $item,
                    acceptableTypes: [new ParameterType($attributeArrayOf->type)],
                    attributes: []
                );
            }

            return $result;
        }

        $filter = match ($attributeArrayOf->type) {
            'int' => FILTER_VALIDATE_INT,
            'float' => FILTER_VALIDATE_FLOAT,
            'string' => FILTER_DEFAULT,
            default => throw new UnsupportedValueArrayOfException("Unsupported type '{$attributeArrayOf->type}' encountered in ArrayOf attribute. Only 'int', 'float' and 'string' types are supported.")
        };

        foreach ($value as $item) {
            $filtered = filter_var($item, $filter, FILTER_NULL_ON_FAILURE);
            if (null === $filtered) {
                $providedType = gettype($item);
                throw new InvalidValueException(
                    "Invalid value encountered in the array. Expected a value of type '{$attributeArrayOf->type}', but an incompatible value `{$providedType}` type was found."
                );
            }
            $result[] = $filtered;
        }

        return $result;
    }
}

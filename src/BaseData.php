<?php

namespace Zendrop\Data;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Zendrop\Data\Attributes\ArrayOf;
use Zendrop\Data\Attributes\MapInputName;
use Zendrop\Data\Exceptions\CannotCreateData;
use Zendrop\Data\Mappers\NameMapperInterface;

abstract class BaseData
{
    public static function from(array $payload): static
    {
        $reflectionClass = new ReflectionClass(static::class);
        $constructor = $reflectionClass->getConstructor();
        $parameters = $constructor->getParameters();

        $resolvedParameters = array_map(
            fn($parameter) => self::resolveParameter($parameter, $payload, $reflectionClass),
            $parameters
        );

        return $reflectionClass->newInstanceArgs($resolvedParameters);
    }

    private static function resolveParameter(ReflectionParameter $parameter, array $payload, ReflectionClass $reflectionClass): mixed
    {
        $nameMapperClass = self::getNameMapper($parameter, $reflectionClass);
        $name = self::getParameterName($parameter, $nameMapperClass);
        $value = self::getParameterValue($payload, $name, $parameter);

        return self::validateAndConvertValue($value, $parameter);
    }

    /**
     * @return class-string<NameMapperInterface>|null
     */
    private static function getNameMapper(ReflectionParameter $parameter, ReflectionClass $reflectionClass): ?string
    {
        foreach ([$reflectionClass, $parameter] as $attributable) {
            if (self::hasAttribute($attributable, MapInputName::class)) {
                return self::getAttributeValue($attributable, MapInputName::class);
            }
        }

        return null;
    }

    private static function getParameterName(ReflectionParameter $parameter, ?string $nameMapperClass): string
    {
        return $nameMapperClass ? (new $nameMapperClass())->map($parameter->getName()) : $parameter->getName();
    }

    private static function getParameterValue(array $payload, string $name, ReflectionParameter $parameter): mixed
    {
        if (!array_key_exists($name, $payload) && !self::isParameterNullable($parameter)) {
            throw new CannotCreateData("Missing required parameter `{$name}`.");
        }

        return $payload[$name] ?? null;
    }

    private static function validateAndConvertValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        if (is_array($value)) {
            return self::handleArrayValue($value, $parameter);
        }

        self::ensureValueType($value, $parameter);
        return $value;
    }

    private static function handleArrayValue(array $value, ReflectionParameter $parameter): mixed
    {
        if (self::isBaseDataSubclassExpectedByParameter($parameter)) {
            return self::instantiateBaseDataSubclassFromArray($value, $parameter);
        }

        return self::hasAttribute($parameter, ArrayOf::class)
            ? self::convertArrayUsingArrayOfAttribute($value, $parameter)
            : $value;
    }

    private static function instantiateBaseDataSubclassFromArray(array $value, ReflectionParameter $parameter): BaseData
    {
        $subclassName = self::getExpectedBaseDataSubclass($parameter);
        return $subclassName::from($value);
    }

    private static function convertArrayUsingArrayOfAttribute(array $array, ReflectionParameter $parameter): array
    {
        $expectedItemsType = self::getAttributeValue($parameter, ArrayOf::class);
        return array_map(
            fn($item) => self::convertItemAccordingToType($item, $expectedItemsType, $parameter),
            $array
        );
    }

    private static function convertItemAccordingToType($item, string $expectedItemsType, ReflectionParameter $parameter): mixed
    {
        if (is_subclass_of($expectedItemsType, BaseData::class)) {
            if (!is_array($item)) {
                throw new RuntimeException("Expected array for nested BaseData object creation in `{$parameter->getName()}`.");
            }
            return $expectedItemsType::from($item);
        }

        self::ensureBuiltinType($item, $expectedItemsType, $parameter);
        return $item;
    }

    private static function ensureValueType(mixed $value, ReflectionParameter $parameter): void
    {
        if (!self::isValueTypeAcceptableByParameter($value, $parameter)) {
            $typeName = gettype($value);
            throw new CannotCreateData("Invalid type `{$typeName}` for parameter `{$parameter->getName()}`.");
        }
    }

    private static function getAttributeValue(ReflectionParameter|ReflectionClass $attributable, string $attributeClassName): string
    {
        $attributes = $attributable->getAttributes($attributeClassName);
        if (count($attributes) > 1) {
            throw new RuntimeException("Multiple `$attributeClassName` attributes found in `{$attributable->getName()}`.");
        } elseif (count($attributes) === 0) {
            throw new RuntimeException("`{$attributeClassName}` attribute not found in `{$attributable->getName()}`.");
        }

        $attribute = array_pop($attributes);
        return $attribute->getArguments()[0];
    }

    private static function hasAttribute(ReflectionParameter|ReflectionClass $attributable, string $attributeClassName): bool
    {
        return count($attributable->getAttributes($attributeClassName)) > 0;
    }

    private static function isParameterNullable(ReflectionParameter $parameter): bool
    {
       return self::isValueTypeAcceptableByParameter(null, $parameter);
    }

    private static function isValueTypeAcceptableByParameter(mixed $value, ReflectionParameter $parameter): bool
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);
        if ($expectedTypes === null) {
            return true; // No type implies any type is acceptable
        }

        foreach ($expectedTypes as $expectedType) {
            if ($value === null && $expectedType->allowsNull()) {
                return true;
            }

            if (self::validateBuiltinType($expectedType->getName(), $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, ReflectionNamedType>|null
     */
    private static function getParameterExpectedTypes(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();
        if (!$type) {
            return null;
        }

        return ($type instanceof \ReflectionUnionType)
            ? $type->getTypes()
            : [$type];
    }

    private static function validateBuiltinType(string $typeName, mixed $value): bool
    {
        return match ($typeName) {
            'int' => is_int($value),
            'string' => is_string($value),
            'float' => is_float($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'mixed' => true,
            'null' => is_null($value),
            default => throw new InvalidArgumentException("Unsupported type `{$typeName}` for validation."),
        };
    }

    private static function isBaseDataSubclassExpectedByParameter(ReflectionParameter $parameter): bool
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);
        if (!$expectedTypes) {
            return false;
        }

        foreach ($expectedTypes as $expectedType) {
            if (is_a($expectedType->getName(), BaseData::class, true)) {
                return true;
            }
        }

        return false;
    }

    private static function getExpectedBaseDataSubclass(ReflectionParameter $parameter): string
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);
        if (!$expectedTypes) {
            throw new RuntimeException("Expected type for parameter `{$parameter->getName()}` is missing.");
        }

        foreach ($expectedTypes as $expectedType) {
            if (is_subclass_of($expectedType->getName(), BaseData::class)) {
                return $expectedType->getName();
            }
        }

        throw new RuntimeException("Expected BaseData subclass for parameter `{$parameter->getName()}` is not found.");
    }

    private static function ensureBuiltinType(mixed $item, string $expectedType, ReflectionParameter $parameter): void
    {
        if (!self::validateBuiltinType($expectedType, $item)) {
            throw new RuntimeException("Item of parameter `{$parameter->getName()}` does not match expected type `{$expectedType}`.");
        }
    }
}

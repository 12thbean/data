<?php

namespace Zendrop\Data;

use Attribute;
use InvalidArgumentException;
use ReflectionAttribute;
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

        $resolvedParameters = [];

        $classNameMapper = null;
        if (self::hasAttribute($reflectionClass, MapInputName::class)) {
            $classNameMapper = self::getAttributeValue($reflectionClass, MapInputName::class);
        }

        foreach ($parameters as $reflectionParameter) {
            $propertyNameMapper = null;
            if (self::hasAttribute($reflectionParameter, MapInputName::class)) {
                $propertyNameMapper = self::getAttributeValue($reflectionParameter, MapInputName::class);
            }

            $resolvedParameters[] = self::resolveParameterValue($reflectionParameter, $payload, $classNameMapper ?? $propertyNameMapper);
        }

        return $reflectionClass->newInstanceArgs($resolvedParameters);
    }

    private static function resolveParameterValue(
        ReflectionParameter $parameter,
        array $payload,
        ?string $nameMapperClass
    ): int|float|string|bool|array|null|BaseData {
        $name = ($nameMapperClass !== null)
            ? (new ($nameMapperClass))->map($parameter->getName())
            : $parameter->getName();

        $value = $payload[$name] ?? null;

        if ($value === null && !self::isParameterNullable($parameter)) {
            throw new CannotCreateData(
                "Missing required parameter `{$parameter->getName()}`."
            );
        }

        if (is_array($value)) {
            return self::handleValueWithTypeArray($parameter, $value);
        }

        if (!self::isValueTypeAcceptableByParameter($value, $parameter)) {
            throw new CannotCreateData(
                "The provided value for parameter `{$parameter->getName()}` has not expected type " . gettype(
                    $value
                )
            );
        }

        return $value;
    }

    private static function isParameterNullable(ReflectionParameter $parameter): bool
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);

        if ($expectedTypes === null) {
            return true;
        }

        foreach ($expectedTypes as $expectedType) {
            if ($expectedType->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    private static function handleValueWithTypeArray(ReflectionParameter $parameter, array $array): array|BaseData
    {
        if (self::isBaseDataSubclassExpectedByParameter($parameter)) {
            /** @var class-string<BaseData> $subclassName */
            $subclassName = self::getExpectedBaseDataSubclass($parameter);
            return $subclassName::from($array);
        }

        if (!self::hasAttribute($parameter, ArrayOf::class)) {
            return $array;
        }

        return self::handleValueWithTypeArrayAndArrayOfAttribute($parameter, $array);
    }

    private static function handleValueWithTypeArrayAndArrayOfAttribute(
        ReflectionParameter $parameter,
        array $array
    ): array {
        /** @var class-string<BaseData>|string $expectedItemsType */
        $expectedItemsType = self::getAttributeValue($parameter, ArrayOf::class);

        $isSubclassExpected = false;
        if (class_exists($expectedItemsType)) {
            if (!is_subclass_of($expectedItemsType, BaseData::class)) {
                throw new \Exception(
                    "ArrayOf attribute of `{$parameter->getName()}` does not contain child of " . BaseData::class
                );
            }

            $isSubclassExpected = true;
        }


        $result = [];

        foreach ($array as $key => $item) {
            if ($isSubclassExpected) {
                if (!is_array($item)) {
                    throw new \Exception(
                        "`{$parameter->getName()}` parameter should contains array of arrays, but " . gettype(
                            $item
                        ) . " given in `{$key}` key of payload"
                    );
                }

                $result[] = $expectedItemsType::from($item);
            } else {
                if (!self::validateBuiltinType($expectedItemsType, $item)) {
                    throw new \Exception(
                        "All the items of `{$parameter->getName()}` should be type of `{$expectedItemsType}`"
                    );
                }

                $result[] = $item;
            }
        }

        return $result;
    }

    private static function isValueTypeAcceptableByParameter(mixed $value, ReflectionParameter $parameter): bool
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);

        if ($expectedTypes === null) {
            return true;
        }

        foreach ($expectedTypes as $expectedType) {
            if (self::validateBuiltinType($expectedType->getName(), $value)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @throws \Exception
     */
    private static function getExpectedBaseDataSubclass(ReflectionParameter $parameter): string
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);
        if ($expectedTypes === null) {
            throw new \Exception("Parameter type is missed.");
        }

        $subclasses = [];
        foreach ($expectedTypes as $expectedType) {
            if (class_exists($expectedType) && is_subclass_of($expectedType->getName(), BaseData::class)) {
                $subclasses[] = $expectedType->getName();
            }
        }

        if (count($subclasses) > 1) {
            throw new \Exception(
                "Error in `{$parameter->getName()}` parameter. More than one data types is not supported."
            );
        }

        if (count($subclasses) === 0) {
            throw new \Exception("No any data type is found for parameter `{$parameter->getName()}`.");
        }

        return $subclasses[0];
    }

    private static function isBaseDataSubclassExpectedByParameter(ReflectionParameter $parameter): bool
    {
        return self::isTypeAmongParameterExpectedTypes(BaseData::class, $parameter);
    }

    private static function isTypeAmongParameterExpectedTypes(string $typeOrClass, ReflectionParameter $parameter): bool
    {
        $expectedTypes = self::getParameterExpectedTypes($parameter);

        if ($expectedTypes === null) {
            return false;
        }

        foreach ($expectedTypes as $expectedType) {
            if (class_exists($expectedType->getName()) && is_a($expectedType->getName(), $typeOrClass, true)) {
                return true;
            }

            if ($expectedType->getName() === $typeOrClass) {
                return true;
            }
        }

        return false;
    }

    private static function getAttributeValue(ReflectionParameter|ReflectionClass $attributable, string $attributeClassName): string
    {
        $attributes = $attributable->getAttributes($attributeClassName);

        if (count($attributes) > 1) {
            throw new \Exception("$attributable->name contains more than one `{$attributeClassName}` attribute");
        } elseif (count($attributes) === 0) {
            throw new \Exception("$attributable->name does not contains `{$attributeClassName}` attribute");
        }

        /** @var ReflectionAttribute|null $attribute */
        $attribute = array_pop($attributes);

        return $attribute->getArguments()[0];
    }

    /**
     * @param  class-string<Attribute>  $attributeClassName
     */
    private static function hasAttribute(ReflectionParameter|ReflectionClass $attributable, string $attributeClassName): bool
    {
        return count($attributable->getAttributes($attributeClassName)) > 0;
    }

    /**
     * @param  ReflectionParameter  $parameter
     *
     * @return ReflectionNamedType[]|null
     */
    private static function getParameterExpectedTypes(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        return $type instanceof \ReflectionUnionType
            ? $type->getTypes()
            : [$type];
    }

    private static function validateBuiltinType(string $typeName, string|int|float|null|bool|array $value): bool
    {
        return match ($typeName) {
            'null' => is_null($value),
            'int' => is_int($value),
            'string' => is_string($value),
            'float' => is_float($value) || is_int($value),
            'bool' => is_bool($value),
            'array' => is_array($value),
            'mixed' => true,
            default => throw new InvalidArgumentException(
                "Unsupported type `{$typeName}` for builtin type validation."
            ),
        };
    }
}

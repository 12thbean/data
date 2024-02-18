<?php

namespace Zendrop\Data;

use Illuminate\Support\Str;
use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\ParameterNotFoundException;
use Zendrop\Data\Parsers\StringParser;

abstract class Data
{
    private static ?array $parsers = null;

    public static function from(array $payload, bool $strict = false): static
    {
        $reflectionClass = new \ReflectionClass(static::class);
        $constructor = $reflectionClass->getConstructor();

        $parsedValues = [];
        foreach ($constructor->getParameters() as $constructorParameter) {
            $acceptableTypes = self::getParameterAcceptableValueTypes($constructorParameter);
            $attributes = $constructorParameter->getAttributes();

            $value = self::extractParameterValueFromPayload(
                parameter: $constructorParameter,
                payload: $payload,
                shouldReturnNullIfNotFound: self::isParameterNullable($acceptableTypes)
            );

            $parsedValues[] = self::parseValue($value, $acceptableTypes, $attributes);
        }

        return $reflectionClass->newInstanceArgs($parsedValues);
    }

    private static function extractParameterValueFromPayload(
        \ReflectionParameter $parameter,
        array $payload,
        bool $shouldReturnNullIfNotFound,
        bool $useStrictKeyMatching = false
    ): mixed {
        $possibleKeys = [$parameter->getName()];

        if (!$useStrictKeyMatching) {
            $possibleKeys[] = Str::snake($parameter->getName());
            $possibleKeys[] = Str::kebab($parameter->getName());
        }

        foreach ($possibleKeys as $key) {
            if (isset($payload[$key])) {
                return $payload[$key];
            }
        }

        if ($shouldReturnNullIfNotFound) {
            return null;
        }

        throw new ParameterNotFoundException("Parameter `{$parameter->getName()}` not found in the payload.");
    }

    private static function parseValue(mixed $originalValue, array $acceptableTypes, array $attributes): mixed
    {
        $acceptableTypes = self::sortValueTypesByPriority($acceptableTypes);
        foreach ($acceptableTypes as $acceptableType) {
            $parser = self::getParser($acceptableType);
            if ($parser->canHandle($originalValue)) {
                return $parser->handle($originalValue);
            }
        }

        $originalValueType = gettype($originalValue);
        throw new InvalidValueException("The provided value with type `{$originalValueType}` cannot be parsed.");
    }

    /**
     * @return ValueType[]
     */
    private static function getParameterAcceptableValueTypes(\ReflectionParameter $parameter): array
    {
        $reflectionParameterType = $parameter->getType();

        if (null === $reflectionParameterType) {
            return [ValueType::MIXED];
        }

        /** @var ValueType[] $acceptableValueTypes */
        $acceptableValueTypes = [];

        if ($reflectionParameterType instanceof \ReflectionNamedType) {
            $acceptableValueTypes[] = ValueType::from($reflectionParameterType->getName());
            if ('mixed' !== $reflectionParameterType->getName() && $reflectionParameterType->allowsNull()) {
                $acceptableValueTypes[] = ValueType::NULL;
            }

            return $acceptableValueTypes;
        }

        foreach ($reflectionParameterType->getTypes() as $t) {
            $acceptableValueTypes[] = ValueType::from($t->getName());
        }

        return $acceptableValueTypes;
    }

    private static function sortValueTypesByPriority(array $valueTypes): array
    {
        $priorities = [
            ValueType::NULL->value => 1,
            ValueType::ARRAY->value => 2,
            ValueType::STRING->value => 3,
            ValueType::INT->value => 4,
            ValueType::FLOAT->value => 5,
            ValueType::BOOL->value => 6,
            ValueType::ENUM->value => 7,
            ValueType::OBJECT->value => 8,
        ];

        usort($valueTypes, function (ValueType $a, ValueType $b) use ($priorities) {
            return $priorities[$a->value] <=> $priorities[$b->value];
        });

        return $valueTypes;
    }

    /**
     * @param ValueType[] $valueTypes
     */
    private static function isParameterNullable(array $valueTypes): bool
    {
        foreach ($valueTypes as $valueType) {
            if (ValueType::NULL === $valueType || ValueType::MIXED === $valueType) {
                return true;
            }
        }

        return false;
    }

    private static function getParser(ValueType $valueType): ParserInterface
    {
        if (null === self::$parsers) {
            self::$parsers = [
                ValueType::STRING->value => new StringParser(),
            ];
        }

        if (!isset(self::$parsers[ValueType::STRING->value])) {
            throw new ParameterNotFoundException("Parser not found for value type `{$valueType->name}`.");
        }

        return self::$parsers[ValueType::STRING->value];
    }
}

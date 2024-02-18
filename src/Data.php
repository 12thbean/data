<?php

namespace Zendrop\Data;

use Illuminate\Support\Str;
use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\ParameterNotFoundException;
use Zendrop\Data\Parsers\ArrayParser;
use Zendrop\Data\Parsers\GenericParser;
use Zendrop\Data\Parsers\ObjectParser;

abstract class Data
{
    /**
     * @var ParserInterface[]|null
     */
    private static ?array $instantiatedParsers = null;

    /**
     * @param array<string, mixed> $payload
     */
    public static function from(array $payload, bool $useStrictKeyMatching = false): static
    {
        if (!$useStrictKeyMatching) {
            $payload = self::normalizePayloadKeys($payload);
        }

        $reflectionClass = new \ReflectionClass(static::class);
        $constructor = $reflectionClass->getConstructor();

        $parsedValues = [];
        foreach ($constructor->getParameters() as $constructorParameter) {
            $acceptableTypes = self::getParameterTypes($constructorParameter);
            $attributes = self::getParameterAttributes($constructorParameter);

            $value = self::extractParameterValueFromPayload(
                parameterName: $constructorParameter->getName(),
                payload: $payload,
                isNullable: self::isParameterNullable($acceptableTypes)
            );

            $parsedValues[] = self::parseValue($value, $acceptableTypes, $attributes);
        }

        return $reflectionClass->newInstanceArgs($parsedValues);
    }

    /**
     * @param array<string, mixed> $payload $payload
     */
    private static function normalizePayloadKeys(array $payload): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            $result[Str::camel($key)] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ParameterNotFoundException
     */
    private static function extractParameterValueFromPayload(
        string $parameterName,
        array $payload,
        bool $isNullable
    ): mixed {
        $value = $payload[$parameterName] ?? null;

        if (null === $value && !$isNullable) {
            throw new ParameterNotFoundException("Parameter `{$parameterName}` not found in the payload.");
        }

        return $value;
    }

    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     *
     * @throws InvalidValueException
     */
    private static function parseValue(mixed $originalValue, array $acceptableTypes, array $attributes): mixed
    {
        foreach (self::getParsers() as $parser) {
            if ($parser->canHandle($originalValue, $acceptableTypes, $attributes)) {
                return $parser->handle($originalValue, $acceptableTypes, $attributes);
            }
        }

        $originalValueType = gettype($originalValue);
        throw new InvalidValueException("The provided value with type `{$originalValueType}` cannot be parsed.");
    }

    /**
     * @return ParameterType[]
     */
    private static function getParameterTypes(\ReflectionParameter $parameter): array
    {
        /** @var \ReflectionUnionType|\ReflectionNamedType|null $reflectionParameterType */
        $reflectionParameterType = $parameter->getType();

        if (null === $reflectionParameterType) {
            return [new ParameterType(ParameterType::MIXED)];
        }

        /** @var ParameterType[] $acceptableValueTypes */
        $acceptableValueTypes = [];

        if ($reflectionParameterType instanceof \ReflectionNamedType) {
            $acceptableValueTypes[] = new ParameterType($reflectionParameterType->getName());
            if ($reflectionParameterType->allowsNull() && $reflectionParameterType->getName() !== 'mixed') {
                $acceptableValueTypes[] = new ParameterType(ParameterType::NULL);
            }

            return $acceptableValueTypes;
        }

        foreach ($reflectionParameterType->getTypes() as $t) {
            $acceptableValueTypes[] = new ParameterType($t->getName());
        }

        return $acceptableValueTypes;
    }

    /**
     * @return \Attribute[]
     */
    private static function getParameterAttributes(\ReflectionParameter $parameter): array
    {
        $result = [];

        foreach ($parameter->getAttributes() as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
    }

    /**
     * @param ParameterType[] $valueTypes
     */
    private static function isParameterNullable(array $valueTypes): bool
    {
        foreach ($valueTypes as $valueType) {
            if ($valueType->isNull() || $valueType->isMixed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ParserInterface[]
     */
    private static function getParsers(): array
    {
        if (null === self::$instantiatedParsers) {
            self::$instantiatedParsers = [
                new ArrayParser(new ObjectParser()),
                new ObjectParser(),
                new GenericParser(),
            ];
        }

        return self::$instantiatedParsers;
    }
}

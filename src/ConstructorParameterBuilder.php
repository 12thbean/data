<?php

namespace Zendrop\Data;

use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\ParameterNotFoundException;
use Zendrop\Data\Parsers\ArrayParser;
use Zendrop\Data\Parsers\GenericParser;
use Zendrop\Data\Parsers\ObjectParser;

class ConstructorParameterBuilder
{
    /** @var ParserInterface[] */
    private array $parsers;

    public function __construct(
        private readonly string $className,
    ) {
        $this->parsers = [
            new ArrayParser(new ObjectParser()),
            new ObjectParser(),
            new GenericParser(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     * @throws InvalidValueException
     * @throws ParameterNotFoundException
     */
    public function parse(array $payload): array
    {
        $reflectionClass = new \ReflectionClass($this->className);
        $constructorParameters = $reflectionClass->getConstructor()->getParameters();

        $parsedValues = $payload;
        foreach ($constructorParameters as $constructorParameter) {
            $parameterName = $constructorParameter->getName();
            if (!array_key_exists($parameterName, $payload)) {
                if ($constructorParameter->isDefaultValueAvailable()) {
                    $parsedValues[$parameterName] = $constructorParameter->getDefaultValue();
                }
                continue;
            }

            $parsedValues[$parameterName] = $this->parseValue($payload[$parameterName], $constructorParameter);
        }

        return $parsedValues;
    }

    /**
     * @throws InvalidValueException
     */
    private function parseValue(mixed $originalValue, \ReflectionParameter $constructorParameter): mixed
    {
        $acceptableTypes = $this->getParameterTypes($constructorParameter);

        foreach ($this->parsers as $parser) {
            if ($parser->canHandle($originalValue, $acceptableTypes)) {
                return $parser->handle($originalValue, $acceptableTypes);
            }
        }

        return $originalValue;
    }

    /**
     * @return ParameterType[]
     */
    private function getParameterTypes(\ReflectionParameter $constructorParameter): array
    {
        /** @var \ReflectionUnionType|\ReflectionNamedType|null $reflectionParameterType */
        $reflectionParameterType = $constructorParameter->getType();

        /** @var ParameterType[] $acceptableValueTypes */
        $acceptableValueTypes = [];

        $attributeArrayOf = $this->findAttributeArrayOf(
            $this->getParameterAttributes($constructorParameter)
        );

        if ($attributeArrayOf instanceof ArrayOf) {
            $acceptableValueTypes[] = new ParameterType($attributeArrayOf->type, true);
        }

        if (null === $reflectionParameterType) {
            $acceptableValueTypes[] = new ParameterType(ParameterType::MIXED);

            return $acceptableValueTypes;
        }

        if ($reflectionParameterType instanceof \ReflectionNamedType) {
            $acceptableValueTypes[] = new ParameterType($reflectionParameterType->getName());

            // Adds "null" for nullable types (e.g., "?string"), except 'mixed' which implicitly includes it
            if ($reflectionParameterType->allowsNull() && 'mixed' !== $reflectionParameterType->getName()) {
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
    private function getParameterAttributes(\ReflectionParameter $parameter): array
    {
        $result = [];

        foreach ($parameter->getAttributes() as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
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
}

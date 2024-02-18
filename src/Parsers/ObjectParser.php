<?php

namespace Zendrop\Data\Parsers;

use Zendrop\Data\Data;
use Zendrop\Data\Exceptions\ObjectInstantiatingException;
use Zendrop\Data\ParameterType;
use Zendrop\Data\ParserInterface;

class ObjectParser implements ParserInterface
{
    public function canHandle(mixed $value, array $acceptableTypes, array $attributes): bool
    {
        return null !== $this->findObjectTypeAmongAcceptableTypes($acceptableTypes);
    }

    public function handle(mixed $value, array $acceptableTypes, array $attributes): ?object
    {
        if (null === $value) {
            return null;
        }

        $targetType = $this->findObjectTypeAmongAcceptableTypes($acceptableTypes);

        if (!$targetType) {
            throw new \RuntimeException(
                sprintf('%s cannot handle any of the provided acceptable types.', static::class)
            );
        }

        return $this->instantiateObject($targetType->type, $value);
    }

    /**
     * @param  ParameterType[]  $acceptableTypes
     */
    private function findObjectTypeAmongAcceptableTypes(array $acceptableTypes): ?ParameterType
    {
        foreach ($acceptableTypes as $type) {
            if ($type->isObject()) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  class-string<Data|\BackedEnum>  $className
     * @param  array<string, mixed>|int|float|string|bool  $value
     */
    private function instantiateObject(string $className, array|int|float|string|bool $value): object
    {
        if (is_subclass_of($className, Data::class) || is_subclass_of($className, \BackedEnum::class)) {
            return $className::from($value);
        }

        throw new ObjectInstantiatingException(
            'Instantiated object should be inheritor of `' . Data::class . '` or `' . \BackedEnum::class . '`'
        );
    }
}

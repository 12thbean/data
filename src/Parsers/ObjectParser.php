<?php

namespace Zendrop\Data\Parsers;

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

        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Expected first argument to be array, %s given.', gettype($value)));
        }

        $targetType = $this->findObjectTypeAmongAcceptableTypes($acceptableTypes);

        if (!$targetType) {
            throw new \RuntimeException(sprintf('%s cannot handle any of the provided acceptable types.', static::class));
        }

        return $this->instantiateObject($targetType->type, $value);
    }

    /**
     * @param ParameterType[] $acceptableTypes
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
     * @param class-string         $className
     * @param array<string, mixed> $value
     */
    private function instantiateObject(string $className, array $value): object
    {
        if (method_exists($className, 'from') && $this->isMethodStatic($className, 'from')) {
            return $className::from($value);
        }

        return new $className(...$value);
    }

    private function isMethodStatic(string $className, string $methodName): bool
    {
        $reflectionMethod = new \ReflectionMethod($className, $methodName);

        return $reflectionMethod->isStatic();
    }
}

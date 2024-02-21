<?php

namespace Zendrop\Data;

use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\ParameterNotFoundException;

trait DataTrait
{
    /**
     * @param array<string, mixed> $payload
     *
     * @throws InvalidValueException
     * @throws ParameterNotFoundException
     */
    public static function from(array $payload, bool $useStrictKeyMatching = false): static
    {
        // Convert values to the expected types
        $valueParser = new ConstructorParameterBuilder(static::class, $useStrictKeyMatching);
        $constructorParameters = $valueParser->parse($payload);

        // instantiate the class with the parsed values
        return new (static::class)(...$constructorParameters);
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     *
     * @return array<int, static>
     *
     * @throws InvalidValueException
     * @throws ParameterNotFoundException
     */
    public static function arrayFrom(array $payload, bool $useStrictKeyMatching = false): array
    {
        $result = [];
        foreach ($payload as $item) {
            $result[] = static::from($item, $useStrictKeyMatching);
        }

        return $result;
    }
}

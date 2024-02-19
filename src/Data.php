<?php

namespace Zendrop\Data;

use Illuminate\Support\Str;
use Zendrop\Data\Exceptions\InvalidValueException;
use Zendrop\Data\Exceptions\ParameterNotFoundException;

abstract class Data
{
    /**
     * @param array<string, mixed> $payload
     * @throws InvalidValueException
     * @throws ParameterNotFoundException
     */
    public static function from(array $payload, bool $useStrictKeyMatching = false): static
    {
        // Convert keys to camelCase if strict key matching is not required
        if (!$useStrictKeyMatching) {
            $payload = self::normalizePayloadKeys($payload);
        }

        // Convert values to the expected types
        $valueParser = new ConstructorParameterBuilder(static::class);
        $constructorParameters = $valueParser->parse($payload);

        // instantiate the class with the parsed values
        return new (static::class)(...$constructorParameters);
    }

    private static function normalizePayloadKeys(array $payload): array
    {
        $normalizedPayload = [];
        foreach ($payload as $key => $value) {
            $normalizedPayload[Str::camel($key)] = $value;
        }
        return $normalizedPayload;
    }
}

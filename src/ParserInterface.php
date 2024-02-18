<?php

namespace Zendrop\Data;

interface ParserInterface
{
    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     */
    public function canHandle(mixed $value, array $acceptableTypes, array $attributes): bool;

    /**
     * @param ParameterType[] $acceptableTypes
     * @param \Attribute[]    $attributes
     */
    public function handle(mixed $value, array $acceptableTypes, array $attributes): mixed;
}

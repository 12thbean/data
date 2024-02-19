<?php

namespace Zendrop\Data;

interface ParserInterface
{
    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function canHandle(mixed $value, array $acceptableTypes): bool;

    /**
     * @param ParameterType[] $acceptableTypes
     */
    public function handle(mixed $value, array $acceptableTypes): mixed;
}

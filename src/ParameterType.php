<?php

namespace Zendrop\Data;

class ParameterType
{
    public const NULL = 'null';
    public const BOOL = 'bool';
    public const INT = 'int';
    public const FLOAT = 'float';
    public const STRING = 'string';
    public const ARRAY = 'array';
    public const MIXED = 'mixed';

    public function __construct(
        public readonly string $type,
        public readonly bool $isList = false,
    ) {
    }

    public function isNull(): bool
    {
        return self::NULL === $this->type;
    }

    public function isBool(): bool
    {
        return self::BOOL === $this->type;
    }

    public function isInt(): bool
    {
        return self::INT === $this->type;
    }

    public function isFloat(): bool
    {
        return self::FLOAT === $this->type;
    }

    public function isString(): bool
    {
        return self::STRING === $this->type;
    }

    public function isArray(): bool
    {
        return self::ARRAY === $this->type;
    }

    public function isMixed(): bool
    {
        return self::MIXED === $this->type;
    }

    public function isObject(): bool
    {
        return 'object' === $this->type || class_exists($this->type);
    }

    public function isList(): bool
    {
        return $this->isList;
    }
}

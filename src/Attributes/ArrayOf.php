<?php

namespace Zendrop\Data\Attributes;

use Attribute;
use Zendrop\Data\BaseData;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayOf
{
    public function __construct(
        /** @var class-string<BaseData> $class */
        public readonly string $class
    ) {
        if (! is_subclass_of($this->class, BaseData::class)) {
            throw new CannotFindDataClass("Class {$this->class} given does not implement `BaseData::class`");
        }
    }
}

<?php

namespace Zendrop\Data\Attributes;

use Attribute;
use Zendrop\Data\BaseData;
use Zendrop\Data\Mappers\NameMapperInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapInputName
{
    public function __construct(
        public readonly string $class
    )
    {
        if (! is_subclass_of($this->class, NameMapperInterface::class)) {
            throw new \Exception("Class {$this->class} given does not implement " . NameMapperInterface::class);
        }
    }
}

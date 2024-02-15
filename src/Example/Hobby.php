<?php

namespace Zendrop\Data\Example;

use Zendrop\Data\BaseData;

class Hobby extends BaseData
{
    public function __construct(
        public readonly string $name
    )
    {
    }
}

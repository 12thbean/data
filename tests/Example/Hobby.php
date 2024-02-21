<?php

namespace Zendrop\Data\Tests\Example;

use Zendrop\Data\DataInterface;
use Zendrop\Data\DataTrait;

class Hobby implements DataInterface
{
    use DataTrait;

    public function __construct(
        public readonly string $name
    ) {
    }
}

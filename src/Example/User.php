<?php

namespace Zendrop\Data\Example;

use Zendrop\Data\Attributes\ArrayOf;
use Zendrop\Data\BaseData;

class User extends BaseData
{
    public function __construct(
        public string $name,

        #[ArrayOf('int')]
        public array $numbers,

        #[ArrayOf(Hobby::class)]
        public array $hobbies,
    ) {
    }
}

<?php

namespace Zendrop\Data\Example;

use Zendrop\Data\Attributes\ArrayOf;
use Zendrop\Data\Attributes\MapInputName;
use Zendrop\Data\BaseData;
use Zendrop\Data\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class User extends BaseData
{
    public function __construct(
        public string $userName,

        #[ArrayOf('int')]
        public array $numbers,

        #[ArrayOf(Hobby::class)]
        public array $hobbies,
    ) {
    }
}

<?php

namespace Zendrop\Data\Tests\Example;

use Zendrop\Data\DataInterface;
use Zendrop\Data\DataTrait;
use Zendrop\Data\Skippable;
use Zendrop\Data\ToArrayInterface;
use Zendrop\Data\ToArrayTrait;

class Person implements DataInterface, ToArrayInterface
{
    use DataTrait;
    use ToArrayTrait;

    public function __construct(
        public readonly string $userName,

        public readonly bool $isCool,

        public readonly int $age,

        #[\Zendrop\Data\ArrayOf('string')]
        public readonly array $nums,

        public readonly ?float $weight,

        public readonly Hobby $hobby,

        #[\Zendrop\Data\ArrayOf(Hobby::class)]
        public readonly array $hobbies,

        public readonly Color $testEnum,

        #[\Zendrop\Data\ArrayOf(Color::class)]
        public readonly array $colors,

        public readonly string|Skippable $car = Skippable::Skipped,
    ) {
    }
}

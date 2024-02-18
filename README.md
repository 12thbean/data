# zendrop/data

## Installation

To install the package via Composer, run the following command:

```sh
composer require zendrop/data
```

## Usage examples

### Defining Data Structures

Here's how you can define simple data structures for `Person` and `Hobby`:

```php
<?php

use Zendrop\Data\Attributes\ArrayOf;
use Zendrop\Data\BaseData;

class Hobby extends BaseData
{
    public function __construct(
        public readonly string $name
    ) {
    }
}

class Person extends BaseData
{
    public function __construct(
        public readonly int $id,

        public readonly ?string $name,

        #[ArrayOf('int')]
        /** @var int[] $numbers */
        public readonly array $numbers,

        public readonly Hobby $favoriteHobby,

        #[ArrayOf(Hobby::class)]
        /** @var Hobby[] $hobbies */
        public readonly array $hobbies,
    ) {
    }
}

$person = Person::from([
    'id' => 1,
    'name' => 'John Doe',
    'numbers' => [7, 8, 9],
    'favoriteHobby' => [
        'name' => 'dancing'
    ],
    'hobbies' => [
        [
            'name' => 'swimming'
        ],
        [
            'name' => 'singing'
        ]
    ]
]);
```

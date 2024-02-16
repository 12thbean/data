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
        public readonly array $numbers,

        public readonly Hobby $favoriteHobby,

        #[ArrayOf(Hobby::class)]
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

### Input Property Names Mapping

The package supports mapping input property names to your data structure's property names. This feature is useful when
you need to map input data from different naming conventions (like snake_case) to your class properties.

Add `MapInputName` to property:

```php
<?php

use Zendrop\Data\Attributes\MapInputName;
use Zendrop\Data\BaseData;
use Zendrop\Data\Mappers\SnakeCaseMapper;

class Person extends BaseData
{
    public function __construct(
        #[MapInputName(SnakeCaseMapper::class)]
        public readonly string $firstName,
        public readonly string $lastName,
    ) {
    }
}

$person = Person::from([
    'first_name' => 'John',
    'lastName' => 'Doe'
]);
```

To apply the mapping to the entire class:

```php
<?php

use Zendrop\Data\Attributes\MapInputName;
use Zendrop\Data\BaseData;
use Zendrop\Data\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class Person extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {
    }
}

$person = Person::from([
    'first_name' => 'John',
    'lastName' => 'Doe'
]);
```

# zendrop/data

## Installation

To install the package via Composer, run the following command:

```sh
composer require zendrop/data
```

## How To Use

### Creating Data Models
1) **Implement Interfaces:** Your models should implement DataInterface and ToArrayInterface.
2) **Use Traits:** Incorporate DataTrait and ToArrayTrait for functionality.
3) **Handle Arrays:** For validating arrays, use the ArrayOf attribute. Mark fields that can be skipped with Skippable.
4) **Nested Structures:** You can create models within models.
5) **Enums:** Use BackedEnum for enum types.

### Code Example

```php
<?php

use Zendrop\Data\DataInterface;
use Zendrop\Data\DataTrait;
use Zendrop\Data\Attributes\ArrayOf;
use Zendrop\Data\Skippable;
use Zendrop\Data\ToArrayInterface;
use Zendrop\Data\ToArrayTrait;

class Tag implements DataInterface, ToArrayInterface
{
    use DataTrait;
    use ToArrayTrait;

    public function __construct(
        public readonly string $name
    ) {
    }
}

enum Occupation: string
{
    case Manager: 'manager';
    case Developer: 'developer';
}

class User implements DataInterface, ToArrayInterface
{
    use DataTrait;
    use ToArrayTrait;

    public function __construct(
        public readonly int $id,
        
        public readonly string $userName,
        
        #[ArrayOf(Tag::class)]
        /** @var Tag[] $tags */
        public readonly array $tags,
        
        public readonly Occupation $occupation,
        
        public readonly string|Skippable $bio = Skippable::Skipped
    ) {
    }
}
```

### Instantiation and Serialization

Create objects from arrays with automatic type conversion and key normalization.
Serialize objects back to arrays with flexible key formatting.

```php
// Create a User object from an array
$user = User::from([
    'id' => '42',                // will be converted to int
    'user_name' => 'John Doe',
    'tags' => [
        ['name' => 'friend'],    // will be converted to Tag class
        ['name' => 'zendrop']
    ],
    'occupation' => 'developer'  // will be converted to Occupation enum
    // 'bio' is optional and skipped here
]);

// Serialize the User object to an array
$arraySnakeCase = $user->toArray(); // Default snake_case
$arrayCamelCase = $user->toArray(ToArrayCase::Camel);
$arrayKebabCase = $user->toArray(ToArrayCase::Kebab);

```

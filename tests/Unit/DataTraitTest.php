<?php

namespace Zendrop\Data\Tests\Unit;

use Zendrop\Data\Skippable;
use Zendrop\Data\Tests\DataProvider\PayloadDataProvider;
use Zendrop\Data\Tests\Example\Color;
use Zendrop\Data\Tests\Example\Hobby;
use Zendrop\Data\Tests\Example\Person;
use Zendrop\Data\Tests\TestCase;

class DataTraitTest extends TestCase
{
    public function testDataCreating()
    {
        $payload = PayloadDataProvider::getArray();

        $createdObject = Person::from($payload);

        // Test basic properties
        $this->assertEquals($payload['userName'], $createdObject->userName);
        $this->assertEquals(true, $createdObject->isCool); // Assuming 'On' translates to true
        $this->assertEquals((int) $payload['age'], $createdObject->age);
        $this->assertEquals($payload['weight'], $createdObject->weight);

        // Test enum conversion
        $this->assertEquals(Color::RED, $createdObject->testEnum);

        // Test array handling with simple type casting and object instantiation
        foreach ($createdObject->nums as $index => $num) {
            $this->assertTrue(is_string($num), "Element $index in nums is not an int");
        }

        // Test nested object creation
        $this->assertInstanceOf(Hobby::class, $createdObject->hobby);
        $this->assertEquals($payload['hobby']['name'], $createdObject->hobby->name);

        // Test array of objects
        $this->assertCount(count($payload['hobbies']), $createdObject->hobbies);
        foreach ($createdObject->hobbies as $index => $hobby) {
            $this->assertInstanceOf(Hobby::class, $hobby);
            $this->assertEquals($payload['hobbies'][$index]['name'], $hobby->name);
        }

        // Test enum array conversion
        foreach ($createdObject->colors as $index => $color) {
            $this->assertInstanceOf(Color::class, $color);
            $this->assertEquals($payload['colors'][$index], $color->value);
        }

        // Test skippable
        $this->assertEquals(Skippable::Skipped, $createdObject->car);
        $this->assertNotEquals(Skippable::Skipped, $createdObject->motorbike);
    }

    public function testDataCreatingFromSnakeCasedPayload()
    {
        $payload = PayloadDataProvider::getArray(useSnakeCase: true);

        $createdObject = Person::from($payload);

        // Test basic properties
        $this->assertEquals($payload['user_name'], $createdObject->userName);
        $this->assertEquals(true, $createdObject->isCool); // Assuming 'On' translates to true
        $this->assertEquals((int) $payload['age'], $createdObject->age);
        $this->assertEquals($payload['weight'], $createdObject->weight);

        // Test enum conversion
        $this->assertEquals(Color::RED, $createdObject->testEnum);

        // Test array handling with simple type casting and object instantiation
        foreach ($createdObject->nums as $index => $num) {
            $this->assertTrue(is_string($num), "Element $index in nums is not an int");
        }

        // Test nested object creation
        $this->assertInstanceOf(Hobby::class, $createdObject->hobby);
        $this->assertEquals($payload['hobby']['name'], $createdObject->hobby->name);

        // Test array of objects
        $this->assertCount(count($payload['hobbies']), $createdObject->hobbies);
        foreach ($createdObject->hobbies as $index => $hobby) {
            $this->assertInstanceOf(Hobby::class, $hobby);
            $this->assertEquals($payload['hobbies'][$index]['name'], $hobby->name);
        }

        // Test enum array conversion
        foreach ($createdObject->colors as $index => $color) {
            $this->assertInstanceOf(Color::class, $color);
            $this->assertEquals($payload['colors'][$index], $color->value);
        }
    }
}

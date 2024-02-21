<?php

namespace Zendrop\Data\Tests\Unit;

use Zendrop\Data\Tests\DataProvider\PayloadDataProvider;
use Zendrop\Data\Tests\Example\Person;
use Zendrop\Data\Tests\TestCase;
use Zendrop\Data\ToArrayCase;

class ToArrayTraitTest extends TestCase
{
    public function testToArray()
    {
        $payload = PayloadDataProvider::getArray();

        $createdObject = Person::from($payload);

        $this->assertEquals(PayloadDataProvider::getStrictValuesArray(), $createdObject->toArray(ToArrayCase::Camel));
    }

    public function testToSnakeCasedArray()
    {
        $payload = PayloadDataProvider::getArray();

        $createdObject = Person::from($payload);

        $this->assertEquals(PayloadDataProvider::getStrictValuesArray(true), $createdObject->toArray());
    }
}

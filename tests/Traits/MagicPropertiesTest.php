<?php

namespace Dryspell\Tests\Traits;

use \Dryspell\Traits\MagicProperties;
use \PHPunit\Framework\TestCase;

/**
 * Tests for MagicProperties Trait
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class MagicPropertiesTest extends TestCase
{

    /**
     * Are properties from protected property "$property" returned?
     *
     * @test
     */
    public function testGetProperties()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);
        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $expected = ['foo' => ['type' => 'bar']];
        $properties->setValue($mock, $expected);
        $actual = $mock->getProperties();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is an exception thrown when setting undeclared properties?
     *
     * @test
     */
    public function testSetPropertyThrowsUndeclaredPropertyException()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);
        $this->expectException(\Dryspell\UndefinedPropertyException::class);
        $mock->foo = 'bar';
    }

    /**
     * Is an exception thrown when setting properties with an invalid type?
     *
     * @param string $type Normaly expected type.
     * @param mixed $value Given value, type must differ from $type.
     * @param string $givenType Real type of $value.
     * @test
     * @dataProvider dataProviderTestSetPropertyThrowsInvalidTypeException
     */
    public function testSetPropertyThrowsInvalidTypeException(string $type,
                                                              $value,
                                                              string $givenType = 'string')
    {
        $mock = $this->getMockForTrait(MagicProperties::class);

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo' => ['type' => $type]]);

        $this->expectException(\Dryspell\InvalidTypeException::class);
        $this->expectExceptionMessageRegExp('/::foo must be of type ' . preg_quote($type) . '.$/');
        $mock->foo = $value;
    }

    /**
     * Dataprovider for testSetPropertyThrowsInvalidTypeException.
     *
     * @return array
     */
    public function dataProviderTestSetPropertyThrowsInvalidTypeException()
    {
        return [
            ['bool', 'bar'], ['boolean', 'bar'],
            ['int', 'bar'], ['integer', 'bar'],
            ['float', 'bar'],
            ['string', 1, 'integer'],
            ['array', 'bar'],
            ['resource', 'bar'],
            ['callable', 'bar'],
            [\stdClass::class, 'bar'],
            [self::class, new \stdClass(), \stdClass::class],
        ];
    }

    /**
     * Is a value correctly set?
     *
     * @param string $type Expected type.
     * @param mixed $value Given value.
     * @test
     * @dataProvider dataProviderTestSetProperty
     */
    public function testSetProperty(string $type, $value)
    {
        $mock = $this->getMockForTrait(MagicProperties::class);

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo' => ['type' => $type]]);

        $mock->foo = $value;

        $values = new \ReflectionProperty($mock, 'values');
        $values->setAccessible(true);
        $actual = $values->getValue($mock);
        $expected = ['foo' => $value];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Dataprovider for testSetProperty.
     *
     * @return array
     */
    public function dataProviderTestSetProperty()
    {
        return [
            ['bool', true], ['boolean', false],
            ['int', 1], ['integer', 2],
            ['float', 1.5],
            ['string', 'bar'],
            ['array', []],
            ['resource', fopen('php://memory', 'r')],
            ['callable', function() {

                }],
            [\stdClass::class, new \stdClass()],
        ];
    }

    /**
     * Is the setter of the snake_case property called in camelCase?
     *
     * @test
     */
    public function testSetPropertyCallsSetter()
    {
        $mock = $this->getMockForTrait(MagicProperties::class, [], '', true,
            true, true, ['setFooBar']);
        $mock->expects($this->once())
            ->method('setFooBar')
            ->with('baz');

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo_bar' => ['type' => 'string']]);

        $mock->foo_bar = 'baz';
    }

    /**
     * Is an exception thrown when getting undeclared properties?
     *
     * @test
     */
    public function testGetPropertyThrowsUndeclaredPropertyException()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);
        $this->expectException(\Dryspell\UndefinedPropertyException::class);
        $actual = $mock->foo;
    }

    /**
     * Is a value correctly returned?
     *
     * @test
     */
    public function testGetProperty()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo' => ['type' => 'string']]);

        $values = new \ReflectionProperty($mock, 'values');
        $values->setAccessible(true);
        $values->setValue($mock, ['foo' => 'bar']);

        $actual = $mock->foo;
        $expected = 'bar';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is the getter of the snake_case property called in camelCase?
     *
     * @test
     */
    public function testGetPropertyCallsGetter()
    {
        $mock = $this->getMockForTrait(MagicProperties::class, [], '', true,
            true, true, ['getFooBar']);
        $mock->expects($this->once())
            ->method('getFooBar')
            ->will($this->returnValue('baz'));

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo_bar' => ['type' => 'string']]);

        $actual = $mock->foo_bar;
        $this->assertEquals('baz', $actual);
    }

    /**
     * Is isset probed correctly?
     *
     * @test
     */
    public function testIssetProperty()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo' => ['type' => 'string'], 'bar' => ['type' => 'string']]);

        $values = new \ReflectionProperty($mock, 'values');
        $values->setAccessible(true);
        $values->setValue($mock, ['foo' => 'bar']);

        $actual = isset($mock->foo);
        $expected = true;
        $this->assertEquals($expected, $actual);

        $actual = isset($mock->bar);
        $expected = false;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is a property correctly unset?
     *
     * @test
     */
    public function testUnsetProperty()
    {
        $mock = $this->getMockForTrait(MagicProperties::class);

        $properties = new \ReflectionProperty($mock, 'properties');
        $properties->setAccessible(true);
        $properties->setValue($mock, ['foo' => ['type' => 'string']]);

        $values = new \ReflectionProperty($mock, 'values');
        $values->setAccessible(true);
        $values->setValue($mock, ['foo' => 'bar']);

        unset($mock->foo);
        $actual = $values->getValue($mock);
        $expected = [];
        $this->assertEquals($expected, $actual);

        // Test that no error is thrown:
        unset($mock->bar);
    }
}
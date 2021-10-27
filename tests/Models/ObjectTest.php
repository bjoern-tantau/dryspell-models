<?php
namespace Dryspell\Tests\Models;

use DateTime;
use DateTimeZone;
use Dryspell\Models\BaseObject;
use Dryspell\Models\Options;
use PHPUnit\Framework\TestCase;

/**
 * Tests for base model object
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class ObjectTest extends TestCase
{

    /**
     * Are the default properties returned?
     *
     * @test
     */
    public function testGetProperties()
    {
        $object   = new ObjectTestClass();
        $actual   = $object->getProperties();
        $expected = [
            'id'         => new Options(id: true, generatedValue: true, signed: false, type: 'int'),
            'created_at' => new Options(type: '\\DateTime', default: 'now'),
            'updated_at' => new Options(type: '\\DateTime', default: 'now', onUpdate: 'now'),
            'child'      => new Options(type: '\\' . ObjectTestClass::class),
            'nullable'   => new Options(type: 'string', nullable: true),
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is the correct id property indicated?
     *
     * @test
     */
    public function testGetIdProperty()
    {
        $object   = $this->getMockForAbstractClass(BaseObject::class);
        $actual   = $object::getIdProperty();
        $expected = 'id';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are values correctly returned?
     *
     * @test
     */
    public function testGetValues()
    {
        $values = [
            'id'         => 1,
            'created_at' => new DateTime('2000-01-01'),
            'updated_at' => new DateTime('2000-01-01'),
        ];

        $object = $this->getMockForAbstractClass(BaseObject::class);
        $object->setValues($values);
        $actual = $object->getValues();
        $this->assertEquals($values, $actual);
    }

    /**
     * Are unset values left out?
     *
     * @test
     */
    public function testGetIncompleteValues()
    {
        $values = [
            'created_at' => new DateTime('2000-01-01'),
            'updated_at' => new DateTime('2000-01-01'),
        ];

        $object = $this->getMockForAbstractClass(BaseObject::class);
        $object->setValues($values);
        $actual = $object->getValues();
        $this->assertEquals($values, $actual);
    }

    /**
     * Are values correctly assigned to properties?
     *
     * @test
     */
    public function testSetValues()
    {
        $values = [
            'id'         => 1,
            'created_at' => new DateTime('2000-01-01'),
            'updated_at' => new DateTime('2000-01-01'),
        ];

        $object = $this->getMockForAbstractClass(BaseObject::class);
        $actual = $object->setValues($values);
        $this->assertEquals($object, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertInstanceOf(DateTime::class, $actual->created_at);
        $this->assertInstanceOf(DateTime::class, $actual->updated_at);
        $this->assertEquals('2000-01-01', $actual->created_at->format('Y-m-d'));
        $this->assertEquals('2000-01-01', $actual->updated_at->format('Y-m-d'));
    }

    /**
     * Can the object be serialized to json?
     *
     * @test
     */
    public function testJsonEncode()
    {
        $values = [
            'id'         => 1,
            'created_at' => new DateTime('2000-01-01'),
            'updated_at' => new DateTime('2000-01-01'),
        ];

        $object = $this->getMockForAbstractClass(BaseObject::class);
        $object->setValues($values);

        $actual   = json_encode($object);
        $expected = json_encode($values);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Can values be set weakly typed instead of strongly typed?
     *
     * @test
     */
    public function testSetWeaklyTyped()
    {
        $object = new ObjectTestClass();

        $object->setWeaklyTyped('created_at', '2000-01-01');
        $actual   = $object->created_at;
        $expected = new DateTime('2000-01-01');

        $this->assertEquals($expected, $actual);

        $object->setWeaklyTyped('child_id', 1);
        $actual = $object->child;
        $this->assertInstanceOf(ObjectTestClass::class, $actual);

        $object = $this->getMockForAbstractClass(BaseObject::class);

        $object->setWeaklyTyped('created_at', [
            'date'     => '2000-01-01',
            'timezone' => 'Europe/Berlin',
        ]);
        $actual   = $object->created_at;
        $expected = new DateTime('2000-01-01');
        $timezone = new DateTimeZone('Europe/Berlin');
        $expected->setTimezone($timezone);

        $this->assertEquals($expected, $actual);
    }
}

class ObjectTestClass extends BaseObject
{

    public ObjectTestClass $child;
    public ?string $nullable;

}

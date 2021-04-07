<?php
namespace Dryspell\Tests\Models;

use DateTime;
use DateTimeZone;
use Dryspell\Models\BackendInterface;
use Dryspell\Models\BaseObject;
use Generator;
use PHPunit\Framework\TestCase;

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
        $backend  = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object   = new ObjectTestClass($backend);
        $actual   = $object->getProperties();
        $expected = [
            'id'         => [
                'type'            => 'int',
                'id'              => true,
                'generated_value' => true,
                'unsigned'        => true,
                'required'        => false,
            ],
            'created_at' => [
                'type'     => '\\DateTime',
                'default'  => 'now',
                'required' => false,
            ],
            'updated_at' => [
                'type'      => '\\DateTime',
                'default'   => 'now',
                'on_update' => 'now',
                'required'  => false,
            ],
            'child'      => [
                'type'     => '\\' . ObjectTestClass::class,
                'required' => true,
            ],
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
        $backend  = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object   = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
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

        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $object->setValues($values);
        $actual  = $object->getValues();
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

        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $actual  = $object->setValues($values);
        $this->assertEquals($object, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertInstanceOf(DateTime::class, $actual->created_at);
        $this->assertInstanceOf(DateTime::class, $actual->updated_at);
        $this->assertEquals('2000-01-01', $actual->created_at->format('Y-m-d'));
        $this->assertEquals('2000-01-01', $actual->updated_at->format('Y-m-d'));
    }

    /**
     * Is the object saved using the backend?
     *
     * @test
     */
    public function testSave()
    {
        $backend  = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object   = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $backend->expects($this->once())
            ->method('save')
            ->with($object)
            ->will($this->returnSelf());
        $actual   = $object->save();
        $expected = $object;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are many objects returned by find?
     *
     * @test
     */
    public function testFind()
    {
        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $obj2    = clone $object;
        $obj3    = clone $object;
        $obj4    = clone $object;
        $backend->expects($this->once())
            ->method('find')
            ->with($object, ['id' => ['>' => 1]])
            ->will($this->returnValue([
                    $obj2->setValues([
                        'id'         => 2,
                        'created_at' => new DateTime('2000-01-01'),
                        'updated_at' => new DateTime('2000-01-01'),
                    ]),
                    $obj3->setValues([
                        'id'         => 3,
                        'created_at' => new DateTime('2000-01-01'),
                        'updated_at' => new DateTime('2000-01-01'),
                    ]),
                    $obj4->setValues([
                        'id'         => 4,
                        'created_at' => new DateTime('2000-01-01'),
                        'updated_at' => new DateTime('2000-01-01'),
                    ]),
        ]));
        $actual  = $object->find(['id' => ['>' => 1]]);
        $this->assertInstanceOf(Generator::class, $actual);
        $values  = [];
        foreach ($actual as $object) {
            $this->assertInstanceOf(BaseObject::class, $object);
            $this->assertInstanceOf(DateTime::class, $object->created_at);
            $this->assertInstanceOf(DateTime::class, $object->updated_at);
            $this->assertEquals('2000-01-01',
                $object->created_at->format('Y-m-d'));
            $this->assertEquals('2000-01-01',
                $object->updated_at->format('Y-m-d'));
            $values[] = $object;
        }
        $this->assertCount(3, $values);
        $this->assertEquals(2, $values[0]->id);
        $this->assertEquals(3, $values[1]->id);
        $this->assertEquals(4, $values[2]->id);
    }

    /**
     * Is the object loaded correctly?
     *
     * @test
     */
    public function testLoad()
    {
        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $backend->expects($this->once())
            ->method('find')
            ->with($object, ['id' => 1])
            ->will($this->returnValue([
                    [
                        'id'         => 1,
                        'created_at' => new DateTime('2000-01-01'),
                        'updated_at' => new DateTime('2000-01-01'),
                    ],
        ]));
        $actual  = $object->load(1);
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

        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
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
        $backend = $this->getMockBuilder(BackendInterface::class)->getMock();
        $object  = new ObjectTestClass($backend);

        $object->setWeaklyTyped('created_at', '2000-01-01');
        $actual   = $object->created_at;
        $expected = new DateTime('2000-01-01');

        $this->assertEquals($expected, $actual);

        $object->setWeaklyTyped('child_id', 1);
        $actual = $object->child;
        $this->assertInstanceOf(ObjectTestClass::class, $actual);

        $object = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);

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

    /**
     * Is the object deleted using the backend?
     *
     * @test
     */
    public function testDelete()
    {
        $backend  = $this->getMockBuilder(BackendInterface::class)->getMock();
        /** @var BaseObject $object */
        $object   = $this->getMockForAbstractClass(BaseObject::class,
            [$backend]);
        $backend->expects($this->once())
            ->method('delete')
            ->with($object)
            ->will($this->returnSelf());
        $actual   = $object->delete();
        $expected = $object;
        $this->assertEquals($expected, $actual);
    }
}

/**
 * @property ObjectTestClass $child
 */
class ObjectTestClass extends BaseObject
{

}

<?php

namespace Tantau\Tests\Models;

use \PHPunit\Framework\TestCase;

/**
 * Tests for base model object
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern.tantau@limora.com>
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
        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $actual = $object->getProperties();
        $expected = [
            'id'         => [
                'type'    => 'int',
                'options' => [
                    'autoincrement' => true,
                    'signed'        => false,
                ],
            ],
            'created_at' => [
                'type'    => '\\DateTime',
                'options' => [
                    'default' => 'NOW()',
                ],
            ],
            'updated_at' => [
                'type'    => '\\DateTime',
                'options' => [
                    'default'   => 'NOW()',
                    'on_update' => 'NOW()',
                ],
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
        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $actual = $object->getIdProperty();
        $expected = 'id';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are values correctl assigned to properties?
     *
     * @test
     */
    public function testSetValues()
    {
        $values = [
            'id'         => 1,
            'created_at' => new \DateTime('2000-01-01'),
            'updated_at' => new \DateTime('2000-01-01'),
        ];

        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $actual = $object->setValues($values);
        $this->assertEquals($object, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertInstanceOf(\DateTime::class, $actual->created_at);
        $this->assertInstanceOf(\DateTime::class, $actual->updated_at);
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
        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $backend->expects($this->once())
                ->method('save')
                ->with($object)
                ->will($this->returnSelf());
        $actual = $object->save();
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
        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $backend->expects($this->once())
                ->method('find')
                ->with($object, ['id' => ['>' => 1]])
                ->will($this->returnValue([
                            [
                                'id'         => 2,
                                'created_at' => new \DateTime('2000-01-01'),
                                'updated_at' => new \DateTime('2000-01-01'),
                            ],
                            [
                                'id'         => 3,
                                'created_at' => new \DateTime('2000-01-01'),
                                'updated_at' => new \DateTime('2000-01-01'),
                            ],
                            [
                                'id'         => 4,
                                'created_at' => new \DateTime('2000-01-01'),
                                'updated_at' => new \DateTime('2000-01-01'),
                            ],
        ]));
        $actual = $object->find(['id' => ['>' => 1]]);
        $this->assertInstanceOf(\Generator::class, $actual);
        $values = [];
        foreach ($actual as $object) {
            $this->assertInstanceOf(\Tantau\Models\Object::class, $object);
            $this->assertInstanceOf(\DateTime::class, $object->created_at);
            $this->assertInstanceOf(\DateTime::class, $object->updated_at);
            $this->assertEquals('2000-01-01', $object->created_at->format('Y-m-d'));
            $this->assertEquals('2000-01-01', $object->updated_at->format('Y-m-d'));
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
        $backend = $this->getMockBuilder(\Tantau\Models\BackendInterface::class)->getMock();
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $backend->expects($this->once())
                ->method('find')
                ->with($object, ['id' => 1])
                ->will($this->returnValue([
                            [
                                'id'         => 1,
                                'created_at' => new \DateTime('2000-01-01'),
                                'updated_at' => new \DateTime('2000-01-01'),
                            ],
        ]));
        $actual = $object->load(1);
        $this->assertEquals($object, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertInstanceOf(\DateTime::class, $actual->created_at);
        $this->assertInstanceOf(\DateTime::class, $actual->updated_at);
        $this->assertEquals('2000-01-01', $actual->created_at->format('Y-m-d'));
        $this->assertEquals('2000-01-01', $actual->updated_at->format('Y-m-d'));
    }

}

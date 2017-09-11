<?php

namespace Tantau\Tests\Models\Backends;

use \PHPunit\Framework\TestCase;

/**
 * Tests for doctrine based backend model
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
class DoctrineTest extends TestCase
{

    /**
     * Are the needed tables for an object setup correctly?
     * 
     * @test
     */
    public function testSetup()
    {
        $table = $this->createMock(\Doctrine\DBAL\Schema\Table::class);
        $table->expects($this->at(0))
                ->method('addColumn')
                ->with('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->expects($this->at(1))
                ->method('addColumn')
                ->with('active', 'boolean', []);
        $table->expects($this->at(2))
                ->method('addColumn')
                ->with('number_float', 'float', []);
        $table->expects($this->at(3))
                ->method('addColumn')
                ->with('number_decimal', 'decimal', []);
        $table->expects($this->at(4))
                ->method('addColumn')
                ->with('short_text', 'string', ['length' => 60]);
        $table->expects($this->at(5))
                ->method('addColumn')
                ->with('long_text', 'text', ['length' => 4000]);
        $table->expects($this->at(6))
                ->method('addColumn')
                ->with('data', 'array', []);
        $table->expects($this->at(7))
                ->method('addColumn')
                ->with('myself_id', 'integer', ['unsigned' => true]);
        $table->expects($this->at(8))
                ->method('addColumn')
                ->with('created_at', 'datetimetz', []);
        $table->expects($this->at(9))
                ->method('addColumn')
                ->with('updated_at', 'datetimetz', []);
        $schema = $this->createMock(\Doctrine\DBAL\Schema\Schema::class);
        $schema->expects($this->once())
                ->method('createTable')
                ->with('object')
                ->will($this->returnValue($table));
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
                ->method('createSchema')
                ->will($this->returnValue($schema));
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $driver->expects($this->once())
                ->method('getSchemaManager')
                ->will($this->returnValue($schemaManager));
        $config = [
            'driverClass' => get_class($driver),
        ];
        $backend = new \Tantau\Models\Backends\Doctrine($config);
        $object = $this->createMock(\Tantau\Models\Object::class);
        $object->expects($this->once())
                ->method('getProperties')
                ->will($this->returnValue([
                            'id'             => 'int',
                            'active'         => 'bool',
                            'number_float'   => 'float',
                            'number_decimal' => 'string',
                            'short_text'     => 'string',
                            'long_text'      => 'string',
                            'data'           => 'array',
                            'myself'         => \Tantau\Models\Object::class,
                            'created_at'     => '\\DateTime',
                            'updated_at'     => '\\DateTime',
        ]));
        $object->expects($this->once())
                ->method('getConstraints')
                ->will($this->returnValue([
                            'short_text' => [
                                'max' => 60,
                            ],
                            'long_text'  => [
                                'max' => 4000,
                            ],
        ]));
        $actual = $backend->setup($object);
        $this->assertEquals($backend, $actual);
    }

    /**
     * Is data saved correctly?
     * 
     * @test
     */
    public function testSave()
    {
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $config = [
            'driverClass' => get_class($driver),
        ];
        $backend = new \Tantau\Models\Backends\Doctrine($config);
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $backend->save($object);
        $this->markTestIncomplete();
    }

    /**
     * Is data found correctly?
     * 
     * @test
     */
    public function testFind()
    {
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $config = [
            'driverClass' => get_class($driver),
        ];
        $backend = new \Tantau\Models\Backends\Doctrine($config);
        $object = $this->getMockForAbstractClass(\Tantau\Models\Object::class, [$backend]);
        $term = 1;
        $actual = $backend->find($object, $term);
        $this->markTestIncomplete();
    }

}

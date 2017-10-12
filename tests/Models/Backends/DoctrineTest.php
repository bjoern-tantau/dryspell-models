<?php

namespace Tantau\Tests\Models\Backends;

use Classgen\Stub\ClassStub;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use PHPunit\Framework\TestCase;
use RtLopez\Decimal;
use Tantau\Models\Backends\Doctrine;
use Tantau\Models\Object;

/**
 * Tests for doctrine based backend model
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class DoctrineTest extends TestCase
{

    /**
     * Is the migration class for the first migration created correctly?
     *
     * @test
     */
    public function testCreateFirstMigration()
    {
        $object = $this->createMock(Object::class);
        $object->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id' => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
        ]));
        $schema = new \Doctrine\DBAL\Schema\Schema();
        $schema_manager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schema_manager->expects($this->once())
            ->method('createSchema')
            ->will($this->returnValue($schema));
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($schema_manager));
        $backend = new Doctrine($conn);
        $class = new ClassStub('Version1');
        $actual = $backend->createMigration($object, $class);
        $this->assertEquals($class, $actual);

        $actual = $class->getMethod('up')->getCodeStub()->toLines();
        $expected = [
            '$table = $schema->createTable(\'' . snake_case(get_class($object)) . '\');',
            '',
            '$table->addColumn(\'id\', \'integer\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'id',
                'type'             => Type::getType(Type::INTEGER),
                'default'          => null,
                'notnull'          => true,
                'length'           => null,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => true,
                'autoincrement'    => true,
                'columnDefinition' => null,
                'comment'          => null,
                ]), "\'\\") . '\'));',
            '',
            '$table->setPrimaryKey(unserialize(\'' . serialize(['id']) . '\'));',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is the migration class for a changed migration created correctly?
     *
     * @test
     */
    public function testCreateNextMigration()
    {
        $object = $this->createMock(Object::class);
        $object->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'  => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo' => [
                        'type' => 'string',
                    ],
        ]));
        $schema = new \Doctrine\DBAL\Schema\Schema();
        $table = $schema->createTable(snake_case(get_class($object)));
        $table->addColumn('id', 'integer',
            ['unsigned' => true, 'autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $schema_manager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schema_manager->expects($this->once())
            ->method('createSchema')
            ->will($this->returnValue($schema));
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($schema_manager));
        $backend = new Doctrine($conn);
        $class = new ClassStub('Version1');
        $actual = $backend->createMigration($object, $class);
        $this->assertEquals($class, $actual);

        $actual = $class->getMethod('up')->getCodeStub()->toLines();
        $expected = [
            '$table = $schema->getTable(\'' . snake_case(get_class($object)) . '\');',
            '',
            '$table->addColumn(\'foo\', \'string\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'foo',
                'type'             => Type::getType(Type::STRING),
                'default'          => null,
                'notnull'          => true,
                'length'           => null,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => false,
                'autoincrement'    => false,
                'columnDefinition' => null,
                'comment'          => null,
                ]), "\'\\") . '\'));',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Is data of new objects saved correctly?
     *
     * @test
     */
    public function testSaveNew()
    {
        $conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend = new Doctrine($conn);
        $object = $this->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProperties'])
            ->getMock();
        $object->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'  => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo' => [
                        'type' => 'string',
                    ],
        ]));
        $object->foo = 'bar';

        $conn->expects($this->once())
            ->method('insert')
            ->with(snake_case(get_class($object)),
                ['foo' => 'bar', 'id' => null]);
        $conn->expects($this->once())
            ->method('lastInsertId')
            ->will($this->returnValue(1));

        $backend->save($object);
        $this->assertEquals(1, $object->id);
    }

    /**
     * Is data of existing objects saved correctly?
     *
     * @test
     */
    public function testSaveExisting()
    {
        $conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'update', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend = new Doctrine($conn);
        $object = $this->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProperties'])
            ->getMock();
        $object->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'  => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo' => [
                        'type' => 'string',
                    ],
        ]));
        $object->id = 1;
        $object->foo = 'bar';

        $stmt = $this->createMock(Driver\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $query_builder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
        $query_builder->expects($this->once())
            ->method('select')
            ->with('id')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('from')
            ->with(snake_case(get_class($object)))
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('where')
            ->with('id = :id')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('setParameter')
            ->with('id', 1)
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($stmt));

        $conn->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($query_builder));
        $conn->expects($this->once())
            ->method('update')
            ->with(snake_case(get_class($object)), ['foo' => 'bar', 'id' => 1],
                ['id' => 1]);

        $backend->save($object);
        $this->assertEquals(1, $object->id);
    }

    /**
     * Is an exception thrown when trying to update a deleted object?
     *
     * @test
     * @expectedException \Tantau\Models\Backends\Exception
     * @expectedExceptionCode 1
     */
    public function testSaveSeeminglyExisting()
    {
        $conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend = new Doctrine($conn);
        $object = $this->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProperties'])
            ->getMock();
        $object->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'  => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo' => [
                        'type' => 'string',
                    ],
        ]));
        $object->id = 1;
        $object->foo = 'bar';

        $stmt = $this->createMock(Driver\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(false));

        $query_builder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
        $query_builder->expects($this->once())
            ->method('select')
            ->with('id')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('from')
            ->with(snake_case(get_class($object)))
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('where')
            ->with('id = :id')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('setParameter')
            ->with('id', 1)
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($stmt));

        $conn->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($query_builder));

        $backend->save($object);
    }

    /**
     * Is data found correctly?
     * 
     * @test
     */
    public function testFind()
    {
        $conn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack', 'quoteIdentifier'])
            ->getMock();
        $backend = new Doctrine($conn);
        $object = $this->getMockBuilder(Object::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProperties'])
            ->getMock();
        $object->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'  => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo' => [
                        'type' => 'string',
                    ],
        ]));

        $stmt = $this->createMock(Driver\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(['id' => 1, 'foo' => 'bar']);

        $query_builder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
        $query_builder->expects($this->once())
            ->method('select')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('from')
            ->with(snake_case(get_class($object)))
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('andWhere')
            ->with('`id` = ?')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('setParameters')
            ->with([0 => 1])
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($stmt));

        $conn->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($query_builder));
        $conn->expects($this->once())
            ->method('quoteIdentifier')
            ->with('id')
            ->will($this->returnValue('`id`'));

        $term = 1;
        $actual = $backend->find($object, $term);
        /* @var $actual \Geanerator */
        $this->assertInstanceOf(\Generator::class, $actual);
        $actual = $actual->current();
        $this->assertInstanceOf(Object::class, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertEquals('bar', $actual->foo);
    }
}
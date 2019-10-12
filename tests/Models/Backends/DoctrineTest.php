<?php
namespace Dryspell\Tests\Models\Backends;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Types\Type;
use Dryspell\Models\Backends\Doctrine;
use Dryspell\Models\Backends\Exception;
use Dryspell\Models\BaseObject;
use Generator;
use PDO;
use PHPunit\Framework\TestCase;
use function snake_case;

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
     * Is data of new objects saved correctly?
     *
     * @test
     */
    public function testSaveNew()
    {
        $conn        = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend     = new Doctrine($conn);
        $object      = $this->getMockBuilder(BaseObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProperties'])
            ->getMock();
        $object->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'         => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo'        => [
                        'type' => 'string',
                    ],
                    'created_at' => [
                        'type' => \DateTime::class,
                    ],
        ]));
        $object->foo = 'bar';
        $object->created_at = new \DateTime('2000-01-01');

        $conn->expects($this->once())
            ->method('insert')
            ->with(snake_case(get_class($object)), ['foo' => 'bar', 'created_at' => '2000-01-01 00:00:00']);
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
        $conn        = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'update', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend     = new Doctrine($conn);
        $object      = $this->getMockBuilder(BaseObject::class)
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
        $object->id  = 1;
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
     * @expectedException Exception
     * @expectedExceptionCode 1
     */
    public function testSaveSeeminglyExisting()
    {
        $conn        = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack'])
            ->getMock();
        $backend     = new Doctrine($conn);
        $object      = $this->getMockBuilder(BaseObject::class)
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
        $object->id  = 1;
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
        $conn    = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'insert', 'lastInsertId', 'beginTransaction',
                'commit', 'rollBack', 'quoteIdentifier'])
            ->getMock();
        $backend = new Doctrine($conn);
        $object  = $this->getMockBuilder(BaseObject::class)
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
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 1, 'foo' => 'bar']);

        $query_builder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
        $query_builder->expects($this->once())
            ->method('select')
            ->with('*')
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('from')
            ->with(snake_case(get_class($object)))
            ->will($this->returnSelf());
        $query_builder->expects($this->once())
            ->method('andWhere')
            ->with('`id` LIKE ?')
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

        $term   = 1;
        $actual = $backend->find($object, $term);
        /* @var $actual \Geanerator */
        $this->assertInstanceOf(Generator::class, $actual);
        $actual = $actual->current();
        $this->assertInstanceOf(BaseObject::class, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertEquals('bar', $actual->foo);
    }
}

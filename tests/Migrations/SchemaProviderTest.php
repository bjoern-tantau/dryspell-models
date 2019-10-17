<?php
namespace Doctrine\DBAL\Migrations\Tests\Provider;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Schema\Schema;
use Dryspell\Migrations\SchemaProvider;
use Dryspell\Models\BaseObject;
use function snake_case;

/**
 * Tests the dryspell schema provider
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class OrmSchemaProviderTest extends MigrationTestCase
{

    /**
     * Is the schema created correctly?
     *
     * @test
     */
    public function testCreateSchema()
    {
        $object = $this->createMock(BaseObject::class);
        $object->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue([
                    'id'               => [
                        'type'            => 'int',
                        'id'              => true,
                        'generated_value' => true,
                        'unsigned'        => true,
                    ],
                    'foo'              => [
                        'type'     => 'string',
                        'required' => false,
                    ],
                    'foreign'          => [
                        'type'     => SchemaProviederTestClassChild::class,
                        'required' => false,
                    ],
                    'foreign_required' => [
                        'type'     => SchemaProviederTestClassChild::class,
                        'required' => true,
                    ],
        ]));

        $provider = new SchemaProvider([$object]);

        $to_schema = $provider->createSchema();
        $this->assertInstanceOf(Schema::class, $to_schema);
        $this->assertTrue($to_schema->hasTable(snake_case(get_class($object))));
        $table     = $to_schema->getTable(snake_case(get_class($object)));
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('foo'));
        $this->assertTrue($table->hasColumn('foreign_id'));
        $this->assertTrue($table->hasColumn('foreign_required_id'));
        $this->assertTrue($table->getColumn('id')->getNotnull());
        $this->assertFalse($table->getColumn('foo')->getNotnull());
        $this->assertFalse($table->getColumn('foreign_id')->getNotnull());
        $this->assertTrue($table->getColumn('foreign_required_id')->getNotnull());
        $this->assertEquals('SET NULL', array_values($table->getForeignKeys())[0]->onDelete());
        $this->assertEquals('CASCADE', array_values($table->getForeignKeys())[0]->onUpdate());
        $this->assertEquals('CASCADE', array_values($table->getForeignKeys())[1]->onDelete());
        $this->assertEquals('CASCADE', array_values($table->getForeignKeys())[1]->onUpdate());
    }
}

/**
 * Description of SchemaProviederTestClassChild
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class SchemaProviederTestClassChild extends BaseObject
{

}

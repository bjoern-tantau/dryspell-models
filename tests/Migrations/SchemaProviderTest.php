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

        $provider = new SchemaProvider([$object]);

        $to_schema = $provider->createSchema();
        self::assertInstanceOf(Schema::class, $to_schema);
        self::assertTrue($to_schema->hasTable(snake_case(get_class($object))));
        $table = $to_schema->getTable(snake_case(get_class($object)));
        self::assertTrue($table->hasColumn('id'));
        self::assertTrue($table->hasColumn('foo'));
    }
}

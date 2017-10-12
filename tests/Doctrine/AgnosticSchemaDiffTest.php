<?php

namespace Tantau\Tests\Doctrine;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Tantau\Doctrine\AgnosticSchemaDiff;

/**
 * Tests for base model object
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class AgnosticSchemaDiffTest extends TestCase
{

    /**
     * Are orphaned foreign keys removed correctly?
     *
     * @test
     */
    public function testGetUpCommandsWithOrphanedForeignKeys()
    {
        $diff = new SchemaDiff();
        $table = new Table('local_table');
        $constraint = new ForeignKeyConstraint(['foo', 'bar'],
            'foreignTableName', ['foreign_foo', 'foreign_bar'],
            'awesome_foreign_key',
            ['onUpdate' => 'CASCADE', 'onDelete' => 'SETNULL']);
        $constraint->setLocalTable($table);
        $diff->orphanedForeignKeys[] = $constraint;

        $a_diff = new AgnosticSchemaDiff($diff);
        $actual = $a_diff->getUpCommands();
        $expected = [
            '$table = $schema->getTable(\'local_table\');',
            '$table->removeForeignKey(\'awesome_foreign_key\');',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are new tables created correctly?
     *
     * @test
     */
    public function testGetUpCommandsWithNewTables()
    {
        $diff = new SchemaDiff();
        $table = new Table('local_table');
        $table->addOption('foo', 'bar');
        $table->addColumn('id', 'integer',
            ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('foo', 'string', ['default' => 'bar', 'length' => 255]);
        $table->addColumn('foreign_id', 'integer',
            ['autoincrement' => true, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['foo'], 'foo_index', [], []);
        $table->addForeignKeyConstraint('foreign_table', ['foreign_id'], ['id'],
            [], 'foreign_key');
        $diff->newTables[] = $table;

        $a_diff = new AgnosticSchemaDiff($diff);
        $actual = $a_diff->getUpCommands();
        $expected = [
            '$table = $schema->createTable(\'local_table\');',
            '$table->addOption(\'foo\', \'bar\');',
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
            '$table->addColumn(\'foreign_id\', \'integer\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'foreign_id',
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
            '$table->addColumn(\'foo\', \'string\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'foo',
                'type'             => Type::getType(Type::STRING),
                'default'          => 'bar',
                'notnull'          => true,
                'length'           => 255,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => false,
                'autoincrement'    => false,
                'columnDefinition' => null,
                'comment'          => null,
                ]), "\'\\") . '\'));',
            '$table->setPrimaryKey(unserialize(\'' . serialize(['id']) . '\'));',
            '$table->addIndex(unserialize(\'' . serialize(['foo']) . '\'), \'foo_index\', unserialize(\'' . serialize([
            ]) . '\'), unserialize(\'' . serialize([]) . '\'));',
            '$table->addIndex(unserialize(\'' . serialize(['foreign_id']) . '\'), \'IDX_E9CF1342CD42CE46\', unserialize(\'' . serialize([
            ]) . '\'), unserialize(\'' . serialize([]) . '\'));',
            '$table->addForeignKeyConstraint(\'foreign_table\', unserialize(\'' . serialize([
                'foreign_id']) . '\'), unserialize(\'' . serialize(['id']) . '\'), unserialize(\'' . serialize([
            ]) . '\'), \'foreign_key\');',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are tables removed correctly?
     *
     * @test
     */
    public function testGetUpCommandsWithRemovedTables()
    {
        $diff = new SchemaDiff();
        $table = new Table('local_table');
        $diff->removedTables[] = $table;

        $a_diff = new AgnosticSchemaDiff($diff);
        $actual = $a_diff->getUpCommands();
        $expected = [
            'throw new \\Tantau\\Migrations\\Exception(\'Dropping a table will lead to data loss. Migrate your data and remove this exception.\');',
            '$schema->dropTable(\'local_table\');',
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Are tables changed correctly?
     *
     * @test
     */
    public function testGetUpCommandsWithChangedTables()
    {
        $diff = new SchemaDiff();
        $table_diff = new TableDiff('table');
        $table_diff->newName = 'new_table';
        $table_diff->addedColumns = [new Column('bar', Type::getType(Type::STRING),
                ['default' => 'bar', 'length' => 255])];
        $changed_column = new ColumnDiff('foo',
            new Column('foo', Type::getType(Type::STRING), ['length' => 100]));
        $changed_column->changedProperties = ['length'];
        $table_diff->changedColumns = [$changed_column];
        $table_diff->removedColumns = [new Column('foobar',
                Type::getType(Type::STRING))];
        $table_diff->renamedColumns = ['foo' => new Column('baz',
                Type::getType(Type::STRING))];
        $table_diff->addedIndexes = [new Index('bar', ['bar'])];
        $table_diff->changedIndexes = [new Index('bar', ['bar'])];
        $table_diff->removedIndexes = [new Index('foo', ['foo'])];
        $table_diff->renamedIndexes = ['baz' => new Index('foobar', ['foobar'])];
        $table_diff->addedForeignKeys = [new ForeignKeyConstraint(['foreign_id'],
                'foreign_table', ['id'], 'foreign_key')];
        $table_diff->changedForeignKeys = [new ForeignKeyConstraint(['foreign_id'],
                'foreign_table', ['id'], 'foreign_key')];
        $table_diff->removedForeignKeys = [new ForeignKeyConstraint(['foreign_id'],
                'foreign_table', ['id'], 'foreign_key')];
        $diff->changedTables[] = $table_diff;

        $a_diff = new AgnosticSchemaDiff($diff);
        $actual = $a_diff->getUpCommands();
        $expected = [
            '$table = $schema->getTable(\'table\');',
            'throw new \\Tantau\\Migrations\\Exception(\'Renaming a table will probably lead to data loss. Use your database engine\\\'s rename query and remove this exception.\');',
            '$schema->renameTable(\'table\', \'new_table\');',
            '$table = $schema->getTable(\'new_table\');',
            '$table->addColumn(\'bar\', \'string\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'bar',
                'type'             => Type::getType(Type::STRING),
                'default'          => 'bar',
                'notnull'          => true,
                'length'           => 255,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => false,
                'autoincrement'    => false,
                'columnDefinition' => null,
                'comment'          => null,
                ]), "\'\\") . '\'));',
            'throw new \\Tantau\\Migrations\\Exception(\'Dropping a column will lead to data loss. Migrate your data and remove this exception.\');',
            '$table->dropColumn(\'foobar\');',
            'throw new \\Tantau\\Migrations\\Exception(\'Changing a column may lead to data loss. Check your changes and remove this exception.\');',
            '$table->changeColumn(\'foo\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'foo',
                'type'             => Type::getType(Type::STRING),
                'default'          => null,
                'notnull'          => true,
                'length'           => 100,
                'precision'        => 10,
                'scale'            => 0,
                'fixed'            => false,
                'unsigned'         => false,
                'autoincrement'    => false,
                'columnDefinition' => null,
                'comment'          => null,
                ]), "\'\\") . '\'));',
            'throw new \\Tantau\\Migrations\\Exception(\'Renaming a column may lead to data loss. Migrate your data and remove this exception.\');',
            '$table->changeColumn(\'foo\', unserialize(\'' . addcslashes(serialize([
                'name'             => 'baz',
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
            '$table->addIndex(unserialize(\'' . serialize(['bar']) . '\'), \'bar\', unserialize(\'' . serialize([
            ]) . '\'), unserialize(\'' . serialize([]) . '\'));',
            '$table->dropIndex(\'bar\');',
            '$table->addIndex(unserialize(\'' . serialize(['bar']) . '\'), \'bar\', unserialize(\'' . serialize([
            ]) . '\'), unserialize(\'' . serialize([]) . '\'));',
            '$table->dropIndex(\'foo\');',
            '$table->renameIndex(\'baz\', \'foobar\');',
            '$table->addForeignKeyConstraint(\'foreign_table\', unserialize(\'' . serialize([
                'foreign_id']) . '\'), unserialize(\'' . serialize(['id']) . '\'), unserialize(\'' . serialize([
            ]) . '\'), \'foreign_key\');',
            '$table->removeForeignKey(\'foreign_key\');',
            '$table->addForeignKeyConstraint(\'foreign_table\', unserialize(\'' . serialize([
                'foreign_id']) . '\'), unserialize(\'' . serialize(['id']) . '\'), unserialize(\'' . serialize([
            ]) . '\'), \'foreign_key\');',
            '$table->removeForeignKey(\'foreign_key\');',
        ];
        $this->assertEquals($expected, $actual);
    }
}
<?php

namespace Dryspell\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Dryspell\Migrations\Exception;
use Dryspell\Migrations\GeneratorHelperInterface;

/**
 * Get the commands to create a database agnostic schema migration
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class AgnosticSchemaDiff implements GeneratorHelperInterface
{
    /**
     *
     * @var SchemaDiff
     */
    private $diff;

    public function __construct(SchemaDiff $diff)
    {
        $this->diff = $diff;
    }

    public function getUpParameters(): array
    {
        return [
            [
                'name' => 'schema',
                'type' => Schema::class,
            ],
        ];
    }

    /**
     * Get commands to run up migration.
     * 
     * @return array
     */
    public function getUpCommands(): array
    {
        /* @var $schema Schema */
        $commands = [];

        foreach ($this->diff->newNamespaces as $namespace) {
            $commands[] = $this->call('$schema->createNamespace', [$namespace]);
        }

        foreach ($this->diff->orphanedForeignKeys as $orphanedForeignKey) {
            $commands[] = $this->call('$table = $schema->getTable',
                [$orphanedForeignKey->getLocalTable()->getName()]);
            $commands[] = $this->call('$table->removeForeignKey',
                [$orphanedForeignKey->getName()]);
        }

        foreach ($this->diff->newTables as $table) {
            $commands = array_merge(
                $commands, $this->getCreateTableCommands($table)
            );
        }

        foreach ($this->diff->removedTables as $table) {
            $commands[] = $this->call('throw new \\' . Exception::class,
                ['Dropping a table will lead to data loss. Migrate your data and remove this exception.']);
            $commands[] = $this->call('$schema->dropTable', [$table->getName()]);
        }

        foreach ($this->diff->changedTables as $tableDiff) {
            $commands = array_merge($commands,
                $this->getAlterTableCommands($tableDiff));
        }

        foreach ($this->diff->newSequences as $sequence) {
            $commands[] = $this->call('$schema->createSequence', [$sequence->getName(), $sequence->getAllocationSize(), $sequence->getInitialValue()]);
        }

        foreach ($this->diff->changedSequences as $sequence) {
            $commands[] = $this->call('$sequence = $schema->getSequence', [$sequence->getName()]);
            $commands[] = $this->call('$sequence->setAllocationSize', [$sequence->getAllocationSize()]);
            $commands[] = $this->call('$sequence->setInitialValue', [$sequence->getInitialValue()]);
        }

        foreach ($this->diff->removedSequences as $sequence) {
            $commands[] = $this->call('$schema->dropSequence', [$sequence->getName()]);
        }

        return $commands;
    }

    /**
     * Get commands to create a table
     *
     * @param Table $table
     * @return array
     */
    private function getCreateTableCommands(Table $table): array
    {
        /* @var $schema Schema */
        $commands = [];
        $commands[] = $this->call('$table = $schema->createTable',
            [$table->getName()]);
        foreach ($table->getOptions() as $name => $option) {
            $commands[] = $this->call('$table->addOption', [$name, $option]);
        }
        foreach ($table->getColumns() as $column) {
            $commands[] = $this->call('$table->addColumn',
                [$column->getName(), $column->getType()->getName(), $column->toArray()]);
        }
        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                $commands[] = $this->call('$table->setPrimaryKey',
                    [$table->getPrimaryKeyColumns()]);
            } else if (!$table->hasForeignKey($index->getName())) {
                $commands[] = $this->call('$table->addIndex',
                    [$index->getColumns(), $index->getName(), $index->getFlags(),
                    $index->getOptions()]);
            }
        }
        foreach ($table->getForeignKeys() as $foreign_key) {
            $commands[] = $this->call('$table->addForeignKeyConstraint',
                [$foreign_key->getForeignTableName(),
                $foreign_key->getLocalColumns(),
                $foreign_key->getForeignColumns(), $foreign_key->getOptions(),
                $foreign_key->getName()]);
        }
        return $commands;
    }

    /**
     * Get commands to alter a table
     * 
     * @param TableDiff $diff
     * @return array
     */
    private function getAlterTableCommands(TableDiff $diff): array
    {
        /* @var $schema Schema */
        $commands = [];
        $commands[] = $this->call('$table = $schema->getTable', [$diff->name]);

        if ($diff->newName !== false) {
            $commands[] = $this->call('throw new \\' . Exception::class,
                ['Renaming a table will probably lead to data loss. Use your database engine\'s rename query and remove this exception.']);
            $commands[] = $this->call('$schema->renameTable',
                [$diff->name, $diff->newName]);
            $commands[] = $this->call('$table = $schema->getTable',
                [$diff->newName]);
        }

        foreach ($diff->addedColumns as $column) {
            $commands[] = $this->call('$table->addColumn',
                [$column->getName(), $column->getType()->getName(), $column->toArray()]);
        }

        foreach ($diff->removedColumns as $column) {
            $commands[] = $this->call('throw new \\' . Exception::class,
                ['Dropping a column will lead to data loss. Migrate your data and remove this exception.']);
            $commands[] = $this->call('$table->dropColumn', [$column->getName()]);
        }

        foreach ($diff->changedColumns as $column_diff) {
            $commands[] = $this->call('throw new \\' . Exception::class,
                ['Changing a column may lead to data loss. Check your changes and remove this exception.']);
            $commands[] = $this->call('$table->changeColumn', [$column_diff->oldColumnName, $column_diff->column->toArray()]);
        }

        foreach ($diff->renamedColumns as $old_column_name => $column) {
            $commands[] = $this->call('throw new \\' . Exception::class,
                ['Renaming a column may lead to data loss. Migrate your data and remove this exception.']);
            $commands[] = $this->call('$table->changeColumn', [$old_column_name, $column->toArray()]);
        }

        foreach ($diff->addedIndexes as $index) {
            $commands[] = $this->call('$table->addIndex', [$index->getColumns(), $index->getName(), $index->getFlags(), $index->getOptions()]);
        }

        foreach ($diff->changedIndexes as $index) {
            $commands[] = $this->call('$table->dropIndex', [$index->getName()]);
            $commands[] = $this->call('$table->addIndex', [$index->getColumns(), $index->getName(), $index->getFlags(), $index->getOptions()]);
        }

        foreach ($diff->removedIndexes as $index) {
            $commands[] = $this->call('$table->dropIndex', [$index->getName()]);
        }

        foreach ($diff->renamedIndexes as $old_index_name => $index) {
            $commands[] = $this->call('$table->renameIndex', [$old_index_name, $index->getName()]);
        }

        foreach ($diff->addedForeignKeys as $foreign_key) {
            $commands[] = $this->call('$table->addForeignKeyConstraint', [$foreign_key->getForeignTableName(), $foreign_key->getLocalColumns(), $foreign_key->getForeignColumns(), $foreign_key->getOptions(), $foreign_key->getName()]);
        }

        foreach ($diff->changedForeignKeys as $foreign_key) {
            $commands[] = $this->call('$table->removeForeignKey', [$foreign_key->getName()]);
            $commands[] = $this->call('$table->addForeignKeyConstraint', [$foreign_key->getForeignTableName(), $foreign_key->getLocalColumns(), $foreign_key->getForeignColumns(), $foreign_key->getOptions(), $foreign_key->getName()]);
        }

        foreach ($diff->removedForeignKeys as $foreign_key) {
            $commands[] = $this->call('$table->removeForeignKey', [$foreign_key->getName()]);
        }

        if (isset($diff->addedIndexes['primary'])) {
            $keyColumns = array_unique(array_values($diff->addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
            unset($diff->addedIndexes['primary']);
        }
        return $commands;
    }

    public function getDownParameters(): array
    {
        return [
            [
                'name' => 'schema',
                'type' => Schema::class,
            ],
        ];
    }

    public function getDownCommands(): array
    {
        return [];
    }

    /**
     * Build an evalable string to call a method
     *
     * @param string $method
     * @param array $parameters
     * @return string
     */
    private function call($method, array $parameters = []): string
    {
        $out = $method;
        $out .= '(';
        $parameters = array_map([$this, 'serialize'], $parameters);
        $out .= join(', ', $parameters);
        $out .= ');';
        return $out;
    }

    /**
     * Serialize the input so that it can be output into an evalable string
     *
     * @param mixed $value
     * @return string
     */
    private function serialize($value): string
    {
        if (is_string($value)) {
            return "'" . addcslashes($value, "\'\\") . "'";
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return 'unserialize(\'' . addcslashes(serialize($value), "\'\\") . '\')';
    }
}
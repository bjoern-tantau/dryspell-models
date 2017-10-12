<?php

namespace Dryspell\Models\Backends;

use Classgen\Stub\ClassStub;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use ReflectionClass;
use RtLopez\Decimal;
use Dryspell\Doctrine\AgnosticSchemaDiff;
use Dryspell\InvalidTypeException;
use Dryspell\Models\BackendInterface;
use Dryspell\Models\ObjectInterface;
use function snake_case;

/**
 * Model backend using the Doctrine DBAL.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class Doctrine implements BackendInterface
{
    /**
     * @var Connection
     */
    private $conn;
    private $type_map = [
        DateTime::class        => 'datetimetz',
        ObjectInterface::class => 'integer',
        Decimal::class         => 'decimal',
    ];
    private $allowed_options = [
        'boolean'    => [
            'default' => true,
        ],
        'integer'    => [
            'generated_value' => 'autoincrement',
            'unsigned'        => true,
            'default'         => true,
        ],
        'float'      => [
            'default' => true,
        ],
        'string'     => [
            'length'  => true,
            'default' => true,
        ],
        'datetimetz' => [
            'default' => true,
        ],
    ];

    /**
     * Initialise Backend
     * 
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Create migration class to get from current state
     * of the databse to the required state.
     *
     * @param ObjectInterface $object
     * @param ClassStub $class
     * @return ClassStub
     */
    public function createMigration(ObjectInterface $object, ClassStub $class): ClassStub
    {
        $schema_manager = $this->conn->getSchemaManager();
        $from_schema = $schema_manager->createSchema();
        $to_schema = $this->getToSchema($from_schema, $object);
        $diff = $this->getDiff($from_schema, $to_schema);

        $a_diff = new AgnosticSchemaDiff($diff);
        $class->extendsFrom(\Doctrine\DBAL\Migrations\AbstractMigration::class);
        $up = $class->addMethod('up');
        foreach ($a_diff->getUpParameters() as $param) {
            $up->addParameter($param['name'], $param['type'] ?? null);
        }
        foreach ($a_diff->getUpCommands() as $command) {
            $up->initialize($command);
        }
        $down = $class->addMethod('down');
        foreach ($a_diff->getDownParameters() as $param) {
            $down->addParameter($param['name'], $param['type'] ?? null);
        }
        foreach ($a_diff->getDownCommands() as $command) {
            $down->initialize($command);
        }
        return $class;
    }

    /**
     * Generate a new schema to compare against
     * 
     * @param Schema $from_schema
     * @param ObjectInterface $object
     * @return Schema
     */
    private function getToSchema(Schema $from_schema, ObjectInterface $object): Schema
    {
        $to_schema = clone $from_schema;
        $table_name = $this->getTableName($object);
        if ($to_schema->hasTable($table_name)) {
            $to_schema->dropTable($table_name);
        }
        $table = $to_schema->createTable($table_name);
        $primary_keys = [];
        foreach ($object->getProperties() as $property => $options) {
            $column_name = $this->getColumnName($property, $options);
            $type = $this->getType($options);
            $column_options = $this->getOptions($options);
            $table->addColumn($column_name, $type, $column_options);

            if (!empty($options['id'])) {
                $primary_keys[] = $column_name;
            }

            if (is_subclass_of($options['type'], ObjectInterface::class) || is_a($options['type'],
                    ObjectInterface::class, true)) {
                $table->addIndex([$column_name]);
                $foreign_table = $this->getTableName($options['type']);
                $foreign_key = call_user_func($options['type'] . '::getIdProperty');
                $on_update = 'CASCADE';
                $on_delete = 'CASCADE';
                $table->addForeignKeyConstraint($foreign_table, [$column_name],
                    [$foreign_key],
                    ['onUpdate' => $on_update, 'onDelete' => $on_delete]);
            }
        }
        if (!empty($primary_keys)) {
            $table->setPrimaryKey($primary_keys);
        }
        return $to_schema;
    }

    /**
     * Get a diff between two schemas
     *
     * @param Schema $from_schema
     * @param Schema $to_schema
     * @return SchemaDiff
     */
    private function getDiff(Schema $from_schema, Schema $to_schema): SchemaDiff
    {
        return Comparator::compareSchemas($from_schema, $to_schema);
    }

    /**
     * Get commands to migrate diff up
     * 
     * @param SchemaDiff $diff
     * @return array
     */
    private function getUpCommands(SchemaDiff $diff): array
    {
        $a_diff = new AgnosticSchemaDiff($diff);
        return $a_diff->getUpCommands();
    }

    /**
     * Get commands to migrate diff down
     * 
     * @param SchemaDiff $diff
     * @return array
     */
    private function getDownCommands(SchemaDiff $diff): array
    {
        $a_diff = new AgnosticSchemaDiff($diff);
        return $a_diff->getDownCommands();
    }

    /**
     * Search data for the given object. Returns arrays.
     *
     * @param ObjectInterface $object
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return iterable
     */
    public function find(ObjectInterface $object, $term): iterable
    {
        $query = $this->conn->createQueryBuilder();
        $query->select()
            ->from($this->getTableName($object));
        if (!is_array($term)) {
            $term = [$object->getIdProperty() => $term];
        }
        foreach ($term as $column => $value) {
            $query->andWhere($this->conn->quoteIdentifier($column) . ' = ?');
        }
        $query->setParameters(array_values($term));
        $stmt = $query->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $new_object = clone $object;
            $new_object->setValues($row);
            yield $new_object;
        }
    }

    /**
     * Save the data in the given object to a file or database.
     *
     * @param ObjectInterface $object
     * @return BackendInterface
     */
    public function save(ObjectInterface $object): BackendInterface
    {
        $table = $this->getTableName($object);
        $this->conn->transactional(function(Connection $conn) use ($table, $object) {
            if ($id = $object->{$object->getIdProperty()}) {
                $query = $conn->createQueryBuilder();
                $query->select($object->getIdProperty())
                    ->from($table)
                    ->where($object->getIdProperty() . ' = :id')
                    ->setParameter('id', $id);
                if ($id == $query->execute()->fetchColumn()) {
                    $conn->update($table, $object->getValues(),
                        [$object->getIdProperty() => $id]);
                    return;
                }
                throw new Exception('Object with id ' . $id . ' does not exist anymore.', Exception::NOT_EXISTS);
            }
            $conn->insert($table, $object->getValues());
            $id = $conn->lastInsertId();
            $object->{$object->getIdProperty()} = $id;
        });

        return $this;
    }

    private function getTableName($class)
    {
        $reflect = new ReflectionClass($class);
        return snake_case($reflect->getShortName());
    }

    private function getColumnName(string $property, array $options)
    {
        if (is_subclass_of($options['type'], ObjectInterface::class)) {
            $property .= '_id';
        }
        return $property;
    }

    private function getType(array $options)
    {
        switch ($options['type']) {
            case 'bool':
            case 'boolean':
                $type = 'boolean';
                break;
            case 'int':
            case 'integer':
                $type = 'integer';
                break;
            case 'float':
                $type = 'float';
                break;
            case 'string':
                $type = 'string';
                break;
            case 'array':
                $type = 'array';
                break;
            default:
                foreach ($this->type_map as $class => $alias) {
                    if (is_subclass_of($options['type'], $class) || is_a($options['type'],
                            $class, true)) {
                        $type = $alias;
                        break;
                    }
                }
                break;
        }
        if (!isset($type)) {
            throw new InvalidTypeException('Unknown type: ' . $options['type']);
        }
        return $type;
    }

    private function getOptions(array $options)
    {
        $out = [];
        $type = $this->getType($options);
        foreach ($options as $option => $value) {
            if (isset($this->allowed_options[$type][$option])) {
                if (is_string($this->allowed_options[$type][$option])) {
                    $option = $this->allowed_options[$type][$option];
                }
                $out[$option] = $value;
            }
        }
        if (is_subclass_of($options['type'], ObjectInterface::class) || is_a($options['type'],
                ObjectInterface::class, true)) {
            $out['unsigned'] = true;
        }
        if (is_subclass_of($options['type'], DateTime::class) || is_a($options['type'],
                DateTime::class, true)) {
            if (isset($options['default']) && $options['default'] == 'now') {
                $out['default'] = 0;
            }
            if (isset($options['on_update']) && $options['on_update'] == 'now') {
                $out['version'] = true;
            }
        }
        return $out;
    }
}
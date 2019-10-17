<?php
namespace Dryspell\Migrations;

use DateTime;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Schema\Schema;
use Dryspell\InvalidTypeException;
use Dryspell\Models\ObjectInterface;
use ReflectionClass;
use RtLopez\Decimal;
use function snake_case;

/**
 * A schema provider that uses the dryspell ORM to generate schemas.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class SchemaProvider implements SchemaProviderInterface
{

    /**
     *
     * @var ObjectInterface[]
     */
    private $objects = [];
    private $type_map        = [
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
     *
     * @param ObjectInterface[] $objects
     */
    public function __construct(array $objects = [])
    {
        $this->objects = $objects;
    }

    /**
     * Add object to stack
     *
     * @param ObjectInterface $object
     */
    public function addObject(ObjectInterface $object)
    {
        $this->objects[] = $object;
    }

    public function createSchema(): Schema
    {
        $to_schema = new Schema();
        foreach ($this->objects as $object) {
            $table_name   = $this->getTableName($object);
            $table        = $to_schema->createTable($table_name);
            $primary_keys = [];
            foreach ($object->getProperties() as $property => $options) {
                $column_name    = $this->getColumnName($property, $options);
                $type           = $this->getType($options);
                $column_options = $this->getOptions($options);
                $table->addColumn($column_name, $type, $column_options);

                if (!empty($options['id'])) {
                    $primary_keys[] = $column_name;
                }

                if (is_subclass_of($options['type'], ObjectInterface::class) || is_a($options['type'],
                        ObjectInterface::class, true)) {
                    $table->addIndex([$column_name]);
                    $foreign_table = $this->getTableName($options['type']);
                    $foreign_key   = call_user_func($options['type'] . '::getIdProperty');
                    $on_update     = 'CASCADE';
                    $on_delete     = $options['required'] ? 'CASCADE' : 'SET NULL';
                    $table->addForeignKeyConstraint($foreign_table, [$column_name],
                        [$foreign_key],
                        ['onUpdate' => $on_update, 'onDelete' => $on_delete]);
                }
            }
            if (!empty($primary_keys)) {
                $table->setPrimaryKey($primary_keys);
            }
        }
        return $to_schema;
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
        $out  = [];
        $type = $this->getType($options);
        foreach ($options as $option => $value) {
            if (isset($this->allowed_options[$type][$option])) {
                if (is_string($this->allowed_options[$type][$option])) {
                    $option = $this->allowed_options[$type][$option];
                }
                $out[$option] = $value;
            }
        }
        $out['notnull'] = $options['required'] ?? false;
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

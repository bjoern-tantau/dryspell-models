<?php
namespace Dryspell\Models\Backends;

use Doctrine\DBAL\Connection;
use Dryspell\Models\BackendInterface;
use Dryspell\Models\BaseObject;
use Dryspell\Models\ObjectInterface;
use PDO;
use ReflectionClass;
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
     * Search data for the given object. Returns objects.
     *
     * @param ObjectInterface $object
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return iterable
     */
    public function find(ObjectInterface $object, $term = null): iterable
    {
        $query = $this->conn->createQueryBuilder();
        $query->select('*')
            ->from($this->getTableName($object));
        if (is_null($term)) {
            $term = [];
        }
        if (!is_array($term)) {
            $term = [$object->getIdProperty() => $term];
        }
        foreach ($term as $column => $value) {
            $query->andWhere($this->conn->quoteIdentifier($column) . ' LIKE ?');
        }
        $query->setParameters(array_values($term));
        $stmt = $query->execute();
        while ($row  = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $new_object = clone $object;
            foreach ($row as $key => $value) {
                $this->setProperty($new_object, $key, $value);
            }
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
                throw new Exception('Object with id ' . $id . ' does not exist anymore.',
                    Exception::NOT_EXISTS);
            }

            $conn->insert($table,
                array_filter($object->getValues(),
                    function($value) {
                    return !is_null($value);
                }));
            $id = $conn->lastInsertId();
            $this->setProperty($object, $object->getIdProperty(), $id);
        });

        return $this;
    }

    private function getTableName($class)
    {
        $reflect = new ReflectionClass($class);
        return snake_case($reflect->getShortName());
    }

    private function setProperty(BaseObject $object, string $property, $value)
    {
        $options = $object->getProperties()[$property];
        switch ($options['type']) {
            case 'bool':
            case 'boolean':
                $value = boolval($value);
                break;
            case 'int':
            case 'integer':
                $value = intval($value);
                break;
            case 'float':
                $value = floatval($value);
                break;
            case 'string':
                // Usually already a string
                break;
            case 'array':
                if (is_string($value)) {
                    $value = unserialize($value);
                }
                break;
            case \DateTime::class:
                if (is_numeric($value)) {
                    $date = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date;
                } else {
                    $value = new \DateTime($value);
                }
                break;
            default:
                $value = new $options['type']($value);
                break;
        }
        $object->$property = $value;
        return $this;
    }
}

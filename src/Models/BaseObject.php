<?php
namespace Dryspell\Models;

use DateTime;
use Dryspell\Traits\AnnotationProperties;
use JsonSerializable;

/**
 * Abstract object to be used for all models.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 *
 * @property int $id Identifier @id, @GeneratedValue, @unsigned
 * @property DateTime $created_at Time and date of creation. @default(now)
 * @property DateTime $updated_at Time and date of last update. @default(now), @OnUpdate(now)
 */
abstract class BaseObject implements ObjectInterface, JsonSerializable
{

    use AnnotationProperties;

    /** @var string Property to be used as id of object. */
    protected static $id_property = 'id';

    /** @var BackendInterface Data-Managing Backend. */
    private $backend;

    /**
     * Currently selected key in the iteration
     *
     * @var integer
     */
    private $current = 0;

    /**
     * Keys to iterate over
     *
     * @var array
     */
    private $keys = [];

    public function __construct(BackendInterface $backend)
    {
        $this->backend = $backend;
        $this->keys    = array_keys($this->getProperties());
    }

    /**
     * Returns the name of the identifier property.
     *
     * @return string
     */
    public static function getIdProperty(): string
    {
        return static::$id_property;
    }

    /**
     * Save data via the backend.
     *
     * @return BaseObject
     */
    public function save(): ObjectInterface
    {
        $this->backend->save($this);
        return $this;
    }

    /**
     * Remove the object via the backend.
     *
     * @return ObjectInterface
     */
    public function delete(): ObjectInterface
    {
        $this->backend->delete($this);
        return $this;
    }

    /**
     * Find many instances of the object with the given criteria.
     *
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return BaseObject[]
     */
    public function find($term = null): iterable
    {
        foreach ($this->backend->find($this, $term) as $object) {
            yield $object;
        }
    }

    /**
     * Find one instance of the object with the given criteria.
     *
     * @param int|string $id Id of the desired object.
     * Array searches for the given property key with the given value.
     * @return BaseObject
     */
    public function load($id): ObjectInterface
    {
        foreach ($this->backend->find($this, [$this->getIdProperty() => $id]) as $values) {
            return $this->setValues($values);
        }
        return $this;
    }

    /**
     * Get all values of the object.
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->getProperties() as $property => $options) {
            $values[$property] = $this->$property;
        }
        return $values;
    }

    /**
     * Mass-assign values to properties.
     *
     * @param array $values Associative array of properties and their values.
     * @param bool $weaklyTyped Perform type conversion while setting.
     * @return BaseObject
     */
    public function setValues(array $values, bool $weaklyTyped = false): ObjectInterface
    {
        foreach ($values as $key => $value) {
            if ($weaklyTyped) {
                $value = $this->convertValueForProperty($key, $value);
            }
            $this->$key = $value;
        }
        return $this;
    }

    public function setWeaklyTyped(string $name, $value): \Dryspell\Models\ObjectInterface
    {
        $properties = $this->getProperties();
        if (!isset($properties[$name])) {
            $name = preg_replace('/_id$/', '', $name);
        }
        $this->$name = $this->convertValueForProperty($name, $value);
        return $this;
    }

    /**
     * (PHP 5 >= 5.0.0, PHP 7)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->{$this->keys[$this->current]};
    }

    /**
     * (PHP 5 >= 5.0.0, PHP 7)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return scalar scalar on success, or <b>NULL</b> on failure.
     */
    public function key()
    {
        return $this->keys[$this->current];
    }

    /**
     * (PHP 5 >= 5.0.0, PHP 7)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next(): void
    {
        $this->current++;
    }

    /**
     * (PHP 5 >= 5.0.0, PHP 7)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind(): void
    {
        $this->current = 0;
    }

    /**
     * (PHP 5 >= 5.0.0, PHP 7)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->current]);
    }

    /**
     * Specify data which should be serialized to JSON
     * <p>Serializes the object to a value that can be serialized natively by <code>json_encode()</code>.</p>
     * @return mixed <p>Returns data which can be serialized by <code>json_encode()</code>, which is a value of any type other than a <code>resource</code>.</p>
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @since PHP 5 >= 5.4.0, PHP 7
     */
    public function jsonSerialize()
    {
        return $this->getValues();
    }

    /**
     * Converts the given value for the named property to the appropriate type.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws InvalidTypeException
     */
    private function convertValueForProperty(string $name, $value)
    {
        $properties = $this->getProperties();
        if (!isset($properties[$name])) {
            $name = preg_replace('/_id$/', '', $name);
        }
        $options = $properties[$name];
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
                $value = strval($value);
                break;
            case 'array':
                if (is_string($value)) {
                    $value = unserialize($value);
                }
                break;
            case '\\' . \DateTime::class:
                if (is_numeric($value)) {
                    $date  = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date;
                } elseif (is_array($value)) {
                    if (!isset($value['date'])) {
                        throw new InvalidTypeException("Can't convert array to DateTime.");
                    }
                    $date = new \DateTime($value['date']);
                    if (isset($value['timezone'])) {
                        $timezone = new \DateTimeZone($value['timezone']);
                        $date->setTimezone($timezone);
                    }
                    $value = $date;
                } else {
                    $value = new \DateTime($value);
                }
                break;
            default:
                if (is_a($options['type'], self::class, true)) {
                    /* @var $object BaseObject */
                    $object     = new $options['type']($this->backend);
                    $object->{$object->getIdProperty()} = $value;
                    $value = $object;
                } else {
                    $value = new $options['type']($value);
                }
                break;
        }
        return $value;
    }
}

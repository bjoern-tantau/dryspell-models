<?php

namespace Dryspell\Models;

/**
 * Abstract object to be used for all models.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 *
 * @property int $id Identifier @id, @GeneratedValue, @unsigned
 * @property \DateTime $created_at Time and date of creation. @default(now)
 * @property \DateTime $updated_at Time and date of last update. @default(now), @OnUpdate(now)
 */
abstract class Object implements ObjectInterface
{

    use \Dryspell\Traits\AnnotationProperties;
    /** @var string Property to be used as id of object. */
    protected static $id_property = 'id';

    /** @var BackendInterface Data-Managing Backend. */
    private $backend;

    public function __construct(BackendInterface $backend)
    {
        $this->backend = $backend;
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
     * @return Object
     */
    public function save(): ObjectInterface
    {
        $this->backend->save($this);
        return $this;
    }

    /**
     * Find many instances of the object with the given criteria.
     *
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return Object[]
     */
    public function find($term): \Iterator
    {
        $data = $this->backend->find($this, $term);
        foreach ($data as $values) {
            $obj = new static($this->backend);
            $obj->setValues($values);
            yield $obj;
        }
    }

    /**
     * Find one instance of the object with the given criteria.
     *
     * @param int|string $id Id of the desired object.
     * Array searches for the given property key with the given value.
     * @return Object
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
     * @return Object
     */
    public function setValues(array $values): ObjectInterface
    {
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
}
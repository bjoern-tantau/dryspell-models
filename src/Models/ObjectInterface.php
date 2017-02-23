<?php

namespace Tantau\Models;

/**
 * Generic Interface for objects
 * that can be saved in a database or file.
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
interface ObjectInterface
{

    /**
     * Get properties of object.
     *
     * @return array
     */
    public function getProperties(): array;

    /**
     * Returns the name of the identifier property.
     *
     * @return string
     */
    public function getIdProperty(): string;

    /**
     * Mass-assign values to properties.
     *
     * @param array $values Associative array of properties and their values.
     * @return ObjectInterface
     */
    public function setValues(array $values): self;

    /**
     * Save data via the backend.
     *
     * @return ObjectInterface
     */
    public function save(): self;

    /**
     * Find many instances of the object with the given criteria.
     *
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return ObjectInterface[]
     */
    public function find($term): iterable;

    /**
     * Find one instance of the object with the given criteria.
     *
     * @param int|string $id Id of the desired object.
     * Array searches for the given property key with the given value.
     * @return ObjectInterface
     */
    public function load($id): self;
}

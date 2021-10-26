<?php
namespace Dryspell\Models;

/**
 * Generic Interface for objects
 * that can be saved in a database or file.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
interface ObjectInterface
{

    /**
     * Get properties of object.
     * Alongside options of said properties.
     *
     * @return Options[]
     */
    public function getProperties(): array;

    /**
     * Returns the name of the identifier property.
     *
     * @return string
     */
    public static function getIdProperty(): string;

    /**
     * Get all values of the object.
     *
     * @return array
     */
    public function getValues(): array;

    /**
     * Mass-assign values to properties.
     *
     * @param array $values Associative array of properties and their values.
     * @param bool $weaklyTyped Perform type conversion while setting.
     * @return ObjectInterface
     */
    public function setValues(array $values, bool $weaklyTyped = false): self;

    /**
     * Set a value while converting it to the required type.
     *
     * @param string $name
     * @param mixed $value
     * @return ObjectInterface
     */
    public function setWeaklyTyped(string $name, $value): self;
}

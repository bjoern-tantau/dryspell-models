<?php
namespace Dryspell\Models;

use DateTime;
use DateTimeZone;
use Dryspell\InvalidTypeException;
use JsonSerializable;

/**
 * Abstract object to be used for all models.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
abstract class AbstractObject implements ObjectInterface, JsonSerializable
{

    /**
     * Get all values of the object.
     *
     * @return array
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->getProperties() as $property => $options) {
            $ref = new \ReflectionProperty($this, $property);
            if ($ref->isInitialized($this)) {
                $values[$property] = $this->$property;
            }
        }
        return $values;
    }

    /**
     * Mass-assign values to properties.
     *
     * @param array $values Associative array of properties and their values.
     * @param bool $weaklyTyped Perform type conversion while setting.
     * @return AbstractObject
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

    public function setWeaklyTyped(string $name, $value): ObjectInterface
    {
        $properties = $this->getProperties();
        if (!isset($properties[$name])) {
            $name = preg_replace('/_id$/', '', $name);
        }
        $this->$name = $this->convertValueForProperty($name, $value);
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * <p>Serializes the object to a value that can be serialized natively by <code>json_encode()</code>.</p>
     * @return mixed <p>Returns data which can be serialized by <code>json_encode()</code>, which is a value of any type other than a <code>resource</code>.</p>
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @since PHP 5 >= 5.4.0, PHP 7, PHP 8
     */
    public function jsonSerialize(): mixed
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
        if (is_null($value)) {
            return $value;
        }

        $properties = $this->getProperties();
        if (!isset($properties[$name])) {
            $name = preg_replace('/_id$/', '', $name);
        }
        $options = $properties[$name];
        switch ($options->type) {
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
                if ($value instanceof DateTime) {
                    $value = $value;
                } elseif (is_numeric($value)) {
                    $date  = new \DateTime();
                    $date->setTimestamp($value);
                    $value = $date;
                } elseif (is_array($value)) {
                    if (!isset($value['date'])) {
                        throw new InvalidTypeException("Can't convert array to DateTime.");
                    }
                    $date = new \DateTime($value['date']);
                    if (isset($value['timezone'])) {
                        $timezone = new DateTimeZone($value['timezone']);
                        $date->setTimezone($timezone);
                    }
                    $value = $date;
                } else {
                    $value = new \DateTime($value);
                }
                break;
            default:
                if (empty($value)) {
                    $value = null;
                } elseif (is_a($value, $options->type, false)) {
                    $value = $value;
                } elseif (is_subclass_of($options->type, ObjectInterface::class)) {
                    /* @var $object ObjectInterface */
                    $object = new $options->type();
                    $object->setWeaklyTyped($object->getIdProperty(), $value);
                    $value  = $object;
                } else {
                    $value = new $options->type($value);
                }
                break;
        }
        return $value;
    }

    /**
     *
     * @return Options[]
     */
    public function getProperties(): array
    {
        $reflection = new \ReflectionObject($this);
        $properties = [];
        /* @var $property \ReflectionProperty */
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            /* @var $attribute \ReflectionAttribute */
            $attribute = $property->getAttributes(Options::class)[0] ?? null;
            if (isset($attribute)) {
                $attributeProperty = $attribute->newInstance();
            } else {
                $attributeProperty = new Options();
            }
            $attributeProperty->type = $property->getType() ?: $attributeProperty->type;
            if (is_string($attributeProperty->type)) {
                if (str_starts_with($attributeProperty->type, '?')) {
                    $attributeProperty->nullable = $attributeProperty->nullable ?? true;
                    $attributeProperty->type     = substr($attributeProperty->type, 1);
                }
                if (class_exists($attributeProperty->type) && !str_starts_with($attributeProperty->type, '\\')) {
                    $attributeProperty->type = '\\' . $attributeProperty->type;
                }
            }
            $properties[$property->getName()] = $attributeProperty;
        }
        return $properties;
    }
}

<?php

namespace Tantau\Traits;

use Tantau\InvalidTypeException;
use Tantau\UndefinedPropertyException;

/**
 * Make getters and setters for virtual public properties available.
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
trait MagicProperties
{

    /**
     * Public properties and their types.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Values of the properties defined in self::$properties.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Get properties available to object.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set given property, if it exists.
     *
     * @param string $name
     * @param mixed $value
     * @throws UndefinedPropertyException
     * @throws InvalidTypeException
     */
    public function __set(string $name, $value)
    {
        $properties = $this->getProperties();
        if (!array_key_exists($name, $properties)) {
            throw new UndefinedPropertyException('Access to undeclared property: ' . static::class . '::' . $name);
        }
        if (!is_null($value)) {
            $type = $properties[$name];
            if (!$this->checkType($value, $type)) {
                throw new InvalidTypeException(static::class . '::' . $name . ' must be of type ' . $type . ', ' . $this->getType($value) . ' given.');
            }
        }
        $method = camel_case('set_' . $name);
        if (is_callable(array($this, $method))) {
            $this->$method($value);
        } else {
            $this->values[$name] = $value;
        }
    }

    /**
     * Get given property, if it exists.
     *
     * @param string $name
     * @return mixed
     * @throws UndefinedPropertyException
     */
    public function __get(string $name)
    {
        $properties = $this->getProperties();
        if (!array_key_exists($name, $properties)) {
            throw new UndefinedPropertyException('Undefined property: ' . static::class . '::' . $name);
        }

        $method = camel_case('get_' . $name);
        if (is_callable(array($this, $method))) {
            return $this->$method();
        } elseif (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        } else {
            return null;
        }
    }

    /**
     * Check existence of given property.
     *
     * @param string $name
     * @return boolean
     */
    public function __isset(string $name)
    {
        return isset($this->values[$name]);
    }

    /**
     * Unset given property.
     *
     * @param string $name
     */
    public function __unset(string $name)
    {
        unset($this->values[$name]);
    }

    /**
     * Check that given value is of the specified type.
     *
     * @param mixed $value
     * @param string $type One of the following:
     * mixed, bool, int, float, string, array, reasource, callable or a class name
     * @return boolean
     */
    protected function checkType($value, string $type)
    {
        switch ($type) {
            case '':
            case 'mixed':
            case 'any':
                break;
            case 'bool':
            case 'boolean':
                return is_bool($value);
            case 'int':
            case 'integer':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'resource':
                return is_resource($value);
            case 'callable':
                return is_callable($value);
            default:
                return is_object($value) && $value instanceof $type;
        }
        return true;
    }

    /**
     * Get type or class of value.
     *
     * @param mixed $value
     * @return string
     */
    protected function getType($value)
    {
        if (is_object($value)) {
            return get_class($value);
        }
        return gettype($value);
    }

}

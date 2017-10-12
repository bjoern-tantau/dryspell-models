<?php

namespace Tantau\Traits;

use Tantau\InvalidTypeException;
use Tantau\UndefinedPropertyException;
use phpDocumentor\Reflection\DocBlock\Tags\Property;

/**
 * Create properties from annotations.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
trait AnnotationProperties
{

    use MagicProperties;
    protected $available_options = [
        'default'         => true,
        'generated_value' => true,
        'id'              => true,
        'length'          => true,
        'on_update'       => true,
        'required'        => true,
        'signed'          => true,
        'unsigned'        => true,
    ];

    /**
     * Get properties available to object.
     *
     * @return array
     */
    public function getProperties(): array
    {
        if (empty($this->properties)) {
            $this->properties = $this->getDocBlockProperties();
        }
        return $this->properties;
    }

    /**
     * Get all defined properties from docblock.
     *
     * @return array
     */
    protected function getDocBlockProperties(): array
    {
        $properties = [];

        $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();

        $reflection = new \ReflectionClass($this);
        $reflections = [];
        do {
            $reflections[] = $reflection;
        } while ($reflection = $reflection->getParentClass());

        $reflections = array_reverse($reflections);

        foreach ($reflections as $reflection) {
            try {
                $docblock = $factory->create($reflection);
                foreach ($docblock->getTagsByName('property') as $property) {
                    /* @var $property Property */
                    $options = [
                        'type' => (string) $property->getType(),
                    ];
                    $options = array_merge($options,
                        $this->getOptions((string) $property->getDescription()));
                    $properties[$property->getVariableName()] = $options;
                }
            } catch (\InvalidArgumentException $e) {
                // Ignore classes with invalid or missing docblocks.
                continue;
            }
        }

        return $properties;
    }

    /**
     * Extracts options beginning with @ from the given string
     * 
     * @param string $desc
     * @return array
     */
    protected function getOptions(string $desc): array
    {
        $options = [];
        $regex = '/@([a-z_]+)(\(([a-z0-9]+)\))?/i';
        if (preg_match_all($regex, $desc, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $key = snake_case($key);
                if (isset($this->available_options[$key])) {
                    $value = $matches[3][$i];
                    switch ($key) {
                        case 'signed':
                            $key = 'unsigned';

                            // reverse meaning because we went
                            // from signed to unsigned
                            $value = false;
                            if (strtolower($value) == 'false') {
                                $value = true;
                            }
                            break;
                        case 'generated_value':
                        case 'id':
                        case 'required':
                        case 'unsigned':
                            $value = true;
                            if (strtolower($value) == 'false') {
                                $value = false;
                            }
                            break;
                        case 'length':
                            $value = (int) $value;
                            break;
                        default:
                            $value = $value;
                            break;
                    }
                    $options[$key] = $value;
                }
            }
        }
        return $options;
    }
}
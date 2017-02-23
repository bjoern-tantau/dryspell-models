<?php

namespace Tantau\Traits;

use Tantau\InvalidTypeException;
use Tantau\UndefinedPropertyException;

/**
 * Create properties from annotations.
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
trait AnnotationProperties
{

    use MagicProperties;

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
                    $properties[$property->getVariableName()] = (string) $property->getType();
                }
            } catch (\InvalidArgumentException $e) {
                // Ignore classes with invalid or missing docblocks.
                continue;
            }
        }

        return $properties;
    }

}

<?php
namespace Dryspell\Traits;

use Dryspell\InvalidTypeException;
use Dryspell\UndefinedPropertyException;
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
        'nullable'        => true,
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

        $reflection  = new \Dryspell\ExtendedReflectionClass($this);
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
                    $type = (string) $property->getType();
                    if ($reflection->hasUseStatement($type)) {
                        $type = '\\' . $reflection->getClassFromUseStatement($type);
                    } else {
                        $namespaced_type = '\\' . $reflection->getNamespaceName() . $type;
                        $reflection->hasUseStatement($type);
                        if (!starts_with('\\', $type) && class_exists($namespaced_type)) {
                            $type = $namespaced_type;
                        }
                    }
                    $options                                  = [
                        'type'     => $type,
                        'required' => is_subclass_of($type, ObjectInterface::class) || is_a($type,
                            ObjectInterface::class, true),
                    ];
                    $options                                  = array_merge($options,
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
        $regex   = '/@([a-z_]+)(\(([a-z0-9]+)\))?/i';
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
                        case 'nullable':
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

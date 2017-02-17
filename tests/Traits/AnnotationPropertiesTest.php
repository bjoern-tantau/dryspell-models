<?php

namespace Tantau\Tests\Traits;

use \Tantau\Traits\AnnotationProperties;
use \PHPunit\Framework\TestCase;

/**
 * Tests for AnnotationProperties Trait
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
class AnnotationPropertiesTest extends TestCase
{

    /**
     * Are properties defined in the annotation returned?
     *
     * @test
     */
    public function testGetProperties()
    {
        $object = new AnnotationPropertiesTestClass();
        $expected = ['foo' => 'string', 'bar' => 'int'];
        $actual = $object->getProperties();
        $this->assertEquals($expected, $actual);

        $object = new AnnotationPropertiesTestClassChild();
        $expected = ['foo' => 'string', 'bar' => 'string', 'baz' => '\Tantau\Tests\Traits\AnnotationPropertiesTestClass'];
        $actual = $object->getProperties();
        $this->assertEquals($expected, $actual);
    }

}

/**
 * Description of AnnotationPropertiesTestClass
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 *
 * @property string $foo Foo property.
 * @property int $bar Bar property.
 */
class AnnotationPropertiesTestClass
{

    use \Tantau\Traits\AnnotationProperties;
}

/**
 * Description of AnnotationPropertiesTestClassChild
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 *
 * @property \Tantau\Tests\Traits\AnnotationPropertiesTestClass $baz Baz property.
 * @property string $bar Bar property, this time as string.
 */
class AnnotationPropertiesTestClassChild extends AnnotationPropertiesTestClass
{

}

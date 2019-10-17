<?php
namespace Dryspell\Tests\Traits;

use \Dryspell\Traits\AnnotationProperties;
use \PHPunit\Framework\TestCase;

/**
 * Tests for AnnotationProperties Trait
 *
 * @category
 * @package
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
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
        $object   = new AnnotationPropertiesTestClass();
        $expected = [
            'foo' => [
                'type'     => 'string',
                'default'  => 'foobar',
                'length'   => 255,
                'required' => true,
            ],
            'bar' => [
                'type'            => 'int',
                'required'        => false,
                'id'              => true,
                'generated_value' => true,
                'unsigned'        => true,
            ],
        ];
        $actual   = $object->getProperties();
        $this->assertEquals($expected, $actual);

        $object   = new AnnotationPropertiesTestClassChild();
        $expected = [
            'foo'         => [
                'type'     => 'string',
                'default'  => 'foobar',
                'length'   => 255,
                'required' => true,
            ],
            'bar'         => [
                'type'     => 'string',
                'required' => false,
            ],
            'baz'         => [
                'type'     => '\Dryspell\Tests\Traits\AnnotationPropertiesTestClass',
                'required' => false,
            ],
            'bazRequired' => [
                'type'     => '\Dryspell\Tests\Traits\AnnotationPropertiesTestClass',
                'required' => true,
            ],
        ];
        $actual   = $object->getProperties();
        $this->assertEquals($expected, $actual);
    }
}

/**
 * Description of AnnotationPropertiesTestClass
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 *
 * @property string $foo Foo property. @default(foobar), @length(255), @required
 * @property int $bar Bar property. @id, @GeneratedValue, @unsigned
 */
class AnnotationPropertiesTestClass
{

    use AnnotationProperties;
}

/**
 * Description of AnnotationPropertiesTestClassChild
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 *
 * @property AnnotationPropertiesTestClass $baz Baz property is nullable.
 * @property AnnotationPropertiesTestClass $bazRequired BazRequired property is not nullable. @required
 * @property string $bar Bar property, this time as string.
 */
class AnnotationPropertiesTestClassChild extends AnnotationPropertiesTestClass
{

}

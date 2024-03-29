<?php
namespace Dryspell\Models;

use Attribute;

/**
 * Options
 *
 * Options to assign to properties.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Options
{

    /**
     *
     * @param string|null $type Fully qualified type of the property. Will usually not be set in the attribute.
     * @param bool $required Whether the property is required.
     * @param type $default The properties default value.
     *                      Use "now" for DateTime properties.
     * @param bool|null $nullable Whether the property is nullable. Will usually not be set in the attribute.
     * @param int|null $length The maximum length of the property value.
     * @param bool $generatedValue Whether the value of the property is generated.
     * @param bool $id Whether the property is used as an identifier.
     * @param bool $signed Whether the integer value of the property is signed.
     * @param string|null $onUpdate Value to assign to the property on every update.
     *                              Use "now" for DateTime properties.
     *                              Use "restrict", "cascade", "set null", "set default" for references to other objects.
     * @param string|null $onDelete Value to assign when a foreign object is deleted.
     *                              Use "restrict", "cascade", "set null", "set default" for references to other objects.
     * @param bool $searchable Create an index to search this property?
     * @param bool $unique Is the property value unique?
     */
    public function __construct(
        public ?string $type = null, public bool $required = false, public $default = null,
        public ?bool $nullable = null, public ?int $length = null, public bool $generatedValue = false,
        public bool $id = false, public bool $signed = true, public ?string $onUpdate = null,
        public ?string $onDelete = null, public bool $searchable = false, public bool $unique = false
    )
    {

    }
}

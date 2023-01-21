<?php
namespace Dryspell\Models;

use DateTime;

/**
 * Base object with id and dates to be used for all models.
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class BaseObject extends AbstractObject
{

    #[Options(id: true, generatedValue: true, signed: false)]
    public int $id;

    #[Options(default: 'now')]
    public DateTime $created_at;

    #[Options(default: 'now', onUpdate: 'now')]
    public DateTime $updated_at;

    /** @var string Property to be used as id of object. */
    protected static $id_property = 'id';

    /**
     * Returns the name of the identifier property.
     *
     * @return string
     */
    public static function getIdProperty(): string
    {
        return static::$id_property;
    }
}

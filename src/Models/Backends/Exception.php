<?php

namespace Tantau\Models\Backends;

/**
 * Exceptions thrown by Backends
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class Exception extends \Exception implements \Tantau\Exception
{
    const NOT_EXISTS = 1;

}
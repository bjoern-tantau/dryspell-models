<?php

namespace Tantau\Models\Backends;

use Tantau\Models\BackendInterface;
use Tantau\Models\ObjectInterface;

/**
 * Model backend using the Doctrine DBAL.
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
class Doctrine implements BackendInterface
{

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * Initialises the connection with the given configuration
     * 
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($config);
    }

    /**
     * Create the necessary database table for the given object.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @return BackendInterface
     */
    public function setup(ObjectInterface $object): BackendInterface
    {
        $properties = $object->getProperties();
        $schemaManager = $this->conn->getSchemaManager();
        return $this;
    }

    /**
     * Search data for the given object. Returns arrays.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return iterable
     */
    public function find(ObjectInterface $object, $term): \Iterator
    {
        yield $tern;
    }

    /**
     * Save the data in the given object to a file or database.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @return BackendInterface
     */
    public function save(ObjectInterface $object): BackendInterface
    {
        
    }

}

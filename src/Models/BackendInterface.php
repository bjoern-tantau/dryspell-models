<?php

namespace Tantau\Models;

/**
 * Backend for actually saving and retrieving
 * data to/from a file or database.
 *
 * @author Björn Tantau <bjoern.tantau@limora.com>
 */
interface BackendInterface
{

    /**
     * Setup a database or file if necessary.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @return BackendInterface
     */
    public function setup(ObjectInterface $object): self;

    /**
     * Save the data in the given object to a file or database.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @return BackendInterface
     */
    public function save(ObjectInterface $object): self;

    /**
     * Search data for the given object. Returns arrays.
     *
     * @param \Tantau\Models\ObjectInterface $object
     * @param int|string|array $term Integer or string searches for the objects id.
     * Array searches for the given property key with the given value.
     * @return iterable
     */
    public function find(ObjectInterface $object, $term): \Iterator;
}

<?php

namespace Dryspell\Migrations;

/**
 * Helper to generate a migration file
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
interface GeneratorHelperInterface
{

    /**
     * Get parameters for the up() method
     *
     * Usefull in conjunction with parameter injection.
     *
     * @return array
     */
    public function getUpParameters(): array;

    /**
     * Get an array of PHP-Commands for the up() method 
     * to run in succession
     * 
     * @return array
     */
    public function getUpCommands(): array;

    /**
     * Get parameters for the down() method
     *
     * Usefull in conjunction with parameter injection.
     *
     * @return array
     */
    public function getDownParameters(): array;

    /**
     * Get an array of PHP-Commands for the down() method
     * to run in succession
     *
     * @return array
     */
    public function getDownCommands(): array;
}
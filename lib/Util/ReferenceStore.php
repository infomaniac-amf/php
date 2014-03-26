<?php
namespace Infomaniac\Util;

use Infomaniac\Exception\DeserializationException;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class ReferenceStore
{
    private $store;

    const TYPE_STRING = 'string';
    const TYPE_OBJECT = 'object';

    public function __construct()
    {
        $this->store = array(self::TYPE_OBJECT => [], self::TYPE_STRING => []);
    }

    /**
     * Creates or retrieves an object reference from the store
     *
     * @param $data
     * @param $type
     *
     * @return int
     */
    public function getReference($data, $type)
    {
        $index = array_search($data, $this->store[$type], true);
        if ($index !== false) {
            return $index;
        }

        if (!$this->validate($data)) {
            return false;
        }

        $this->addReference($data, $type);
        return false;
    }

    /**
     * Retrieves a value of a given type by reference
     *
     * @param $reference
     * @param $type
     *
     * @return bool
     * @throws \Infomaniac\Exception\DeserializationException
     */
    public function getByReference($reference, $type)
    {
        if (!isset($this->store[$type])) {
            return false;
        }

        $count = count($this->store[$type]);

        if ($reference >= $count) {
            throw new DeserializationException('Invalid ' . $type . ' reference: ' . $reference);
        }

        if (!$count) {
            return false;
        }

        return $this->store[$type][$reference];
    }

    /**
     * Adds a new reference by type
     *
     * @param $data
     * @param $type
     *
     * @return bool
     */
    public function addReference(&$data, $type)
    {
        if (!$this->validate($data)) {
            return false;
        }

        $this->store[$type][] =& $data;
        return $data;
    }

    /**
     * Validates a given value and type for issues
     * and prepares array for possible reference addition
     *
     * @param $data
     *
     * @return bool
     */
    private function validate($data)
    {
        // null or zero-length values cannot be assigned references
        if (is_null($data) || (is_string($data) && !strlen($data))) {
            return false;
        }

        return true;
    }
} 
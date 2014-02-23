<?php
namespace Infomaniac\Util;

use Infomaniac\Exception\DeserializationException;

/**
 * @author Danny Kopping <danny.kopping@zando.co.za>
 * @module
 */
class ReferenceStore
{
    private $store;

    const TYPE_STRING = 'string';
    const TYPE_OBJECT = 'object';

    public function __construct()
    {
        $this->store = array();
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
        if (!$this->validate($data, $type)) {
            return false;
        }

        $index = array_search($data, $this->store[$type], true);
        if ($index !== false) {
            return $index;
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
            throw new DeserializationException('Invalid string reference: ' . $reference);
        }

        if (!$count) {
            return false;
        }

        foreach ($this->store[$type] as $ref) {
            if ($ref == $reference) {
                return $this->store[$type][$ref];
            }
        }

        return false;
    }

    /**
     * Adds a new reference by type
     *
     * @param $data
     * @param $type
     *
     * @return bool
     */
    public function addReference($data, $type)
    {
        if (!$this->validate($data, $type)) {
            return false;
        }

        $this->store[$type][] = $data;
        return $data;
    }

    /**
     * Validates a given value and type for issues
     * and prepares array for possible reference addition
     *
     * @param $data
     * @param $type
     *
     * @return bool
     */
    private function validate($data, $type)
    {
        if (empty($this->store)) {
            $this->store = array();
        }

        if (!isset($this->store[$type])) {
            $this->store[$type] = [];
        }

        // null or zero-length values cannot be assigned references
        if (is_null($data) || (is_string($data) && !strlen($data))) {
            return false;
        }

        return true;
    }
} 
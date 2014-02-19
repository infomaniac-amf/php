<?php
namespace Infomaniac\Util;

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
        if (empty($this->store)) {
            $this->store = array();
        }

        if(!isset($this->store[$type])) {
            $this->store[$type] = [];
        }

        // null or zero-length values cannot be assigned references
        if(is_null($data) || (is_string($data) && !strlen($data))) {
            return false;
        }

        $index = array_search($data, $this->store[$type], true);
        if ($index !== false) {
            return $index;
        }

        $this->store[$type][] = $data;
        return false;
    }
} 
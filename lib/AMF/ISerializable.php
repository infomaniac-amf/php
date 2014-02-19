<?php
namespace Infomaniac\AMF;

interface ISerializable
{
    /**
     * Return an associative array of class properties
     *
     * @return array
     */
    public function export();

    /**
     * Import data from an external source into this class
     *
     * @param $data mixed
     */
    public function import($data);
} 
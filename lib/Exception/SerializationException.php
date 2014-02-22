<?php
namespace Infomaniac\Exception;

use Exception;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class SerializationException extends Exception
{
    protected $data;

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
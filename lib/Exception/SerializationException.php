<?php
namespace Infomaniac\Exception;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class SerializationException extends AMFException
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
<?php
namespace Infomaniac\Type;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class ByteArray
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

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

    public function __toString()
    {
        return $this->getData();
    }
} 
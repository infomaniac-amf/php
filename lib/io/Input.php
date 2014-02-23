<?php
namespace Infomaniac\IO;

use Infomaniac\AMF\Spec;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Input extends Stream
{
    private $pointer = 0;

    public function readByte()
    {
        return $this->readBytes(1);
    }

    public function readRawByte()
    {
        return $this->readBytes(1, true);
    }

    public function readRawBytes($length = 1)
    {
        return $this->readBytes($length, true);
    }

    public function readBytes($length = 1, $raw = false)
    {
        $value = substr($this->getRaw(), $this->pointer, $length);
        $this->pointer += strlen($value);
        return $raw ? $value : ord($value);
    }

    /**
     * Read a byte as a float
     *
     * @return float
     */
    public function readDouble()
    {
        $double = $this->readRawBytes(8);

        if (Spec::isBigEndian()) {
            $double = strrev($double);
        }

        $double = unpack("d", $double);
        return $double[1];
    }
} 
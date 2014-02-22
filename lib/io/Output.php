<?php
namespace Infomaniac\IO;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Output extends Stream
{
    /**
     * Write a single byte as a signed char
     *
     * @param $byte
     */
    public function writeByte($byte)
    {
        $this->writeBytes($byte);
    }

    /**
     * Write a stream of bytes as signed chars
     *
     * @param $bytes
     */
    public function writeBytes($bytes)
    {
        $this->raw .= pack('c', $bytes);
    }

    /**
     * Write raw bytes
     *
     * @param $raw
     */
    public function writeRaw($raw)
    {
        $this->raw .= $raw;
    }
} 
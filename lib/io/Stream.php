<?php
namespace Infomaniac\IO;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
abstract class Stream
{
    protected $raw;

    public function __construct($raw = '')
    {
        $this->raw = $raw;
    }

    /**
     * @return string
     */
    public function getRaw()
    {
        return $this->raw;
    }

    public function __toString()
    {
        return $this->raw;
    }
} 
<?php
namespace Infomaniac\Type;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Undefined
{
    public function __toString()
    {
        return null;
    }
}
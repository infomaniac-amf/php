<?php
namespace AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class AMF
{
    public static function serialize($data)
    {
        Serializer::init();
        return Serializer::run($data);
    }
}

class Undefined
{
    public function __toString()
    {
        return null;
    }
}
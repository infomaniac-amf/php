<?php
namespace Infomaniac\AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Deserializer
{
    public static function deserialize($data)
    {
        return self::deserializeUndefined($data);
    }

    private static function deserializeUndefined($data)
    {
        return self::readBytes($data);
    }

    private static function readBytes($data, $offset = 0, $length = 1)
    {
        return ord(substr($data, $offset, $length));
    }
} 
<?php
namespace AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Serializer
{
    private static $packet = null;

    public static function init()
    {
        self::$packet = null;
    }

    public static function run($data)
    {
        switch (true) {
            case $data instanceof Undefined:
                self::serializeUndefined($data);
                break;
        }

        return self::$packet;
    }

    private static function serializeUndefined($data)
    {
        self::writeByte(Spec::MARKER_UNDEFINED);
    }

    private static function writeByte($byte)
    {
        self::$packet += $byte;
    }
} 
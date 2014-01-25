<?php
namespace AMF;

use Exception;

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
                self::serializeUndefined();
                break;

            case $data === null:
                self::serializeNull();
                break;

            case $data === true || $data === false:
                self::serializeBoolean($data);
                break;

            case is_int($data):
                self::serializeInt($data);
                break;

            case is_float($data):
                self::serializeDouble($data);
                break;

            default:
                throw new Exception('Unrecognized AMF type for data: ' . var_export($data));
                break;
        }

        return self::$packet;
    }

    private static function serializeUndefined()
    {
        self::writeBytes(Spec::MARKER_UNDEFINED);
    }

    private static function serializeNull()
    {
        self::writeBytes(Spec::MARKER_NULL);
    }

    private static function serializeBoolean($value)
    {
        self::writeBytes($value === true ? Spec::MARKER_TRUE : Spec::MARKER_FALSE);
    }

    private static function serializeInt($value)
    {
        // AMF3 uses "Variable Length Unsigned 29-bit Integer Encoding"
        // ...depending on the length, we will add some flags or
        // serialize the number as a double

        if ($value < Spec::getMinInt() || $value > Spec::getMaxInt()) {
            self::serializeDouble($value);
            return;
        }

        $value &= Spec::getMaxInt();

        self::writeBytes(Spec::TYPE_INT);
        switch (true) {
            case $value < 0x80:
                self::writeBytes($value);
                break;
            case $value < 0x4000:
                self::writeBytes($value >> 7 & 0x7F | 0x80);
                self::writeBytes($value & 0x7F);
                break;
            case $value < 0x200000:
                self::writeBytes($value >> 14 & 0x7F | 0x80);
                self::writeBytes($value >> 7 & 0x7F | 0x80);
                self::writeBytes($value & 0x7F);
                break;
            case $value < 0x40000000:
                self::writeBytes($value >> 22 & 0x7F | 0x80);
                self::writeBytes($value >> 15 & 0x7F | 0x80);
                self::writeBytes($value >> 8 & 0x7F | 0x80);
                self::writeBytes($value & 0xFF);
                break;
            default:
                throw new Exception(sprintf('Integer %d is out of range and cannot be serialized', $value));
                break;
        }
    }

    private static function serializeDouble($value)
    {
        self::writeBytes(Spec::TYPE_DOUBLE);

        $bin = pack("d", $value);
        if (Spec::isBigEndian()) {
            $bin = strrev($bin);
        }

        self::$packet .= $bin;
    }

    private static function writeBytes($bytes)
    {
        self::$packet .= pack('c', $bytes);
    }
} 
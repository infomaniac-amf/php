<?php
namespace AMF;

use AMF\Exception\NotSupportedException;
use DateTime;
use Exception;
use SimpleXMLElement;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Serializer
{
    private static $packet = null;

    private static $references;

    public static function init()
    {
        self::$packet     = null;
        self::$references = [];
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

            case is_string($data):
                self::serializeString($data);
                break;

            case ($data instanceof SimpleXMLElement):
                throw new NotSupportedException('XML serialization is not supported');
                break;

            case ($data instanceof DateTime):
                self::serializeDate($data);
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

    private static function serializeInt($value, $includeType = true)
    {
        // AMF3 uses "Variable Length Unsigned 29-bit Integer Encoding"
        // ...depending on the length, we will add some flags or
        // serialize the number as a double

        if ($value < Spec::getMinInt() || $value > Spec::getMaxInt()) {
            self::serializeDouble($value);
            return;
        }

        $value &= Spec::getMaxInt();

        if ($includeType) {
            self::writeBytes(Spec::TYPE_INT);
        }

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

    private static function serializeDouble($value, $includeType = true)
    {
        if ($includeType) {
            self::writeBytes(Spec::TYPE_DOUBLE);
        }

        $bin = pack("d", $value);
        if (Spec::isBigEndian()) {
            $bin = strrev($bin);
        }

        self::writeBytes($bin, true);
    }

    private static function serializeString($data, $includeType = true)
    {
        if ($includeType) {
            self::writeBytes(Spec::TYPE_STRING);
        }

        $ref = self::getOrCreateReference($data);
        if ($ref !== false) {
            // use reference
            self::serializeInt($ref << 1, false);
            return;
        }

        self::serializeInt((strlen($data) << 1) | 1, false);
        self::writeBytes($data, true);
    }

    private static function serializeDate(DateTime $data)
    {
        // @see http://php.net/manual/en/datetime.gettimestamp.php#98374
        // use the format() option rather than getTimestamp
        $millisSinceEpoch = $data->format('U') * 1000;

        self::writeBytes(Spec::TYPE_DATE);
        $ref = self::getOrCreateReference($millisSinceEpoch);
        if($ref !== false) {
            self::serializeInt($ref << 1);
        }

        self::serializeInt($millisSinceEpoch);
    }

    private static function getOrCreateReference($object)
    {
        // empty objects cannot have references
        if (empty($object)) {
            return false;
        }

        // reference found
        if (isset(self::$references[$object])) {
            return (int) self::$references[$object];
        }

        // create reference
        $nextID                    = count(self::$references);
        self::$references[$object] = $nextID;
        return false;
    }

    private static function writeBytes($bytes, $raw = false)
    {
        self::$packet .= $raw ? $bytes : pack('c', $bytes);
    }
} 
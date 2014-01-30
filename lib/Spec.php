<?php
namespace AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Spec
{
    /**
     * Markers represent a type AND its value
     */
    const MARKER_UNDEFINED = 0x00;
    const MARKER_NULL      = 0x01;
    const MARKER_FALSE     = 0x02;
    const MARKER_TRUE      = 0x03;

    /**
     * Types represent their proceeding value
     */
    const TYPE_INT           = 0x04;
    const TYPE_DOUBLE        = 0x05;
    const TYPE_STRING        = 0x06;
    const TYPE_XML_DOC       = 0x07;    // not supported
    const TYPE_DATE          = 0x08;
    const TYPE_ARRAY         = 0x09;
    const TYPE_OBJECT        = 0x0A;
    const TYPE_XML           = 0x0B;    // not supported
    const TYPE_BYTE_ARRAY    = 0x0C;
    const TYPE_VECTOR_INT    = 0x0D;
    const TYPE_VECTOR_UINT   = 0x0E;
    const TYPE_VECTOR_DOUBLE = 0x0F;
    const TYPE_VECTOR_OBJECT = 0x10;
    const TYPE_DICTIONARY    = 0x11;

    public static function getMaxInt()
    {
        return pow(2, 28) - 1;
    }

    public static function getMinInt()
    {
        return pow(-2, 29);
    }

    /**
     * @link http://stackoverflow.com/a/9745170
     */
    public static function isBigEndian()
    {
        $test = unpack("C*", pack("S*", 256));
        return !$test[1] == 1;
    }
}
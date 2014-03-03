<?php
namespace Infomaniac\AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Spec
{
    /**
     * Markers represent a type AND its value
     */
    const AMF3_UNDEFINED   = 0x00;
    const AMF3_NULL        = 0x01;
    const AMF3_FALSE       = 0x02;
    const AMF3_TRUE        = 0x03;

    /**
     * Types represent their proceeding value
     */
    const AMF3_INT           = 0x04;
    const AMF3_DOUBLE        = 0x05;
    const AMF3_STRING        = 0x06;
    const AMF3_XML_DOC       = 0x07;    // not supported
    const AMF3_DATE          = 0x08;
    const AMF3_ARRAY         = 0x09;
    const AMF3_OBJECT        = 0x0A;
    const AMF3_XML           = 0x0B;    // not supported
    const AMF3_BYTE_ARRAY    = 0x0C;
    const AMF3_VECTOR_INT    = 0x0D;    // not supported
    const AMF3_VECTOR_UINT   = 0x0E;    // not supported
    const AMF3_VECTOR_DOUBLE = 0x0F;    // not supported
    const AMF3_VECTOR_OBJECT = 0x10;    // not supported
    const AMF3_DICTIONARY    = 0x11;    // not supported

    const OBJECT_DYNAMIC     = 0x00;

    const REFERENCE_BIT      = 0x01;

    const MIN_2_BYTE_INT     = 0x80;
    const MIN_3_BYTE_INT     = 0x4000;
    const MIN_4_BYTE_INT     = 0x200000;

    const MAX_INT            = 0xFFFFFFF;       // (2 ^ 28) - 1
    const MIN_INT            = -0x10000000;     // (-2 ^ 28)

    /**
     * @link http://stackoverflow.com/a/9745170
     */
    public static function isLittleEndian()
    {
        $testint = 0x00FF;
        $p       = pack('S', $testint);
        return $testint === current(unpack('v', $p));
    }

    /**
     * Determine if a given array is "dense".
     *
     * From the AMF spec:
     * "ordinal indices start at 0 and do not contain gaps between successive
     *  indices (that is, every index is defined from 0 for the length of the array)"
     *
     * @param $array
     *
     * @return bool
     */
    public static function isDenseArray($array)
    {
        $arrayLength = count($array);
        if(!$arrayLength) {
            return true;
        }

        // generate a dense array with incrementing numeric keys
        $keyTest = array_flip(range(0, $arrayLength - 1));

        // perform a diff on the two arrays' keys - if there are any differences,
        // then this array is not dense.
        $diff = array_diff_key($keyTest, $array);
        return empty($diff);
    }
}
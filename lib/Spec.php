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

    const OBJECT_DYNAMIC        = 0x00;
    const OBJECT_EXTERNALIZABLE = 0x01;

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

    /**
     * Determine if an object is externalizable, based on its implementing the correct interface
     *
     * @param $data
     *
     * @return bool
     */
    public static function isExternalizable($data)
    {
        if(empty($data) || !is_object($data)) {
            return false;
        }

        return $data instanceof IExternalizable;
    }

    /**
     * Determine if an object is dynamic, i.e. not externalizable
     *
     * @param $data
     *
     * @return bool
     */
    public static function isDynamic($data)
    {
        return !self::isExternalizable($data);
    }
}
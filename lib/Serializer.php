<?php
namespace AMF;

use AMF\Exception\NotSupportedException;
use AMF\Exception\SerializationException;
use DateTime;
use Exception;
use SimpleXMLElement;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Serializer
{
    private static $packet = null;

    /**
     * @var ReferenceStore
     */
    protected static $referenceStore;

    public static function init()
    {
        self::$packet         = null;
        self::$referenceStore = new ReferenceStore();
    }

    public static function serialize($data, $includeType = true)
    {
        switch (true) {
            case $data instanceof Undefined:
                self::serializeUndefined($includeType);
                break;

            case $data === null:
                self::serializeNull($includeType);
                break;

            case $data === true || $data === false:
                self::serializeBoolean($data, $includeType);
                break;

            case is_int($data):
                self::serializeInt($data, $includeType);
                break;

            case is_float($data):
                self::serializeDouble($data, $includeType);
                break;

            case is_string($data):
                self::serializeString($data, $includeType);
                break;

            case ($data instanceof SimpleXMLElement):
                throw new NotSupportedException('XML serialization is not supported');
                break;

            case ($data instanceof DateTime):
                self::serializeDate($data, $includeType);
                break;

            case ($data instanceof ByteArray):
                self::serializeByteArray($data, $includeType);
                break;

            case is_array($data):
                self::serializeArray($data, $includeType);
                break;

            case is_object($data):
                self::serializeObject($data, $includeType);
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

    private static function serializeString($data, $includeType = true, $useRefs = true)
    {
        if ($includeType) {
            self::writeBytes(Spec::TYPE_STRING);
        }

        if ($useRefs) {
            $ref = self::$referenceStore->getReference($data, ReferenceStore::TYPE_STRING);
            if ($ref !== false) {
                //use reference
                self::serializeInt($ref << 1, false);
                return;
            }
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
        self::serializeInt($millisSinceEpoch);
    }

    private static function serializeArray($data)
    {
        self::writeBytes(Spec::TYPE_ARRAY);

        $ref = self::$referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            self::serializeInt($ref << 1, false);
            return;
        }

        $isDense = Spec::isDenseArray($data);
        if ($isDense) {
            self::serializeInt((count($data) << 1) | Spec::REFERENCE_BIT, false);
            self::serializeString('', false);

            foreach ($data as $element) {
                self::serialize($element);
            }

        } else {
            self::serializeInt(1, false);

            foreach ($data as $key => $value) {
                self::serializeString((string) $key, false, false);
                self::serialize($value);
            }

            self::serializeString('', false);
        }
    }

    private static function serializeObject($data)
    {
        self::writeBytes(Spec::TYPE_OBJECT);

        $ref = self::$referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            self::serializeInt($ref << 1, false);
            return;
        }

        // Get the accessible non-static properties of the given object according to scope
        $properties = $data instanceof ISerializable ? $data->export() : get_object_vars($data);

        // write object info & class name
        self::serializeInt(0b1011, false);
        self::serializeString(self::getObjectClassname($data), false, false);

        // write keys
        if (count($properties)) {
            foreach ($properties as $key => $value) {
                self::serializeString($key, false, false);
                self::serialize($value);
            }
        }

        // close
        self::serializeInt(Spec::REFERENCE_BIT, false);
    }

    private static function serializeByteArray($data, $includeType = true)
    {
        if ($includeType) {
            self::writeBytes(Spec::TYPE_BYTE_ARRAY);
        }

        if (!$data instanceof ByteArray) {
            throw new SerializationException('Invalid ByteArray data provided');
        }

        self::serializeInt(strlen($data->getData()) << 1 | Spec::REFERENCE_BIT, false);
        self::writeBytes($data->getData(), true);
    }

    private static function writeBytes($bytes, $raw = false)
    {
        self::$packet .= $raw ? $bytes : pack('c', $bytes);
    }

    /**
     * Get the fully-qualified classname for a given typed object
     *
     * @param $object
     *
     * @return null|string
     */
    private static function getObjectClassname($object)
    {
        if (!is_object($object)) {
            return null;
        }

        $className = get_class($object);
        return $className == 'stdClass' ? null : $className;
    }
} 
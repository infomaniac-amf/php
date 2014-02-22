<?php
namespace Infomaniac\AMF;

use DateTime;
use Exception;
use Infomaniac\Exception\SerializationException;
use Infomaniac\IO\Output;
use Infomaniac\Type\ByteArray;
use Infomaniac\Type\Undefined;
use Infomaniac\Util\ReferenceStore;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Serializer extends Base
{
    public function __construct(Output $stream)
    {
        parent::__construct($stream);
    }

    /**
     * Return the AMF3 data type for given data
     *
     * @param $data
     *
     * @return int|null
     */
    private function getDataType($data)
    {
        switch (true) {
            case $data instanceof Undefined:
                return Spec::AMF3_UNDEFINED;
                break;

            case $data === null:
                return Spec::AMF3_NULL;
                break;

            case $data === true || $data === false:
                return $data ? Spec::AMF3_TRUE : Spec::AMF3_FALSE;
                break;

            case is_int($data):
                if ($data < Spec::getMinInt() || $data > Spec::getMaxInt()) {
                    return Spec::AMF3_DOUBLE;
                }

                return Spec::AMF3_INT;
                break;

            case is_float($data):
                return Spec::AMF3_DOUBLE;
                break;

            case is_string($data):
                return Spec::AMF3_STRING;
                break;

            case ($data instanceof DateTime):
                return Spec::AMF3_DATE;
                break;

            case ($data instanceof ByteArray):
                return Spec::AMF3_BYTE_ARRAY;
                break;

            case is_array($data):
                return Spec::AMF3_ARRAY;
                break;

            case is_object($data):
                return Spec::AMF3_OBJECT;
                break;

            default:
                return null;
                break;
        }
    }

    public function serialize($data, $includeType = true, $forceType = null)
    {
        $type = !empty($forceType) ? $forceType : $this->getDataType($data);

        // add the AMF type marker for this data before the serialized data is added
        if ($includeType) {
            $this->stream->writeByte($type);
        }

        switch ($type) {
            case Spec::AMF3_UNDEFINED:
            case Spec::AMF3_NULL:
            case Spec::AMF3_FALSE:
            case Spec::AMF3_TRUE:
                // no data is serialized except their type marker
                break;

            case Spec::AMF3_INT:
                $this->serializeInt($data);
                break;

            case Spec::AMF3_DOUBLE:
                $this->serializeDouble($data);
                break;

            case Spec::AMF3_STRING:
                $this->serializeString($data);
                break;

            case Spec::AMF3_DATE:
                $this->serializeDate($data);
                break;

            case Spec::AMF3_BYTE_ARRAY:
                $this->serializeByteArray($data);
                break;

            case Spec::AMF3_ARRAY:
                $this->serializeArray($data);
                break;

            case Spec::AMF3_OBJECT:
                $this->serializeObject($data);
                break;

            default:
                throw new Exception("Unrecognized AMF type [$type] for data: " . var_export($data));
                break;
        }

        return $this->stream;
    }

    private function serializeInt($value)
    {
        // AMF3 uses "Variable Length Unsigned 29-bit Integer Encoding"
        // ...depending on the length, we will add some flags or
        // serialize the number as a double

        if ($value < Spec::getMinInt() || $value > Spec::getMaxInt()) {
            $this->serializeDouble($value);
            return;
        }

        $value &= Spec::getMaxInt();

        switch (true) {
            case $value < 0x80:
                $this->stream->writeBytes($value);
                break;
            case $value < 0x4000:
                $this->stream->writeBytes($value >> 7 & 0x7F | 0x80);
                $this->stream->writeBytes($value & 0x7F);
                break;
            case $value < 0x200000:
                $this->stream->writeBytes($value >> 14 & 0x7F | 0x80);
                $this->stream->writeBytes($value >> 7 & 0x7F | 0x80);
                $this->stream->writeBytes($value & 0x7F);
                break;
            case $value < 0x40000000:
                $this->stream->writeBytes($value >> 22 & 0x7F | 0x80);
                $this->stream->writeBytes($value >> 15 & 0x7F | 0x80);
                $this->stream->writeBytes($value >> 8 & 0x7F | 0x80);
                $this->stream->writeBytes($value & 0xFF);
                break;
            default:
                throw new Exception(sprintf('Integer %d is out of range and cannot be serialized', $value));
                break;
        }
    }

    private function serializeDouble($value)
    {
        $bin = pack("d", $value);
        if (Spec::isBigEndian()) {
            $bin = strrev($bin);
        }

        $this->stream->writeRaw($bin);
    }

    private function serializeString($data, $useRefs = true)
    {
        if ($useRefs) {
            $ref = $this->referenceStore->getReference($data, ReferenceStore::TYPE_STRING);
            if ($ref !== false) {
                //use reference
                $this->serializeInt($ref << 1);
                return;
            }
        }

        $this->serializeInt((strlen($data) << 1) | 1);
        $this->stream->writeRaw($data);
    }

    private function serializeDate(DateTime $data)
    {
        // @see http://php.net/manual/en/datetime.gettimestamp.php#98374
        // use the format() option rather than getTimestamp
        $millisSinceEpoch = $data->format('U') * 1000;

        $this->serialize($millisSinceEpoch, true, Spec::AMF3_INT);
    }

    private function serializeArray($data)
    {
        $ref = $this->referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            $this->serializeInt($ref << 1);
            return;
        }

        $isDense = Spec::isDenseArray($data);
        if ($isDense) {
            $this->serializeInt((count($data) << 1) | Spec::REFERENCE_BIT);
            $this->serializeString('');

            foreach ($data as $element) {
                $this->serialize($element);
            }

        } else {
            $this->serializeInt(1);

            foreach ($data as $key => $value) {
                $this->serializeString((string) $key);
                $this->serialize($value);
            }

            $this->serializeString('');
        }
    }

    private function serializeObject($data)
    {
        $ref = $this->referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            $this->serializeInt($ref << 1);
            return;
        }

        // Get the accessible non-properties of the given object according to scope
        $properties = $data instanceof ISerializable ? $data->export() : get_object_vars($data);

        // write object info & class name
        $this->serializeInt(0b1011);
        $this->serializeString($this->getObjectClassname($data), false);

        // write keys
        if (count($properties)) {
            foreach ($properties as $key => $value) {
                $this->serializeString($key, false);
                $this->serialize($value);
            }
        }

        // close
        $this->serializeInt(Spec::REFERENCE_BIT);
    }

    private function serializeByteArray($data)
    {
        if (!$data instanceof ByteArray) {
            throw new SerializationException('Invalid ByteArray data provided');
        }

        $this->serializeInt(strlen($data->getData()) << 1 | Spec::REFERENCE_BIT);
        $this->stream->writeRaw($data->getData());
    }

    /**
     * Get the fully-qualified classname for a given typed object
     *
     * @param $object
     *
     * @return null|string
     */
    private function getObjectClassname($object)
    {
        if (!is_object($object)) {
            return null;
        }

        $className = get_class($object);
        return $className == 'stdClass' ? null : $className;
    }
} 
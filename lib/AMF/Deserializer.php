<?php
namespace Infomaniac\AMF;

use ArrayObject;
use DateTime;
use Exception;
use Infomaniac\Exception\DeserializationException;
use Infomaniac\IO\Input;
use Infomaniac\Type\ByteArray;
use Infomaniac\Type\Undefined;
use Infomaniac\Util\ReferenceStore;
use ReflectionClass;
use stdClass;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Deserializer extends Base
{
    /**
     * @var \Infomaniac\IO\Input
     */
    protected $stream;

    public function __construct(Input $stream, $options = AMF_DEFAULT_OPTIONS)
    {
        parent::__construct($stream, $options = AMF_DEFAULT_OPTIONS);
    }

    public function deserialize($forceType = null)
    {
        // regardless of the type being forced, we need to read the first byte
        $type = $this->stream->readByte();
        $type = empty($forceType) ? $type : $forceType;

        switch ($type) {
            case Spec::AMF3_UNDEFINED:
                return new Undefined();
                break;
            case Spec::AMF3_NULL:
                return null;
                break;
            case Spec::AMF3_FALSE:
                return false;
                break;
            case Spec::AMF3_TRUE:
                return true;
                break;
            case Spec::AMF3_INT:
                return $this->deserializeInt();
                break;
            case Spec::AMF3_DOUBLE:
                return $this->deserializeDouble();
                break;
            case Spec::AMF3_STRING:
                return $this->deserializeString();
                break;
            case Spec::AMF3_DATE:
                return $this->deserializeDate();
                break;
            case Spec::AMF3_ARRAY:
                return $this->deserializeArray();
                break;
            case Spec::AMF3_OBJECT:
                return $this->deserializeObject();
                break;
            case Spec::AMF3_BYTE_ARRAY:
                return $this->deserializeByteArray();
                break;
            default:
                throw new DeserializationException('Cannot deserialize type: ' . $type);
                break;
        }
    }

    private function deserializeInt()
    {
        $result = 0;

        $n = 0;
        $b = $this->stream->readByte();
        while (($b & 0x80) != 0 && $n < 3) {
            $result <<= 7;
            $result |= ($b & 0x7F);
            $b = $this->stream->readByte();
            $n++;
        }
        if ($n < 3) {
            $result <<= 7;
            $result |= $b;
        } else {
            $result <<= 8;
            $result |= $b;
            if (($result & 0x10000000) != 0) {
                $result |= Spec::MIN_INT;
            }
        }

        return $result;
    }

    private function deserializeDouble()
    {
        $double = $this->stream->readRawBytes(8, true);
        if (Spec::isLittleEndian()) {
            $double = strrev($double);
        }

        $double = unpack('d', $double);
        return array_pop($double);
    }

    private function deserializeString()
    {
        $reference = $this->deserializeInt();

        if (($reference & Spec::REFERENCE_BIT) == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_STRING);
        }

        $length = $reference >> Spec::REFERENCE_BIT;
        $string = $this->stream->readRawBytes($length);
        $this->referenceStore->addReference($string, ReferenceStore::TYPE_STRING);

        return $string;
    }

    private function deserializeDate()
    {
        $reference = $this->deserializeInt();

        if (($reference & Spec::REFERENCE_BIT) == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_OBJECT);
        }

        $timestamp = floor($this->stream->readDouble() / 1000);
        $date      = new DateTime("@$timestamp");

        $this->referenceStore->addReference($date, ReferenceStore::TYPE_OBJECT);

        return $date;
    }

    private function deserializeArray()
    {
        $reference = $this->deserializeInt();

        if (($reference & Spec::REFERENCE_BIT) == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_OBJECT);
        }

        $size = $reference >> Spec::REFERENCE_BIT;

        $arr = array();
        $this->referenceStore->addReference($arr, ReferenceStore::TYPE_OBJECT);

        $key = $this->deserializeString();
        while (strlen($key) > 0) {
            $arr[$key] = $this->deserialize();
            $key       = $this->deserializeString();
        }

        for ($i = 0; $i < $size; $i++) {
            $arr[] = $this->deserialize();
        }

        return $arr;
    }

    private function deserializeObject()
    {
        $reference = $this->deserializeInt();
        if (($reference & Spec::REFERENCE_BIT) == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_OBJECT);
        }

        $class    = $this->deserializeString();
        $instance = $this->createClassInstance($class);

        // add a new reference at this stage - essential to handle self-referencing objects
        $this->referenceStore->addReference($instance, ReferenceStore::TYPE_OBJECT);

        // collect all properties into hash
        $data = array();
        while (strlen($property = $this->deserializeString()) > 0) {
            $data[$property] = $this->deserialize();
        }

        if ($instance instanceof ISerializable) {
            $instance->import($data);
        } else {
            // assign all properties to class if property is public
            try {
                foreach ($data as $property => $val) {
                    $instance->$property = $val;
                }
            } catch (Exception $e) {
                throw new DeserializationException("Property [$property] cannot be set on class [$class]");
            }
        }

        return $instance;
    }

    private function deserializeByteArray()
    {
        $reference = $this->deserializeInt();
        if (($reference & Spec::REFERENCE_BIT) == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_OBJECT);
        }

        $length = $reference >> Spec::REFERENCE_BIT;
        $bytes  = $this->stream->readRawBytes($length);

        $instance = new ByteArray($bytes);
        return $instance;

    }

    /**
     * Create an instance of a given class
     * Use stdClass if no class name is provided
     *
     * @param $className
     *
     * @throws \Infomaniac\Exception\DeserializationException
     * @return object|stdClass
     */
    private function createClassInstance($className)
    {
        if (empty($className)) {
            return new stdClass();
        }

        try {
            $refClass = new ReflectionClass($className);
            return $refClass->newInstance();
        } catch (Exception $e) {
            throw new DeserializationException("Class [$className] could not be instantiated");
        }
    }
} 
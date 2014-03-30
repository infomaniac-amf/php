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
    /**
     * @var \Infomaniac\IO\Output
     */
    protected $stream;

    /**
     * @var callable
     */
    protected $classmappingCallback;

    public function __construct(Output $stream, $options = AMF_DEFAULT_OPTIONS)
    {
        parent::__construct($stream, $options);
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
        if ($value < Spec::MIN_INT || $value > Spec::MAX_INT) {
            throw new SerializationException('Integer out of range: ' . $value);
        }

        if ($value < 0 || $value >= Spec::MIN_4_BYTE_INT) {
            $this->stream->writeByte(($value >> 22) | 0x80);
            $this->stream->writeByte(($value >> 15) | 0x80);
            $this->stream->writeByte(($value >> 8) | 0x80);
            $this->stream->writeByte($value);
        } elseif ($value >= Spec::MIN_3_BYTE_INT) {
            $this->stream->writeByte(($value >> 14) | 0x80);
            $this->stream->writeByte(($value >> 7) | 0x80);
            $this->stream->writeByte($value & 0x7f);
        } elseif ($value >= Spec::MIN_2_BYTE_INT) {
            $this->stream->writeByte(($value >> 7) | 0x80);
            $this->stream->writeByte($value & 0x7f);
        } else {
            $this->stream->writeByte($value);
        }
    }

    private function serializeDouble($value)
    {
        $bin = pack("d", $value);
        if (Spec::isLittleEndian()) {
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
        $ref = $this->referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            $this->serializeInt($ref << 1);
            return;
        }

        // @see http://php.net/manual/en/datetime.gettimestamp.php#98374
        // use the format() option rather than getTimestamp
        $millisSinceEpoch = $data->format('U') * 1000;

//        var_dump($data->format('Y-m-d'), $millisSinceEpoch); die();
        $this->serialize($millisSinceEpoch, true, Spec::AMF3_DOUBLE);
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
                $this->serializeString((string) $key, false);
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

        $properties = $data instanceof ISerializable ? $data->export() : get_object_vars($data);

        // write object info & class name
        $this->serializeInt(11);
        $this->serializeString($this->getObjectClassname($data), false);

        // write keys
        if (count($properties)) {
            foreach ($properties as $key => $value) {
                $this->serializeString($key, false);
                $this->serialize($value);
            }
        }

        // close
        $this->serializeString('');
    }

    private function serializeByteArray($data)
    {
        if (!$data instanceof ByteArray) {
            throw new SerializationException('Invalid ByteArray data provided');
        }

        $ref = $this->referenceStore->getReference($data, ReferenceStore::TYPE_OBJECT);
        if ($ref !== false) {
            //use reference
            $this->serializeInt($ref << 1);
            return;
        }

        // write length
        $this->serializeInt((strlen($data->getData()) << 1) | Spec::REFERENCE_BIT);

        // write raw bytes
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

        if (!$this->isClassMappingEnabled()) {
            return '';
        }

        if(is_callable($this->getClassmappingCallback())) {
            $className = call_user_func_array($this->getClassmappingCallback(), [$object]);
        } else {
            $className = get_class($object);
        }

        return $className == 'stdClass' ? '' : $className;
    }

    /**
     * @param callable $classmappingCallback
     */
    public function setClassmappingCallback($classmappingCallback)
    {
        $this->classmappingCallback = $classmappingCallback;
    }

    /**
     * @return callable
     */
    public function getClassmappingCallback()
    {
        return $this->classmappingCallback;
    }

    private function isClassMappingEnabled()
    {
        return $this->options & AMF_CLASS_MAPPING;
    }
} 
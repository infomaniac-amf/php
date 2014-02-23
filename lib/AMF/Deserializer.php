<?php
namespace Infomaniac\AMF;

use DateTime;
use Infomaniac\Exception\DeserializationException;
use Infomaniac\IO\Input;
use Infomaniac\Util\ReferenceStore;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class Deserializer extends Base
{
    /**
     * @var \Infomaniac\IO\Input
     */
    protected $stream;

    public function __construct(Input $stream)
    {
        parent::__construct($stream);
    }

    public function deserialize()
    {
        $type = $this->stream->readByte();

        switch ($type) {
            case Spec::AMF3_UNDEFINED:
            case Spec::AMF3_NULL:
            case Spec::AMF3_FALSE:
            case Spec::AMF3_TRUE:
                // the data type is the value for these simple types
                return $type;
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
            default:
                die("oh noes!");
                break;
        }
    }

    private function deserializeInt()
    {
        $count = 1;
        $int   = 0;

        $byte = $this->stream->readByte();

        while ((($byte & 0x80) != 0) && $count < 4) {
            $int <<= 7;
            $int |= ($byte & 0x7F);
            $byte = $this->stream->readByte();
            $count++;
        }

        if ($count < 4) {
            $int <<= 7;
            $int |= $byte;
        } else {
            // Use all 8 bits from the 4th byte
            $int <<= 8;
            $int |= $byte;
        }

        if (($int & 0x18000000) == 0x18000000) {
            $int ^= 0x1fffffff;
            $int *= -1;
            $int -= 1;
        } else {
            if (($int & 0x10000000) == 0x10000000) {
                // remove the signed flag
                $int &= 0x0fffffff;
            }
        }

        return $int;
    }

    private function deserializeDouble()
    {
        $double = $this->stream->readRawBytes(8, true);
        if (Spec::isBigEndian()) {
            $double = strrev($double);
        }

        $double = unpack('d', $double);
        return array_pop($double);
    }

    private function deserializeString()
    {
        $reference = $this->deserializeInt();

        if ($reference & Spec::REFERENCE_BIT == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_STRING);
        }

        $length = $reference << Spec::REFERENCE_BIT | 1;
        $string = $this->stream->readRawBytes($length);
        $this->referenceStore->addReference($string, ReferenceStore::TYPE_STRING);

        return $string;
    }

    private function deserializeDate()
    {
        $reference = $this->deserializeInt();

        if ($reference & Spec::REFERENCE_BIT == 0) {
            $reference >>= Spec::REFERENCE_BIT;

            return $this->referenceStore->getByReference($reference, ReferenceStore::TYPE_OBJECT);
        }

        $timestamp = $this->stream->readDouble() / 1000;
        $date = new DateTime("@$timestamp");

        $this->referenceStore->addReference($date, ReferenceStore::TYPE_OBJECT);

        return $date;
    }
} 
<?php
namespace Infomaniac\AMF;

use DateTime;
use Infomaniac\IO\Stream;
use Infomaniac\Type\ByteArray;
use Infomaniac\Type\Undefined;
use Infomaniac\Util\ReferenceStore;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
abstract class Base
{
    /**
     * @var \Infomaniac\IO\Stream
     */
    protected $stream;

    /**
     * @var \Infomaniac\Util\ReferenceStore
     */
    protected $referenceStore;

    /**
     * @var int
     */
    protected $options;

    public function __construct(Stream $stream, $options = AMF_DEFAULT_OPTIONS)
    {
        $this->stream  = $stream;
        $this->options = $options;

        $this->referenceStore = new ReferenceStore();
    }

    /**
     * @param \Infomaniac\IO\Stream $stream
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return \Infomaniac\IO\Stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Return the AMF3 data type for given data
     *
     * @param $data
     *
     * @return int|null
     */
    protected function getDataType($data)
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
                // AMF3 uses "Variable Length Unsigned 29-bit Integer Encoding"
                // ...depending on the size, we will either deserialize it as an integer or a float

                if ($data < Spec::MIN_INT || $data > Spec::MAX_INT) {
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
} 
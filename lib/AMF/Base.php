<?php
namespace Infomaniac\AMF;

use Infomaniac\IO\Stream;
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

    public function __construct(Stream $stream)
    {
        $this->stream         = $stream;
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
} 
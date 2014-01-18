<?php

use AMF\AMF;
use AMF\Spec;
use AMF\Undefined;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class SerializationTest extends PHPUnit_Framework_TestCase
{
    public function testSerializeUndefined()
    {
        $this->assertEquals(Spec::MARKER_UNDEFINED, AMF::serialize(new Undefined()));
    }
} 
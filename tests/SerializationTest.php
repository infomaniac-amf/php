<?php
use AMF\AMF;
use AMF\Spec;
use AMF\Undefined;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class SerializationTest extends PHPUnit_Framework_TestCase
{
    public function testSerializeUndefined()
    {
        $this->assertEquals(Spec::MARKER_UNDEFINED, bin2hex(AMF::serialize(new Undefined())));
    }

    public function testSerializeNull()
    {
        $this->assertEquals(Spec::MARKER_NULL, bin2hex(AMF::serialize(null)));
    }

    public function testSerializeBoolean()
    {
        $this->assertEquals(Spec::MARKER_TRUE, bin2hex(AMF::serialize(true)));
        $this->assertEquals(Spec::MARKER_FALSE, bin2hex(AMF::serialize(false)));
    }

    public function testSerializeInt()
    {
        $samples = [1, 13, 1398693, 100000000, 12345013, 9876543, Spec::getMaxInt() - 1];
        $expectations = ["0401", "040d", "04d5af25", "0497ebe100", "0482f8deb5", "0482adb43f", "04bffffffe"];

        foreach ($samples as $sample) {
            $expected = $expectations[array_search($sample, $samples)];

            $this->assertEquals($expected, bin2hex(AMF::serialize($sample)));
        }
    }

    public function testSerializeDouble()
    {
        $samples      = [1.5, 9879.4, 999 * 999 / 2, Spec::getMaxInt() + 2];
        $expectations = ["053ff8000000000000", "0540c34bb333333333", "05411e74e200000000", "0541b0000001000000"];

        foreach ($samples as $sample) {
            $expected = $expectations[array_search($sample, $samples)];

            $this->assertEquals($expected, bin2hex(AMF::serialize($sample)));
        }
    }

    public function testSerializeString()
    {
        $this->assertEquals('060b68656c6c6f', bin2hex(AMF::serialize('hello')));
    }
} 
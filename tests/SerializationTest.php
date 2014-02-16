<?php
use AMF\AMF;
use AMF\ByteArray;
use AMF\IExternalizable;
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

    public function testSerializeObject()
    {
        // dynamic object
        $dyn = new stdClass();
        $dyn->a = 'b';
        $dyn->b = array('123');

        $this->assertEquals('0a0b0103610603620362090301060731323301', bin2hex(AMF::serialize($dyn)));

        // externalizable object
        $ext = new ExternalizableClass();
        $ext->doesnot = 'matter';
        $ext->what = 'properties';
        $ext->are = 'defined';

        // serialization of externalized objects does not necessarily include class members
        $this->assertNotEquals('0a332745787465726e616c697a61626c65436c6173730f646f65736e6f74097768617407617265060d6d6174746572061570726f70657274696573060f646566696e656401', bin2hex(AMF::serialize($ext)));
        $this->assertEquals('0a072745787465726e616c697a61626c65436c6173730901036106036201', bin2hex(AMF::serialize($ext)));

        // object with no properties
        $null = new stdClass();
        $this->assertEquals('0a0b0101', bin2hex(AMF::serialize($null)));
    }

    public function testSerializeBytes()
    {
        $bytes = new ByteArray('A');
        $this->assertEquals('0c0341', bin2hex(AMF::serialize($bytes)));

        $bytes = new ByteArray('$1ï¿½2');
        $this->assertEquals('0c132431c3afc2bfc2bd32', bin2hex(AMF::serialize($bytes)));

        $bytes = new ByteArray(0b11000011101010); // 12522
        $this->assertEquals('0c0b3132353232', bin2hex(AMF::serialize($bytes)));

        $bytes = new ByteArray(file_get_contents(__DIR__.'/php.ico'));
        $this->assertEquals('0c8c6d424d360300000000000036000000280000001000000010000000010018000000000000030000c40e0000c40e00000000000000000000c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7c08080c08080d7d7d7d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7000000000000000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000000000000000d7d7d7d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7c08080d7d7d7d7d7d7d7d7d7c08080d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080',
            bin2hex(AMF::serialize($bytes)));
    }
}

class ExternalizableClass implements IExternalizable {

    /**
     * Write externally provided data into object
     *
     * @param $data
     */
    function setExternalData($data) {}

    /**
     * Read this object's data for external usage
     *
     * @return mixed
     */
    function getExternalData()
    {
        return array('a' => 'b');
    }
}
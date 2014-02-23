<?php
use Infomaniac\AMF\AMF;
use Infomaniac\Type\ByteArray;
use Infomaniac\AMF\ISerializable;
use Infomaniac\AMF\Spec;
use Infomaniac\Type\Undefined;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class SerializationTest extends PHPUnit_Framework_TestCase
{
    public function testSerializeUndefined()
    {
        $this->assertSame(Spec::AMF3_UNDEFINED, AMF::deserialize(AMF::serialize(new Undefined())));
    }

    public function testSerializeNull()
    {
        $this->assertSame(Spec::AMF3_NULL, AMF::deserialize(AMF::serialize(null)));
    }

    public function testSerializeBoolean()
    {
        $this->assertSame(Spec::AMF3_FALSE, AMF::deserialize(AMF::serialize(false)));
        $this->assertSame(Spec::AMF3_TRUE, AMF::deserialize(AMF::serialize(true)));
    }

    public function testSerializeInt()
    {
        $samples = [1, 13, 1398693, 100000000, 12345013, 9876543, Spec::getMaxInt() - 1, -123, -9999999];

        foreach ($samples as $sample) {
            $this->assertEquals($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_INT)));
        }
    }

    public function testSerializeDouble()
    {
        $samples = [1.5, 9879.4, 999 * 999 / 2, Spec::getMaxInt() + 2.0, Spec::getMinInt() * 2];

        foreach ($samples as $sample) {
            $this->assertEquals($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_DOUBLE)));
        }
    }

    public function testSerializeDate()
    {
        $samples = [
            new DateTime(),
            new DateTime('15 Jan 1989'),
            new DateTime('31 December 1880'),
            new DateTime('1 Jan 2079')
        ];

        foreach ($samples as $sample) {
            $timestamp = $sample->format('U');
            $datetime  = AMF::deserialize(AMF::serialize($sample, Spec::AMF3_DATE));

            $this->assertEquals($timestamp, $datetime->format('U'));
        }
    }

    public function testSerializeString()
    {
        $samples = ['hello', '.', file_get_contents(__FILE__), 'ünicødé'];

        foreach ($samples as $sample) {
            $this->assertSame($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_STRING)));
        }
    }

    public function testSerializeObject()
    {
        // dynamic object
        $dyn = new stdClass();
        $dyn->a = 'b';
        $dyn->b = array('123');

        $this->assertEquals('0a0b0103610603620362090301060731323301', bin2hex(AMF::serialize($dyn)));

        // serializable
        $serializable = new SerializableData();
        $serializable->setName('Test');

        $this->assertEquals('0a0b2153657269616c697a61626c6544617461096e616d6506095465737411726576657273656406097473655401',
            bin2hex(AMF::serialize($serializable))
        );

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

        $bytes = new ByteArray(file_get_contents(__DIR__ . '/php.ico'));
        $this->assertEquals('0c8c6d424d360300000000000036000000280000001000000010000000010018000000000000030000c40e0000c40e00000000000000000000c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7c08080c08080d7d7d7d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7000000000000000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000000000000000d7d7d7d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7000000d7d7d7d7d7d7000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7000000000000000000d7d7d7d7d7d7c08080d7d7d7d7d7d7d7d7d7c08080d7d7d7000000d7d7d7d7d7d7c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080d7d7d7000000d7d7d7c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080d7d7d7d7d7d7d7d7d7c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080c08080',
            bin2hex(AMF::serialize($bytes))
        );
    }
}

class SerializableData implements ISerializable
{
    private $name;

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return an associative array of class properties
     *
     * @return array
     */
    public function export()
    {
        return array('name' => $this->getName(), 'reversed' => strrev($this->getName()));
    }

    /**
     * Import data from an external source into this class
     *
     * @param $data mixed
     */
    public function import($data)
    {
        if (isset($data['name'])) {
            $this->setName($data['name']);
        }
    }
}
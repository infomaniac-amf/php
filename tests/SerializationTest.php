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
        $this->assertEquals(new Undefined(), AMF::deserialize(AMF::serialize(new Undefined())));
    }

    public function testSerializeNull()
    {
        $this->assertSame(null, AMF::deserialize(AMF::serialize(null)));
    }

    public function testSerializeBoolean()
    {
        $this->assertSame(false, AMF::deserialize(AMF::serialize(false)));
        $this->assertSame(true, AMF::deserialize(AMF::serialize(true)));
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

    public function testSerializeString()
    {
        $samples = ['hello', '.', file_get_contents(__FILE__), 'ünicødé'];

        foreach ($samples as $sample) {
            $this->assertSame($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_STRING)));
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

    public function testSerializeArray()
    {
        $ref     = ['1' => 1, '4' => 'Hello!'];
        $samples = [['a' => 'b'], [], ['ref' => $ref, 'hi', 'another' => $ref], [1, 2, 3, 4], [5, 9, 10, '11' => 14]];

        foreach ($samples as $sample) {
            $this->assertEquals($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_ARRAY)));
        }
    }

    public function testSerializeObject()
    {
        // dynamic object
        $dyn    = new stdClass();
        $dyn->a = 'b';
        $dyn->b = array('123');
        $dyn->c = new Undefined();

        $this->assertEquals($dyn, AMF::deserialize(AMF::serialize($dyn)));

        // typed object
        $typed           = new NormalClass();
        $typed->property = 'value';
        $this->assertEquals($typed, AMF::deserialize(AMF::serialize($typed)));

        // serializable
        $serializable = new SerializableData();
        $serializable->setName('Test');

        $this->assertEquals($serializable, AMF::deserialize(AMF::serialize($serializable)));

        // reference
        $a    = new stdClass();
        $a->x = 'y';

        $b           = new NormalClass();
        $b->property = 'abc';

        $a->normal = $b;
        $this->assertEquals($a, AMF::deserialize(AMF::serialize($a)));

        // self-reference
        $a       = new stdClass();
        $a->x    = 'y';
        $a->self = $a;
        $this->assertEquals($a, AMF::deserialize(AMF::serialize($a)));
    }

    public function testSerializeBytes()
    {
        $samples = [
            new ByteArray('A'),
            new ByteArray('$1ï¿½2'),
            new ByteArray(0b11000011101010),
            new ByteArray(file_get_contents(__DIR__ . '/php.ico'))
        ];

        foreach ($samples as $sample) {
            $this->assertEquals($sample, AMF::deserialize(AMF::serialize($sample, Spec::AMF3_BYTE_ARRAY)));
        }
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

class NormalClass
{
    public $property;
    public $another = false;
}
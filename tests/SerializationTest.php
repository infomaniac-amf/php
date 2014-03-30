<?php
use Infomaniac\AMF\AMF;
use Infomaniac\Exception\DeserializationException;
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
        $this->assertEquals(new Undefined(), AMF::deserialize(AMF::serialize(new Undefined(), AMF_DEFAULT_OPTIONS)));
    }

    public function testSerializeNull()
    {
        $this->assertSame(null, AMF::deserialize(AMF::serialize(null, AMF_DEFAULT_OPTIONS)));
    }

    public function testSerializeBoolean()
    {
        $this->assertSame(false, AMF::deserialize(AMF::serialize(false, AMF_DEFAULT_OPTIONS)));
        $this->assertSame(true, AMF::deserialize(AMF::serialize(true, AMF_DEFAULT_OPTIONS)));
    }

    public function testSerializeInt()
    {
        $samples = [1, 13, 1398693, 100000000, 12345013, 9876543, Spec::MAX_INT, -123, -9999999, Spec::MIN_INT];

        foreach ($samples as $sample) {
            $this->assertEquals(
                $sample,
                AMF::deserialize(AMF::serialize($sample, AMF_DEFAULT_OPTIONS, Spec::AMF3_INT))
            );
        }
    }

    public function testSerializeDouble()
    {
        $samples = [1.5, 9879.4, 999 * 999 / 2, Spec::MAX_INT + 2.0, Spec::MIN_INT * 2];

        foreach ($samples as $sample) {
            $this->assertEquals(
                $sample,
                AMF::deserialize(
                    AMF::serialize(
                        $sample,
                        AMF_DEFAULT_OPTIONS,
                        Spec::AMF3_DOUBLE
                    )
                )
            );
        }
    }

    public function testSerializeString()
    {
        $samples = ['hello', '.', file_get_contents(__FILE__), 'ünicødé'];

        foreach ($samples as $sample) {
            $this->assertSame(
                $sample,
                AMF::deserialize(AMF::serialize($sample, AMF_DEFAULT_OPTIONS, Spec::AMF3_STRING))
            );
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
            $datetime  = AMF::deserialize(AMF::serialize($sample, AMF_DEFAULT_OPTIONS, Spec::AMF3_DATE));

            $this->assertEquals($timestamp, $datetime->format('U'));
        }
    }

    public function testSerializeArray()
    {
        $ref     = ['1' => 1, '4' => 'Hello!'];
        $samples = array(
            ['a' => 'b'],
            [],
            ['ref' => $ref, 'hi', 'a' => 'reused key', 'another' => $ref],
            ['ref' => $ref, 'another' => $ref],
            [1, 2, 3, 4],
            [5, 9, 10, '11' => 14]
        );

        foreach ($samples as $sample) {
            $this->assertEquals(
                $sample,
                AMF::deserialize(AMF::serialize($sample, AMF_DEFAULT_OPTIONS, Spec::AMF3_ARRAY))
            );
        }
    }

    public function testSerializeObject()
    {
        // dynamic object
        $dyn    = new stdClass();
        $dyn->a = 'b';
        $dyn->b = array('123');
        $dyn->c = new Undefined();
        $dyn->d = new stdClass();

        $this->assertEquals($dyn, AMF::deserialize(AMF::serialize($dyn, AMF_DEFAULT_OPTIONS)));

        // typed object
        $typed           = new NormalClass();
        $typed->property = 'value';
        $this->assertEquals($typed, AMF::deserialize(AMF::serialize($typed, AMF_DEFAULT_OPTIONS | AMF_CLASS_MAPPING)));

        // serializable
        $serializable = new SerializableData();
        $serializable->setName('Test');

        $this->assertEquals(
            $serializable,
            AMF::deserialize(AMF::serialize($serializable, AMF_DEFAULT_OPTIONS | AMF_CLASS_MAPPING))
        );

        // reference
        $a    = new stdClass();
        $a->x = 'y';

        $b           = new NormalClass();
        $b->property = 'abc';

        $a->normal = $b;
        $this->assertEquals($a, AMF::deserialize(AMF::serialize($a, AMF_DEFAULT_OPTIONS | AMF_CLASS_MAPPING)));

        // self-reference
        $a       = new stdClass();
        $a->x    = 'y';
        $a->self = $a;
        $this->assertEquals($a, AMF::deserialize(AMF::serialize($a, AMF_DEFAULT_OPTIONS)));
    }

    /**
     * @expectedException           Infomaniac\Exception\DeserializationException
     * @expectedExceptionMessage    Class [XXX] could not be instantiated
     */
    public function testCustomClassmappingCallback()
    {
        AMF::setClassmappingCallback(function($object) {
            return 'XXX';
        });

        $obj = new NormalClass();
        $serialized = AMF::serialize($obj, AMF_CLASS_MAPPING);
        AMF::deserialize($serialized);
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
            $this->assertEquals(
                $sample,
                AMF::deserialize(
                    AMF::serialize(
                        $sample,
                        AMF_DEFAULT_OPTIONS,
                        Spec::AMF3_BYTE_ARRAY
                    )
                )
            );
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
<?php
namespace AMF;

use AMF\Exception\SerializationException;
use Exception;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class AMF
{
    public static function serialize($data)
    {
        try {
            Serializer::init();

            return Serializer::serialize($data);
        } catch (Exception $e) {
            $ex = new SerializationException($e->getMessage(), $e->getCode(), $e);
            $ex->setData($data);
            throw $ex;
        }
    }
}

class Undefined
{
    public function __toString()
    {
        return null;
    }
}
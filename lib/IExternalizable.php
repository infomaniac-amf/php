<?php
namespace AMF;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
interface IExternalizable
{
    /**
     * Write externally provided data into object
     *
     * @param $data
     */
    function setExternalData($data);

    /**
     * Read this object's data for external usage
     *
     * @return mixed
     */
    function getExternalData();
}
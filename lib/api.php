<?php
use Infomaniac\AMF\AMF;

/**
 * Promote errors to exceptions
 */
define('AMF_PROMOTE_ERRORS', 0x1);

/**
 * Include class mapping information when serializing objects
 */
define('AMF_CLASS_MAPPING', 0x2);

/**
 * By default, do not enable class mapping
 */
define('AMF_DEFAULT_OPTIONS', AMF_PROMOTE_ERRORS);


if (!function_exists('amf_encode')) {
    function amf_encode($data, $options = AMF_DEFAULT_OPTIONS)
    {
        return AMF::serialize($data, $options);
    }
}


if (!function_exists('amf_decode')) {
    function amf_decode($data)
    {
        return AMF::deserialize($data);
    }
}

if (!function_exists('amf_set_classmapping_callback')) {
    function amf_set_classmapping_callback($callback)
    {
        AMF::setClassmappingCallback($callback);
    }
}
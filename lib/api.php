<?php
use Infomaniac\AMF\AMF;

if (!function_exists('amf_encode')) {
    function amf_encode($data)
    {
        return AMF::serialize($data);
    }
}

if (!function_exists('amf_decode')) {
    function amf_decode($data)
    {
        return AMF::deserialize($data);
    }
}
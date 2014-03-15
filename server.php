<?php
use Infomaniac\AMF\AMF;

require_once __DIR__.'/vendor/autoload.php';

$data = file_get_contents("php://input");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
header('Content-Type: application/x-amf');

try {
    $deserialized = AMF::deserialize($data);

    http_response_code(200);
    echo AMF::serialize($deserialized);

} catch(Exception $e) {
    http_response_code(500);

    var_dump($e); die();
    die('Invalid AMF packet');
}
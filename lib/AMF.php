<?php
namespace AMF;

use AMF\Exception\SerializationException;
use ErrorException;
use Exception;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
class AMF
{
    private static $debugMode = false;

    public static function serialize($data)
    {
        // if in debug mode, don't do anything to error handling - let it work normally
        if(!self::$debugMode) {
            set_error_handler('\\AMF\\AMF::errorHandler');
        }

        try {
            Serializer::init();

            return Serializer::serialize($data);
        } catch (Exception $e) {
            $ex = new SerializationException($e->getMessage(), $e->getCode(), $e);
            $ex->setData($data);
            throw $ex;
        }
    }

    /**
     * @link  http://www.php.net/manual/en/class.errorexception.php#95415
     *
     * @param $code
     * @param $message
     * @param $file
     * @param $line
     *
     * @return bool
     * @throws \ErrorException
     */
    public static function errorHandler($code, $message, $file, $line)
    {
        // Determine if this error is one of the enabled ones in php config (php.ini, .htaccess, etc)
        $enabled = (bool) ($code & ini_get('error_reporting'));

        // -- FATAL ERROR
        // throw an Error Exception, to be handled by whatever Exception handling logic is available in this context
        if (in_array($code, array(E_USER_ERROR, E_RECOVERABLE_ERROR, E_WARNING)) && $enabled) {
            throw new ErrorException($message, 0, $code, $file, $line);
        }

        // -- NON-FATAL ERROR/WARNING/NOTICE
        // Log the error if it's enabled, otherwise just ignore it
        else {
            if ($enabled) {
                error_log($message, 0);
                return false; // Make sure this ends up in $php_errormsg, if appropriate
            }
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
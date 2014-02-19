<?php
namespace Infomaniac\AMF;

use Infomaniac\AMF\AMF;
use Infomaniac\Util\ReferenceStore;

/**
 * @author Danny Kopping <dannykopping@gmail.com>
 */
abstract class Base
{
    protected static $packet = null;

    /**
     * @var \Infomaniac\Util\ReferenceStore
     */
    protected static $referenceStore;

    public static function init()
    {
        self::$packet         = null;
        self::$referenceStore = new ReferenceStore();

        // if in debug mode, don't do anything to error handling - let it work normally
        if(!AMF::$debugMode) {
            set_error_handler('\\Infomaniac\\AMF\\AMF::errorHandler');
        }
    }
} 
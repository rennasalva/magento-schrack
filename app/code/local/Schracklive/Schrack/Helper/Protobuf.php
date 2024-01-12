<?php

class Schracklive_Schrack_Helper_Protobuf {
    static private $protbufAutoloadSwitchedOn = false;

    public function initProtobuf () {
        $libDir = Mage::getBaseDir('lib');
        if ( ! self::$protbufAutoloadSwitchedOn ) {
            //instantiate a zend autoloader first, since we
            //won't be able to do it in an unautoloader universe
            require_once $libDir . '/Zend/Loader.php';

            //get a list of call the registered autoloader callbacks
            //and pull out the Varien_Autoload.  It's unlikely there
            //are others, but famous last words and all that
            $autoloader_callbacks = spl_autoload_functions();
            $original_autoload = null;
            foreach($autoloader_callbacks as $callback)
            {
                if(is_array($callback) && $callback[0] instanceof Varien_Autoload)
                {
                    $original_autoload = $callback;
                }
            }

            //remove the Varien_Autoloader from the stack
            spl_autoload_unregister($original_autoload);

            // force now SrSlump autoloader
            require_once $libDir . "/DrSlump/Protobuf.php";
            \DrSlump\Protobuf::autoload();

            //IMPORANT: add the Varien_Autoloader back to the stack
            spl_autoload_register($original_autoload);
            self::$protbufAutoloadSwitchedOn = true;
        }
    }
}
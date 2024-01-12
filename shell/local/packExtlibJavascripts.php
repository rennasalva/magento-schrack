<?php

/*
 * this packs to the extlib.js
 * (extlib javascripts) -> extlib.js
 *
 * DANGER WILL ROBINSON DANGER:
 * this is the 1st step of a 2-step process involving packExtlibJavascripts.php + packAllJavascripts.php!!!!!!!
 */


define('UP', '..');
define('DS', DIRECTORY_SEPARATOR);

define('THIS_DIR', dirname(__FILE__));
define('TOP_DIR', THIS_DIR . DS . UP . DS . UP);
define('MAGENTO_JS_DIR', TOP_DIR . DS . 'js');
define('MAGENTO_JS_PROTOTYPE_DIR', MAGENTO_JS_DIR . DS . 'prototype');
define('MAGENTO_JS_SCRIPTACULOUS_DIR', MAGENTO_JS_DIR . DS . 'scriptaculous');
define('MAGENTO_JS_VARIEN_DIR', MAGENTO_JS_DIR . DS . 'varien');
define('MAGENTO_JS_MAGE_DIR', MAGENTO_JS_DIR . DS . 'mage');
define('MAGENTO_SKIN_BASE_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'base');
define('MAGENTO_SKIN_BASE_JS_DIR', MAGENTO_SKIN_BASE_DIR . DS . 'default' . DS . 'js');

define('SCHRACK_JS_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'schrack' . DS . 'default' . DS . 'schrackdesign' . DS . 'Public' . DS . 'Javascript');

$outFile = SCHRACK_JS_DIR . DS . 'extlibPacked.js';

$inFiles = array(
    MAGENTO_JS_DIR . DS . 'prototype' . DS . 'prototype.js',
    MAGENTO_JS_DIR . DS . 'prototype' . DS . 'validation.js',

    MAGENTO_JS_DIR . DS . 'scriptaculous' . DS . 'builder.js',
    MAGENTO_JS_DIR . DS . 'scriptaculous' . DS . 'effects.js',
    MAGENTO_JS_DIR . DS . 'scriptaculous' . DS . 'dragdrop.js',
    MAGENTO_JS_DIR . DS . 'scriptaculous' . DS . 'controls.js',
    MAGENTO_JS_DIR . DS . 'scriptaculous' . DS . 'slider.js',

    MAGENTO_JS_DIR . DS . 'varien' . DS . 'js.js',
    MAGENTO_JS_DIR . DS . 'varien' . DS . 'form.js',
    //MAGENTO_JS_DIR . DS . 'varien' . DS . 'menu.js',
    MAGENTO_JS_DIR . DS . 'varien' . DS . 'product.js',
    MAGENTO_JS_DIR . DS . 'varien' . DS . 'configurable.js',

    MAGENTO_JS_DIR . DS . 'calendar' . DS . 'calendar.js',
    MAGENTO_JS_DIR . DS . 'calendar' . DS . 'calendar-setup.js',

    MAGENTO_JS_DIR . DS . 'mage' . DS . 'translate.js',
    MAGENTO_JS_DIR . DS . 'mage' . DS . 'cookies.js',

    MAGENTO_SKIN_BASE_JS_DIR . DS . 'bundle.js',
    MAGENTO_SKIN_BASE_JS_DIR . DS . 'ie6.js',
    // MAGENTO_SKIN_BASE_JS_DIR . DS . 'opcheckout.js',
    // MAGENTO_SKIN_BASE_JS_DIR . DS . 'checkout' . DS . 'review.js',

    // MAGENTO_JS_DIR . DS . 'varien' . DS . 'weee.js',
    
    //SCHRACK_JS_DIR . DS . 'jquery-1.10.2.min.js',
    // SCHRACK_JS_DIR . DS . 'jquery-1.10.2.js', // replacement for local debugging
    //SCHRACK_JS_DIR . DS . 'jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.min.js', // we do not use the already minified version because this generates js syntax errors
    //SCHRACK_JS_DIR . DS . 'jquery-ui-1.10.3.custom/development-bundle/ui/i18n/jquery-ui-i18n.js',
    //SCHRACK_JS_DIR . DS . 'history.js/scripts/bundled-uncompressed/html5/jquery.history.js',
    //SCHRACK_JS_DIR . DS . 'shadowbox/shadowbox.js',
);

$command = "php \"" . THIS_DIR . DS . "packJavascript.php\" -o\"$outFile\" " . implode(' ', array_map(function($file){ return "\"".$file."\"";}, $inFiles));
echo "$command\n\n";

system($command);
<?php

/*
 * this packs the extlib.js + our own javascripts to the final packed js file
 * extlib.js + (our js files) -> allPacked.js
 *
 * DANGER WILL ROBINSON DANGER:
 * this is the 2nd step of a 2-step process involving packExtlibJavascripts.php + packAllJavascripts.php!!!!!!!
 */

$append = ( isset($argv[1]) && $argv[1] === '-a' ); # don't pack, just append

define('UP', '..');
define('DS', DIRECTORY_SEPARATOR);

define('THIS_DIR', dirname(__FILE__));
define('TOP_DIR', THIS_DIR . DS . UP . DS . UP);
define('MAGENTO_JS_DIR', TOP_DIR . DS . 'js');
define('MAGENTO_JS_PROTOTYPE_DIR', MAGENTO_JS_DIR . DS . 'prototype');
define('MAGENTO_JS_SCRIPTACULOUS_DIR', MAGENTO_JS_DIR . DS . 'scriptaculous');
define('MAGENTO_JS_VARIEN_DIR', MAGENTO_JS_DIR . DS . 'varien');
define('MAGENTO_JS_MAGE_DIR', MAGENTO_JS_DIR . DS . 'mage');

define('SCHRACK_JS_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'schrack' . DS . 'default' . DS . 'schrackdesign' . DS . 'Public' . DS . 'Javascript');

$outFile = SCHRACK_JS_DIR . DS . 'allPacked.js';

$inFiles = array(
    SCHRACK_JS_DIR . DS . 'extlibPacked.js',

    
    SCHRACK_JS_DIR . DS . 'rwd'. DS .'jquery-3.1.1.min.js',
    SCHRACK_JS_DIR . DS  . 'rwd'. DS .'bootstrap.js',
    SCHRACK_JS_DIR . DS  . 'rwd'. DS .'noconflict.js',
    SCHRACK_JS_DIR . DS  . 'rwd'. DS .'jquery.bxslider.min.js',
    SCHRACK_JS_DIR . DS  . 'rwd'. DS .'commonJs.js',
    SCHRACK_JS_DIR . DS . 'application.js',
    SCHRACK_JS_DIR . DS . 'quickadd.js',
    SCHRACK_JS_DIR . DS . 'ListRequestManager.js',
    SCHRACK_JS_DIR . DS . 'dropdown-menu.js',
    SCHRACK_JS_DIR . DS . 'jquery.hoverIntent.js',
    SCHRACK_JS_DIR . DS . 'app.js',
    SCHRACK_JS_DIR . DS . 'login.js',
    
);

if ( $append ) {
    $command = "php \"" . THIS_DIR . DS . "packJavascript.php\" -a -o\"$outFile\" " . implode(' ', array_map(function($file){ return "\"".$file."\"";}, $inFiles));
} else {
    $command = "php \"" . THIS_DIR . DS . "packJavascript.php\" -o\"$outFile\" " . implode(' ', array_map(function($file){ return "\"".$file."\"";}, $inFiles));
}
echo "$command\n\n";

system($command);

<?php

$append = ( isset($argv[1]) && $argv[1] === '-a' ); # don't pack, just append

define('UP', '..');
define('DS', DIRECTORY_SEPARATOR);

define('THIS_DIR', dirname(__FILE__));
define('TOP_DIR', THIS_DIR. DS. UP . DS . UP);
define('MAGENTO_JS_DIR', TOP_DIR . DS . 'js');
define('MAGENTO_CSS_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'base' . DS . 'default' . DS . 'css');
define('SCHRACK_JS_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'schrack' . DS . 'default' . DS . 'schrackdesign' . DS . 'Public' . DS . 'Javascript');
define('SCHRACK_CSS_DIR', TOP_DIR . DS . 'skin' . DS . 'frontend' . DS . 'schrack' . DS . 'default' . DS . 'schrackdesign' . DS . 'Public' . DS . 'Stylesheets');

$outFile = SCHRACK_CSS_DIR . DS . 'allPacked.css';

$inFiles = array(
    array('filename' => MAGENTO_JS_DIR . DS . 'calendar' . DS . 'calendar-win2k-1.css', 'compile' => false),
    array('filename' => MAGENTO_CSS_DIR . DS . 'widgets.css', 'compile' => false),

    array('filename' => SCHRACK_JS_DIR . DS . 'shadowbox' . DS . 'shadowbox.css', 'compile' => false),
    array('filename' => SCHRACK_CSS_DIR . DS . 'app.css', 'compile' => false),
    array('filename' => SCHRACK_JS_DIR . DS . 'jquery-ui-1.10.3.custom' . DS . 'css' . DS . 'schrack-theme' . DS . 'jquery-ui-1.10.3.custom.min.css', 'compile' => false),
);


$ofh = @fopen($outFile, 'w');
if (!$ofh) {
    throw new Exception('oida cannot write to ' . $outFile);
}
try {
    fwrite($ofh,  '/* ' . date("Y-m-d H:i:s") . ' */ ' . PHP_EOL);

    foreach ($inFiles as $inFile) {
        if ( $inFile['compile'] && !$append ) {
            echo "packing {$inFile['filename']}...\n";
            $command = 'sass --scss "' . $inFile['filename'] . '" -t compressed';
            $content = system($command);
        } else {
            echo "appending {$inFile['filename']}...\n";
            $content = file_get_contents($inFile['filename']);
        }
        fwrite($ofh, "\n" . '/* ' .  basename($inFile['filename']) . ' */' . "\n");
        fwrite($ofh, $content);
    }

    fclose($ofh);
} catch (Exception $e) {
    echo "\n\nAn error occurred: " . $e->getMessage() . "\n\n";
}

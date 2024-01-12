<?php

define('DS', DIRECTORY_SEPARATOR);

require_once dirname(__FILE__) . DS . '..' . DS . '..' . DS . 'lib' . DS . 'JShrink' . DS . 'Minifier.php';

$options = getopt('ao:');

$append = ( isset($options['a']) );

if ( !isset($options['o']) ) {
    printUsageAndDie('No output file given');
}

if ( count($argv) < 3 ) {
    printUsageAndDie('No input file(s) given');
}

// Note: we use $argv here, not $options, because $options will not contain additional unnamed parameters
if ( $append ) {
    $inFiles = array_splice($argv, 3);
} else {
    $inFiles = array_splice($argv, 2);
}

$outFile = $options['o'];



$ofh = @fopen($outFile, 'w');
if (!$ofh) {
    throw new Exception('oida cannot write to ' . $outFile);
}

try {
    fwrite($ofh,  '/* ' . date("Y-m-d H:i:s") . ' */ ' . PHP_EOL);

    foreach ($inFiles as $inFile) {

        if (!is_readable($inFile)) {
            throw new Exception('oida file does not exist: ' . $inFile);
        }
        $ifh = @fopen($inFile, 'r');
        if (!$ifh) {
            throw new Exception('oida cannot read from ' . $inFile);
        }

        $size = filesize($inFile);
        if (!$size) {
            throw new Exception('oida file size is 0 for file ' . $inFile);
        }
        $contents = fread($ifh, $size);
        fclose($ifh);

        if ( preg_match('/Packed/', $inFile) || $append ) {
            echo "copying $inFile\n";

            $newContents = $contents;
        } else {
            echo "packing $inFile\n";

            $newContents = JShrink\Minifier::minify($contents, array('filename' => basename($inFile)));
        }

        // DEBUG
        // $newContents = $contents;
        fwrite($ofh, $newContents);
    }

    fclose($ofh);
} catch (Exception $e) {
    echo "\n\nAn error occurred: " . $e->getMessage() . "\n\n";
}


function printUsageAndDie($message) {
    echo <<<EOF
    $message
Usage: php packJavascript.php -o<output file> <input file>[ <input file>...]

EOF;
    die;
}

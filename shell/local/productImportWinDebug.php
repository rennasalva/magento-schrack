<?php

$winDebug = true;

ini_set('memory_limit', '2048M');

require_once 'productImport.php';


class Schracklive_Shell_ProductImportWinDebug extends Schracklive_Shell_ProductImport {

	public function __construct() {
        $tmpDir = 'C:\\tmp';
        if ( ! is_dir($tmpDir) ) {
            $tmpDir = sys_get_temp_dir();
        }
        echo 'XXX Temp Dir will be set to : "'.$tmpDir.'"'.PHP_EOL;
        
        $this->_baseCmd = 'php '.__FILE__;
        
		parent::__construct($tmpDir);

	}

    protected function exec ($cmd) {
        $cmd = $this->quoteCmd($cmd);
        echo 'XXX Executing commandline by passthru(): '.$cmd, PHP_EOL;
        return passthru($cmd);
    }
    
    function quoteCmd ( $cmdLine ) {
        $newPath = str_replace('C:\Program ', '"C:\Program ', $cmdLine);
        if ( strlen($cmdLine) != strlen($newPath) ) {
            $newPath = str_replace('.php', '.php"', $newPath);
        }
        return $newPath;
    }
}


$shell = new Schracklive_Shell_ProductImportWinDebug();
$shell->run();

?>

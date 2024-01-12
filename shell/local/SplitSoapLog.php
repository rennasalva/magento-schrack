<?php

require_once 'AbstractSoapLogParser.php';

class Schracklive_Shell_SplitSoapLog extends Schracklive_Shell_AbstractSoapLogDocParser {
    private $targetDir;
    private $fn2cnt = array();

    protected function showUsage () {
        echo 'Usage: php SplitSoapLog <log file name>'.PHP_EOL;
    }

    protected function initWriting () {
        $this->targetDir = basename(reset($this->srcFileNames),'.log');
        $ext = pathinfo($this->targetDir,PATHINFO_EXTENSION);
        if ( (int) $ext > 0 ) {
            $p = strrpos($this->targetDir,$ext) - 1;
            $this->targetDir = substr($this->targetDir,0,$p);
            $this->targetDir = basename($this->targetDir,'.log');
        }
        $this->targetDir = '/tmp/SplitSoapLog/'.$this->targetDir;
        $this->rrmdir($this->targetDir);
        @mkdir($this->targetDir,0777,true);
        $this->printlnVerbose("output dir is $this->targetDir");
        if ( $this->targetDir[strlen($this->targetDir)-1] != '/' ) {
            $this->targetDir .= '/';
        }
    }

    protected function handleDocument ( DOMDocument $doc, $fullInfoLine, $ts ) {
        echo '.';
        $fn = $this->getWebserviceFunctionName($doc);
        $p = strpos($fullInfoLine,',');
        if ( ! $p ) {
            $p = strlen($fullInfoLine);
        }
        $fileName = substr($fullInfoLine,0,$p);
        $fileName = str_replace(' ','_',$fileName);
        $fileName = str_replace(array(':','-','='),'',$fileName);
        $fileName = str_replace('UTC_id','',$fileName);
        $fileName = str_replace('UTC_','',$fileName);
        while ( substr_count($fileName,'_') > 2 ) {
            $p = strrpos($fileName,'_');
            $fileName = substr($fileName,0,$p);
        }
        $fileName = $fn.'_'.$fileName;
        if ( isset($this->fn2cnt[$fileName]) ) {
            $this->fn2cnt[$fileName] = $this->fn2cnt[$fileName] + 1;
        } else {
            $this->fn2cnt[$fileName] = 1;
        }
        $fileName .= ("_" . sprintf("%03d",$this->fn2cnt[$fileName]));
        $fileName = $this->targetDir.$fileName.'.xml';
        $doc->save($fileName);
    }
}

(new Schracklive_Shell_SplitSoapLog())->run($argv);

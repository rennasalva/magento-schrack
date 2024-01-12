<?php

require_once 'shell.php';

class Schracklive_Shell_MkAppStat extends Schracklive_Shell{

    private $fpOut, $logdir;

    public function run () {
        $this->countryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        $outName = "/tmp/appstat_" . $this->countryCode . ".csv";
        $this->fpOut = fopen($outName,"w");
        $this->logdir = Mage::getBaseDir('log');

        $files = scandir($this->logdir);
        $filesToUse = array();
        $lastfile = null;
        foreach ( $files as $file ) {
            if ( substr($file,0,43) == 'schracklive_rest_server_iphone_response.log' ) {
                if ( $file == 'schracklive_rest_server_iphone_response.log' ) {
                    $lastfile = $file;
                } else {
                    $filesToUse[] = $file;
                }
            }
        }
        if ( $lastfile ) {
            $filesToUse[] = $lastfile;
        }

        foreach ( $filesToUse as $file ) {
            $this->handleLogFile($this->logdir . DS . $file);
        }

        fclose($this->fpOut);
    }

    private function handleLogFile ( $logFile ) {
        if ( ($isGZ = substr($logFile,-3) == '.gz') ) {
            $file = $this->ungz($logFile);
        } else {
            $file = $logFile;
        }

        $fpIn = fopen($file,"r");
        $cnt = 1000;
        $outAr = null;
        while ( ($line = fgets($fpIn)) ) {
            $line = rtrim($line);
            if ( substr($line,0,5) == "2019-" ) {
                $cnt = 1;
            }
            switch ( $cnt ) {
                case 1 :
                    $outAr = explode(" ",$line);
                    $outAr[] = $this->countryCode;
                    break;
                case 2 :
                    $outAr[] = explode("&",explode("=",$line)[1])[0];
                    break;
                case 3 :
                    $outAr[] = $line;
                    $outline = implode(";",$outAr);
                    $outline .= PHP_EOL;
                    fputs($this->fpOut,$outline);
                    echo $outline;
                    break;
            }
            ++$cnt;
        }
        fclose($fpIn);

        if ( $isGZ ) {
            unlink($file);
        }
    }

}

(new Schracklive_Shell_MkAppStat())->run();


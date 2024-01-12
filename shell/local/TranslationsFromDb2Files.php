<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_TranslationsFromDb2Files extends Mage_Shell_Abstract {
    var $writeConnection, $readConnection;
    var $translationTree = array();
    var $locale = null;
    var $dryRun = false;

    function __construct() {
        parent::__construct();
        if ($this->getArg('help')) {
            die($this->usageHelp());
        }
        if ($this->getArg('dry_run')) {
            $this->dryRun = true;
        }
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    public function run () {
        $this->readDb();
        foreach ( $this->translationTree as $fileName => $dbTranslations ) {
            echo $fileName . PHP_EOL;
            $fileTranslations = $this->readCsvFile($fileName);
            $changed = false;
            foreach ( $dbTranslations as $dbTransKey => $dbTransVal ) {
                if ( isset($fileTranslations[$dbTransKey]) ) {
                    if ( $fileTranslations[$dbTransKey] != $dbTransVal ) {
                        echo '  u ' . $dbTransKey . ' => ' . $dbTransVal . '(was: ' . $fileTranslations[$dbTransKey] . ')' . PHP_EOL;
                        $fileTranslations[$dbTransKey] = $dbTransVal;
                        $changed = true;
                    }
                } else {
                    echo '  i ' . $dbTransKey . ' => ' . $dbTransVal . PHP_EOL;
                    $fileTranslations[$dbTransKey] = $dbTransVal;
                    $changed = true;
                }
            }
            if ( $changed && ! $this->dryRun ) {
                $this->saveCsvFile($fileName,$fileTranslations);
            }
        }
        echo 'done.' . PHP_EOL;
    }

    private function readDb () {
        $sql = "select * from translation where file like 'Schracklive%';";
        $res = $this->readConnection->fetchAll($sql);
        foreach ( $res as $rec ) {
            if ( ! $this->locale ) {
                $this->locale = $rec['locale'];
            } else if ( $this->locale != $rec['locale'] ) {
                throw new Exception("Different locales in DB: {$this->locale} and {$rec['locale']} (translation_id = {$rec['translation_id']})");
            }
            $file = $rec['file'];
            $src = $rec['string_en'];
            $dest = $rec['string_translated'];
            if ( ! isset($this->translationTree[$file]) ) {
                $this->translationTree[$file] = array();
            }
            $this->translationTree[$file][$src] = $dest;
        }
    }

    private function readCsvFile ( $fileName ) {
        $res = array();
        $file = $this->getTranslationFilePath($fileName);
        $fp = fopen($file,'r');
        while ( $line = fgetcsv($fp) ) {
            $res[$line[0]] = $line[1];
        }
        fclose($fp);
        return $res;
    }

    private function saveCsvFile ( $fileName, $fileTranslations ) {
        ksort($fileTranslations);
        $file = $this->getTranslationFilePath($fileName);
        echo '>>> saving ' . $file . PHP_EOL;
        $fp = fopen($file,'w');
        if ( ! $fp ) {
            throw new Exception("Cannot write file '$file' !");
        }
        foreach ( $fileTranslations as $transKey => $transVal ) {
            $ar = array( $transKey, $transVal );
            $line = $this->prepareCsv($ar);
            fputs($fp,$line);
        }
        fclose($fp);
    }

    private function prepareCsv ( $vals ) {
        $res = '';
        foreach ( $vals as $v ) {
            $v = str_replace ('"', '""', $v);
            $v = '"' . $v . '"';
            if ( strlen($res) > 0 ) {
                $res .= ',';
            }
            $res .= $v;
        }
        $res .= PHP_EOL;
        return $res;
    }

    private function getTranslationFilePath ( $fileName ) {
        $file = Mage::getBaseDir('locale');
        $file.= DS.$this->locale.DS.'local'.DS.$fileName;
        if ( strncasecmp(PHP_OS, 'WIN', 3) == 0 ) {
            $file = str_replace ('Program Files (x86)', 'PROGRA~2', $file);
            $file = str_replace ('\\', '/', $file);
        }
        return $file;
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f TranslationsFromDb2Files.php [--dry_run]

--dry_run does not modify files.

USAGE;
    }
}

(new Schracklive_Shell_TranslationsFromDb2Files())->run();
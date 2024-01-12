<?php

die('This file is deprecated. Use S4YConnector.php instead.');

use com\schrack\queue\protobuf\AccountMessage;

require_once 'shell.php';

class Schracklive_Shell_ProtoFileAccountImport extends Schracklive_Shell {

    /* @var $_importer Schracklive_Account_Model_Protoimport */
    var $_importer = null;

    var $_file = null;

    var $_dump = false;
    var $_import = false;
    var $_poll = false;


    public function __construct($tempDir = null) {
        parent::__construct();

        $this->_importer = Mage::getModel('account/protoimport');

        if ($this->getArg('help')) {
            die($this->usageHelp());
        }

        if ($this->getArg('dump')) {
            $this->_dump = true;
        }
        if ($this->getArg('import')) {
            $this->_import = true;
        }
        if ($this->getArg('file')) {
            $this->_file = $this->getArg('file');
        } else if ($this->getArg('poll')) {
            $this->_poll = true;
        }


         if ( !$this->_poll && !$this->_file ) {
            die($this->usageHelp());
        }
        if ( $this->_file && $this->_poll ) {
            die($this->usageHelp());
        }
        Schracklive_Account_Model_Protoimport_Base::initProtobuf();
    }

    public function run() {
        if ( $this->_file ) {
            $msg = $this->_importer->readFileToMessage($this->_file);
            if ( $this->_dump ) {
                $codec = new \DrSlump\Protobuf\Codec\TextFormat();
                $textData = $codec->encode($msg);
                print($textData . PHP_EOL);
            }

            if ( $this->_import ) {
                $this->_importer->insertOrUpdateOrDelete($msg);
            }
        }
    }

    private function _readFileToMessage($fileName) {
        $data = @file_get_contents($fileName);
        $l = strlen($data);
        if ( $l < 1 ) {
            return null;
        }
        echo "object with size {$l} got. ========================================== <br>".PHP_EOL;
        $msg = new Message($data);
        return $msg;
    }
    
    private function getSerializeFileName () {
        $tmpDir = 'C:\\tmp';
        if ( ! is_dir($tmpDir) ) {
            $tmpDir = sys_get_temp_dir();
        }
        $res = $tmpDir . '/ProtoFileAccountImport.ser';
        return $res;
    }

    public function usageHelp() {
        return <<<USAGE
   
Usage:  php -f ProtoFileAccountImport.php -- [options]

  --file <file>                   The catalog-protobuf to import. Mandatory.
  --dump                          dumps a text representation of the given file to stdout

  --help                          this help
  
USAGE;
        /*
         * hidden because not needed and long time not used - still working???
  --serialize                     serializes the given file into temp dir (no further action)
  --unserializeAndRun             unserializes previous serialized data and runs the import with
         */
    }

}

$shell = new Schracklive_Shell_ProtoFileAccountImport();
$shell->run();

?>

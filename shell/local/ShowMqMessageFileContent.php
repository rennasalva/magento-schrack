<?php

require_once 'shell.php';

use com\schrack\queue\protobuf\Message;

class Schracklive_Shell_ShowMqMessageFileContent extends Schracklive_Shell {

    const PROMO_MAPPING_FILE = '/com/schrack/queue/protobuf/SptPromotionToShop.php';
    const DEL_PROMO_MAPPING_FILE = '/com/schrack/queue/protobuf/SptDeletePromotionToShop.php';

    private $file = '';
    private $mappingFile = self::PROMO_MAPPING_FILE;

    public function __construct ()
    {
        parent::__construct();

        if ($this->getArg('help')) {
            die($this->usageHelp());
        }
        if ($this->getArg('file')) {
            $this->file = $this->getArg('file');
        } else {
            die($this->usageHelp());
        }
        if ($this->getArg('mapping_file')) {
            $this->mappingFile = $this->getArg('mapping_file');
        }
    }

    public function run () {
        // promotion and del promotion messages are zipped!
        if ( $this->mappingFile == self::PROMO_MAPPING_FILE || $this->mappingFile == self::DEL_PROMO_MAPPING_FILE ) {
            $binContent = '';
            if ( $zip = zip_open($this->file) ) {
                if ( $zipEntry = zip_read($zip) ) { // is usually a while, but in that case we support only one entry.
                    if ( zip_entry_open($zip,$zipEntry,'r') ) {
                        while ( $buffer = zip_entry_read($zipEntry) ) {
                            $binContent .= $buffer;
                        }
                        zip_entry_close($zipEntry);
                    }
                }
                zip_close($zip);
            }
        } else {
            $binContent = file_get_contents($this->file);
        }
        if ( ! is_string($binContent) || strlen($binContent) == 0 ) {
            throw new Exception("Invalid file '" . $this->file . "' got!");
        }

        Mage::helper("schrack/protobuf")->initProtobuf();
        $libDir = Mage::getBaseDir('lib');
        require_once $libDir . $this->mappingFile;

        $protobufMessage = new Message($binContent);
        $dump = $protobufMessage->serialize(new \DrSlump\Protobuf\Codec\TextFormat());

        echo $dump;
    }


    public function usageHelp() {
        return <<<USAGE

Usage:  php -f ShowMqMessageFileContent.php --file <message_file> [--mapping_file <DrSlump php mapping file>] 

    Default mapping file is '/com/schrack/queue/protobuf/SptPromotionToShop.php'

USAGE;
    }
}

(new Schracklive_Shell_ShowMqMessageFileContent())->run();
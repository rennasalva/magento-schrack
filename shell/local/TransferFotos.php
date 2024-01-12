<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_TransferFotos extends Mage_Shell_Abstract {

    private $readConnection, $writeConnection;
    private $dryRun = true;

    function __construct () {
        parent::__construct();
        $this->dryRun = $this->getArg('dry_run');
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

	public function run() {
        if ( $this->getArg('save') !== false) {
            $this->save();
        } else if ( $this->getArg('restore') !== false) {
            $this->restore();
        } else {
            echo $this->usageHelp();
            return;
        }
        echo "\ndone.\n";
	}

	private function save () {
        $sql = " SELECT p.sku, a.url, a.label FROM catalog_product_entity p"
             . " JOIN catalog_attachment a ON p.entity_id = a.entity_id AND a.entity_type_id = 4"
             . " WHERE a.filetype = 'foto'";
        $dbRes = $this->readConnection->fetchAll($sql);
        $fp = fopen($this->getFileName(),"w");
        foreach ( $dbRes as $row ) {
            echo '.';
            fputcsv($fp,$row);
        }
        fclose($fp);
    }

	private function restore () {
        $fn = $this->getFileName();
        if ( ! is_readable($fn) ) {
            throw new Exception("File '$fn' is missing or not readable!\n");
        }
        $sql = " SELECT p.sku, a.url FROM catalog_product_entity p"
             . " JOIN catalog_attachment a ON p.entity_id = a.entity_id AND a.entity_type_id = 4"
             . " WHERE a.filetype = 'foto'";
        $dbRes = $this->readConnection->fetchAll($sql);
        $existingMap = array();
        foreach ( $dbRes as $row ) {
            $existingMap[$row['sku'] . '_' . $row['url']] = true;
        }
        $dbRes = null;

        $sku2idMap = array();
        $sql = "SELECT entity_id, sku FROM catalog_product_entity";
        $dbRes = $this->readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $sku2idMap[$row['sku']] = $row['entity_id'];
        }
        $dbRes = null;

        $i = 1;
        $fp = fopen($fn,"r");
        while (($data = fgetcsv($fp, 1024)) !== FALSE) {
            if ( ! is_array($data) || count($data) < 3 ) {
                echo "\nwrong input line #$i\n";
                continue;
            }
            $sku = $data[0];
            $url = $data[1];
            $label = $data[2];
            $key = $sku . '_' . $url;
            if ( isset($existingMap[$key]) ) {
                echo '.';
                continue;
            }
            if ( ! isset($sku2idMap[$sku]) ) {
                echo "\nSKU $sku not found, skipping...\n";
                continue;
            }
            echo "\nadding now image $url to product $sku\n";
            if ( ! $this->dryRun ) {
                $sql = " INSERT INTO catalog_attachment (entity_type_id,entity_id,filetype,url,label) "
                     . " VALUES(?,?,?,?,?)";
                $this->writeConnection->query($sql,array(4,$sku2idMap[$sku],'foto',$url,$label));
            }
            ++$i;
        }
        fclose($fp);
    }

    private function getFileName () {
        $countryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        return "/tmp/foto_transfer_$countryCode.csv";
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f TransferFotos.php save
or      php -f TransferFotos.php [dry_run] restore

USAGE;
    }
}

$shell = new Schracklive_Shell_TransferFotos();
$shell->run();

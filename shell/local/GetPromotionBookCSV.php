<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_GetPromotionBookCSV extends Mage_Shell_Abstract
{
    private $readConnection, $writeConnection;
    private $mailinglistID = false;
    private $oldMailinglistID = false;
    private $validUntil = false;
    private $country = false;

    function __construct () {
        parent::__construct();
        // --mailinglist_id <ID> [--shop_mailinglist_id <ID>] --valid_until <YYYY-MM-DD>
        $this->sourceFile = $this->getArg('source_file');
        if ( $this->getArg('mailinglist_id') ) {
            $this->mailinglistID = $this->getArg('mailinglist_id');
            if ( $this->mailinglistID == '0-1' ) $this->mailinglistID = -1;
            if ( ! is_numeric($this->mailinglistID) ) {
                throw new Exception("Invalid mailinglist id {$this->mailinglistID} !");
            }
        }
        if ( $this->getArg('shop_mailinglist_id') ) {
            $this->oldMailinglistID = $this->getArg('shop_mailinglist_id');
            if ( $this->oldMailinglistID == '0-1' ) $this->oldMailinglistID = -1;
            if ( ! is_numeric($this->oldMailinglistID) ) {
                throw new Exception("Invalid mailinglist id {$this->oldMailinglistID} !");
            }
        }
        if ( $this->getArg('valid_until') ) {
            $this->validUntil = $this->getArg('valid_until');
        }

        if ( ! $this->validUntil || ! $this->mailinglistID ) {
            echo "Missing parameter!" . PHP_EOL;
            die($this->usageHelp());
        }

        $this->country = strtoupper(Mage::getStoreConfig('schrack/general/country'));

        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function run () {
        if ( $this->oldMailinglistID ) {
            $this->changeMailinglist();
        }
        $csv = array(array('country','mailinglist_id','customer_id','contact_number','valid_until','link'));
        $sql = "SELECT * FROM promotion_book WHERE mailinglist_id = ?;";
        $res = $this->readConnection->fetchAll($sql,array($this->mailinglistID));
        if ( count($res) < 1 ) {
            echo "Mailinglist id {$this->mailinglistID} not found!" . PHP_EOL;
            die();
        }
        foreach ( $res as $row ) {
            echo '.';
            if ( ! isset($row['contact_number']) ) {
                $row['contact_number'] = -1;
            }
            $csv[] = array($this->country,$this->mailinglistID,$row['customer_id'],$row['contact_number'],$this->validUntil,$row['link']);
        }
        echo PHP_EOL;

        $fn = '/tmp/promotionbook_links_' . $this->country . '_' . $this->mailinglistID . '.csv';
        echo "writing now '$fn'..." . PHP_EOL;
        $fp = fopen($fn,'w');
        foreach ( $csv as $fields ) {
            fputcsv($fp,$fields);
        }
        fclose($fp);
        echo "done." . PHP_EOL;
    }

    private function changeMailinglist () {
        $this->writeConnection->beginTransaction();
        try {
            $sql = "UPDATE promotion_book_image SET mailinglist_id = ? WHERE mailinglist_id = ?";
            $this->writeConnection->query($sql,array($this->mailinglistID,$this->oldMailinglistID));
            $sql = "UPDATE promotion_book SET mailinglist_id = ? WHERE mailinglist_id = ?";
            $this->writeConnection->query($sql,array($this->mailinglistID,$this->oldMailinglistID));
            $this->writeConnection->commit();
        } catch ( Exception $ex ) {
            $this->writeConnection->rollback();
            throw new Exception('Could not change mailinglist!',0,$ex);
        }
    }
    
    public function usageHelp ()
    {
        return <<<USAGE

       php GetPromotionBookCSV.php --mailinglist_id <ID> [--shop_mailinglist_id <ID>] --valid_until <YYYY-MM-DD>


(Use 0-1 for passign id -1)"


USAGE;
    }
}

(new Schracklive_Shell_GetPromotionBookCSV())->run();

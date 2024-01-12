<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_AddPromotionBooks extends Mage_Shell_Abstract
{
    private $sourceFile = null;
    private $mailinglistID = -1;
    private $imageLink = null;
    private $readConnection, $writeConnection;
    private $testContact = null;

    function __construct () {
        parent::__construct();
        $this->sourceFile = $this->getArg('source_file');
        if ( $this->getArg('mailinglist_id') ) {
            $this->mailinglistID = $this->getArg('mailinglist_id');
            if ( ! is_numeric($this->mailinglistID) ) {
                throw new Exception("Invalid mailinglist id {$this->mailinglistID} !");
            }
        }
        if ( $this->getArg('image_link') ) {
            $this->imageLink = $this->getArg('image_link');
        }
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function run () {
        if ( $this->imageLink ) {
            $this->runImage();
        } else if ( $this->sourceFile ) {
            $this->runPdf();
        } else {
            $this->usageHelp();
        }
        echo "done. Test with contact {$this->testContact}" . PHP_EOL;
    }

    private function runImage () {
        $where = " WHERE mailinglist_id = {$this->mailinglistID};";
        $sql = "SELECT COUNT(*) FROM promotion_book_image " . $where;
        $res = $this->readConnection->fetchOne($sql);
        if ( $res != 1 ) { // insert
            $sql = "INSERT INTO promotion_book_image (mailinglist_id, image_link) VALUES ({$this->mailinglistID}, '{$this->imageLink}');";
        } else {
            $sql = "UPDATE promotion_book_image SET image_link = '{$this->imageLink}' " . $where;
        }
        $this->writeConnection->query($sql);
    }
    
    private function runPdf () {
        $sql = "SELECT COUNT(*) FROM promotion_book_image WHERE mailinglist_id = {$this->mailinglistID};";
        $res = $this->readConnection->fetchOne($sql);
        if ( $res != 1 ) {
            throw new Exception("No such mailinglist_id ({$this->mailinglistID}) found in table promotion_book_image!");
        }

        if ( $this->sourceFile ) {
            echo 'Source file: ' . $this->sourceFile . PHP_EOL;
            $fp = fopen($this->sourceFile, 'r');
            if ( $fp ) {
                $data = fread($fp, filesize($this->sourceFile));
                fclose($fp);
                $tok = strtok($data,"\r\n");
                while ( $tok !== false ) {
                    $row = explode(chr(9),$tok);
                    $this->insertUpdate($row[0],$row[1],$row[2]);
                    $tok = strtok("\r\n");
                }
            }
        }
    }

    private function insertUpdate ( $custNo, $contactNo, $link ) {
        $mid = $this->mailinglistID;
        echo $custNo . '|' . $contactNo . '|' . $mid . ' : ' . $link;
        $where = " WHERE customer_id = '$custNo' AND contact_number = $contactNo AND mailinglist_id = $mid;";
        $sql = "SELECT COUNT(*) FROM promotion_book " . $where;
        $res = $this->readConnection->fetchOne($sql);
        if ( $res > 1 ) { // error
            throw new Exception("Too many already existing records fond!");
        } else if ( $res == 1 ) { // update
            $sql = "UPDATE promotion_book SET link = '$link' " . $where;
            echo ' U ';
        } else { // insert
            $sql = "INSERT INTO promotion_book (customer_id, contact_number, mailinglist_id, link) VALUES('$custNo',$contactNo,$mid,'$link');";
            echo ' I ';
        }
        $this->writeConnection->query($sql);
        if ( ! $this->testContact ) {
            $sql = " SELECT e.email FROM customer_entity e"
                 . " LEFT JOIN customer_entity_varchar a ON e.entity_id = a.entity_id AND a.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'confirmation')"
                 . " WHERE e.schrack_wws_customer_id = $custNo AND e.schrack_wws_contact_number = $contactNo AND e.email NOT LIKE '%schrack%' AND (a.value IS NULL OR a.value < ' ') AND schrack_confirmed_dsgvo = 1;";
            $this->testContact = $this->readConnection->fetchOne($sql);
            $sql = " SELECT count(*) FROM customer_dsgvo WHERE email = ?";
            $cnt = $this->readConnection->fetchOne($sql,$this->testContact);
            if ( $cnt < 1 )
                $this->testContact = null;
        }
        echo PHP_EOL;
    }

    public function usageHelp ()
    {
        return <<<USAGE

       php AddPromotionBooks.php [--mailinglist_id <ID>] --source_file <File>
       
or

       php AddPromotionBooks.php --mailinglist_id <ID> --image_link <valid http link>



USAGE;
    }
}

(new Schracklive_Shell_AddPromotionBooks())->run();

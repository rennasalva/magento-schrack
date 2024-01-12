<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_UpdateMoPrices extends Mage_Shell_Abstract {

    private $dryRun = true;
    private $sourceFile = null;
    private $czMulti = 1.00;
    private $readConnection, $writeConnection;

    function __construct () {
        parent::__construct();
        $this->sourceFile = $this->getArg('source_file');
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('commondb_write');
    }

    public function run() {
        //----------------------------------------------------------------------
        // !!!!!!! ATTENTION !!!!!!!!! ATTENTION !!!!!!!!! ATTENTNION !!!!!!!!!!
        //----------------------------------------------------------------------
        //        has to be checked in the provided source excel file
        //----------------------------------------------------------------------
        //        **** SAMPLE DATA'S ****
        //----------------------------------------------------------------------
        //        Products which are no longer available
        //        --------------------------------------------------------------
        //        $toDeleteArray = array('MO800F05','MO830N20','MO834N20',etc.);
        //----------------------------------------------------------------------
        //        New products which will be added to catalogue
        //        --------------------------------------------------------------
        //        $newArray = array('MO800F35','MO800F36','MO800F37');
        //----------------------------------------------------------------------
        //----------------------------------------------- last update 11.07.2022
        $toDeleteArray = array();
        $newArray = array();
        //----------------------------------------------------------------------
        // !!!!!!! ATTENTION !!!!!!!!! ATTENTION !!!!!!!!! ATTENTNION !!!!!!!!!!
        //----------------------------------------------------------------------
        if ( ! $this->sourceFile || ! file_exists($this->sourceFile) ) {
            echo "Source file name invalid or not found" . PHP_EOL;
            die($this->usageHelp());
        }
        //----------------------------------------------------------------------
        $msg = "Did you make an backup before that sensible task";
        $answer = $this->get_yn_input($msg);
        //----------------------------------------------------------------------
        if(!$answer){
            $msg = "You have to make one you silly boy!\n";
            die($msg);
        } else {
            $msg = "Well done!\n".
                   "Script execution starts....\n" .
                   "Enter CZ price update multiplier in percent?:\n".
                   "(If no value will be entered the update will not be executed)\n";
            echo $msg;
            //----------------------------------------------------- reads number
            $number = -23;
            fscanf(STDIN, "%d\n", $number);
            $number = intval($number);
            $msg = "CZ prices will be increased by: ".$number."%\n\n";
            if($number == -23){
                $msg = "You considered to not update CZ prices!\n";
            }
            //--------------------------------------------------- error handling
            if($number == 0){
                die("Reconsider man!\n");
            } elseif($number >= 100){
                die("i doub't it!\n");
            } else{
                echo $msg;
                //--------------------------- set multiplier only if set by user
                if($number != -23){
                    $zero = "";
                    if($number < 10){ $zero = 0; }
                    $this->czMulti = floatval("1.".$zero.$number);
                }
                //-------------------------------------- only logging or execute
                $msg = "Do you wanna execute";
                $answer = $this->get_yn_input($msg);
                //--------------------------------------------------------------
                if(!$answer) {
                    $msg = "Script execution will not affect data's!\n";
                } else {
                    $msg = "You've reached point of no return!\n";
                    $this->dryRun = false;
                }
                //--------------------------------------------------------------
                echo $msg;
            }
        }
        //----------------------------------------------------------------------

        $warning = 0;
        //-------------------------------------------------------- READ CSV File
        // TODO: Take shared XLS file (from Klaus Mader) and delete
        // collumns excepting "Schrack" + "Listprice"
        // export to csv.file whith ; sepperator
        // save csv file to backend server and execute UpdateMoPrice.php
        //----------------------------------------------------------------------
        // !!!!!!! ATTENTION !!!!!!!!! ATTENTION !!!!!!!!! ATTENTNION !!!!!!!!!!
        //----------------------------------------------------------------------
        //      To adjust prices for CZ/CS
        //      you have to uncomment the "UPDATE PRICING FOR CZ(CS)" block
        //      To avoid multiple updates take care to comment this block
        //      back in after succesful update.
        //----------------------------------------------------------------------
        //      before update don't forget to make an sql dump
        //================================================================== CMD
        /*      sudo mysqldump magento_common tx_schrackcbconf_acb tx_schrackcbconf_acb_base_accessories tx_schrackcbconf_acb_base_accessories_lang  tx_schrackcbconf_acb_base_accessory_types tx_schrackcbconf_acb_optional_accessories tx_schrackcbconf_acb_optional_accessories_lang tx_schrackcbconf_acb_properties tx_schrackcbconf_acb_property_options tx_schrackcbconf_acb_property_options_lang tx_schrackcbconf_prices > mocalc_dump_20220711 */
        //======================================================================
        // !!!!!!! ATTENTION !!!!!!!!! ATTENTION !!!!!!!!! ATTENTNION !!!!!!!!!!
        //----------------------------------------------------------------------
        $newData = array();
        $data = file_get_contents($this->sourceFile);
        $data = explode("\n",$data);
        foreach ( $data as $row ) {
            $row = trim($row);
            if ( strlen($row) < 7 || substr($row,2,1) == '9' || strpos($row,'Schrack') !== false ) {
                continue;
            }
            $fields = explode(';',$row);
            $partNo = substr($fields[0],2,6);
            $price = $fields[1];
            $price = str_replace('.','',$price);
            $price = (float) str_replace(',','.',$price);
            $newData[$partNo]= $price;
        }
        //----------------------------------------------------------------------
        $acbs = array();
        $sql = "SELECT part_number, price FROM tx_schrackcbconf_acb";
        $dbRes = $this->readConnection->fetchAll($sql);
        //----------------------------------------------------------------------
        foreach ( $dbRes as $row ) {
            $partNo = $row['part_number'];
            $acbs[$partNo] = (float) $row['price'];
            if ( ! isset($newData[$partNo]) ) {
                echo "WARNING: No new price for part no $partNo (main component)!\n";
                ++$warning;
            }
        }
        //----------------------------------------------------------------------
        $baseAccs = array();
        $sql = "SELECT part_number, price FROM tx_schrackcbconf_acb_base_accessories";
        $dbRes = $this->readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $partNo = $row['part_number'];
            $baseAccs[$partNo] = (float) $row['price'];
            if ( ! isset($newData[$partNo]) ) {
                echo "WARNING: No new price for part no $partNo (base accessory)!\n";
                ++$warning;
            }
        }
        //----------------------------------------------------------------------
        $optAccs = array();
        $sql = "SELECT part_number, price FROM tx_schrackcbconf_acb_optional_accessories";
        $dbRes = $this->readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $partNo = substr($row['part_number'],2);
            $optAccs[$partNo] = (float) $row['price'];
            if ( ! isset($newData[$partNo]) ) {
                $toDelete = in_array($row['part_number'],$toDeleteArray);
                if ( ! $toDelete ) {
                    echo "WARNING: No new price for part no $partNo (optional accessory)!\n";
                    ++$warning;
                }
            }
        }
        //----------------------------------------------------------------------
        if ( ! $this->dryRun ) {
            $this->writeConnection->beginTransaction();
        }
        //--------------------- updating regular tables with new prices from csv
        try {
            foreach ( $newData as $partNo => $price ) {
                if ( isset($acbs[$partNo]) ) {
                    $this->updateRecord('tx_schrackcbconf_acb', $partNo, $acbs[$partNo], $price, 'a');
                } else if ( isset($baseAccs[$partNo]) ) {
                    $this->updateRecord('tx_schrackcbconf_acb_base_accessories', $partNo, $baseAccs[$partNo],$price, 'b');
                } else if ( isset($optAccs[$partNo]) ) {
                    $this->updateRecord('tx_schrackcbconf_acb_optional_accessories', 'MO' . $partNo,$optAccs[$partNo], $price, 'o');
                } else {
                    $toInsert = in_array('MO' . $partNo, $newArray);
                    if ( ! $toInsert ) {
                        echo "\nWARNING: No existing record for part no $partNo !\n";
                        ++$warning;
                    }
                }
            }
            //------------------------------------------------------------------
            if ( $warning > 0 ) {
                echo "\n$warning warnings!\n";
            }
            //------------------------------------------------------------------
            if ( !$this->dryRun ) {
                //------------------------------------------------------- delete
                $sql = "DELETE FROM tx_schrackcbconf_acb_optional_accessories WHERE part_number IN ('" . implode("','",$toDeleteArray) . "')";
                $this->writeConnection->query($sql);
                //**************************************************************
                //------------------------------------ UPDATE PRICING FOR CZ(CS)
                //**************************************************************
                //-------------------------------------------------- clear table
                $fullPrices = array();
                //**************************************************************
                //-------------------------------------------- MULTIPLIER for CZ
                //--------------------------------------- last update 22.07.2022
                //--------------------------------------------- increased by 12%
                //**************************************************************
                if($this->czMulti > 1.00){
                    $sql = "UPDATE `tx_schrackcbconf_prices` SET price = price * " . $this->czMulti;
                    $this->writeConnection->query($sql);
                }
                //**************************************************************
                //-------------------------- UPDATE PRICING FOR CZ(CS) ***END***
                //**************************************************************
                $this->writeConnection->commit();
            }
            //----------------------------------------------------------------------
        } catch ( Exception $ex ) {
            if ( !$this->dryRun ) {
                $this->writeConnection->rollback();
            }
            throw $ex;
        }
        //--------------------------------------------------------------- RETURN
        echo "\ndone\n";
    }

    private function get_yn_input($txt, $decide){
        $RET = false;
        echo $txt."[yes]/[no]?: ";
        $line = trim(fgets(STDIN));
        if( $line != "yes" && $line != "no" ){
            die("You have to answer with [yes] or [no]!\n");
        } else {
            switch($line):
                case "yes": $RET = true; break;
                case "no":  $RET = false; break;
            endswitch;
        }
        return $RET;
    }

    private function updateRecord ( $table, $partNo, $oldPrice, $newPrice, $progressChar ) {
        if ( $oldPrice == $newPrice ) {
            echo '-';
        } else {
            echo $progressChar;
            if ( ! $this->dryRun ) {
                $this->writeConnection->query("UPDATE $table SET price = ? WHERE part_number = ?",[$newPrice, $partNo]);
            }
        }
    }

    public function usageHelp ()
    {
        return <<<USAGE

       php UpdateMoPrices.php --source_file <File>



USAGE;
    }
}

$shell = new Schracklive_Shell_UpdateMoPrices();
$shell->run();

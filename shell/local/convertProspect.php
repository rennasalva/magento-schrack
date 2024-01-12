<?php

require_once('shell.php');

class Schracklive_Shell_ConvertProspect extends Schracklive_Shell {
    private $interactive;
    private $country;
    private $countries;
    private $wwsCustomerId;
    private $readConnection, $writeConnection;
    private $sim;
    private $override;

    function __construct ()
    {
        parent::__construct();
        //----------------------------------------------------------------------
        $this->wwsCustomerId = "all";
        $this->override = false;
        //-------------------------------------------------- available countries
        $this->countries = "%at%ba%be%bg%co%cz%de%hr%hu%nl%pl%ro%rs%ru%sa%si%sk%";
        //-------------------------------------------------------- db connection
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        //----------------------------------------------------- interactive mode
        $this->interactive = $this->getArg('interactive') ? true : false;
        //------------------------------------------------------ simulation mode
        $this->sim = $this->getArg('sim') ? true : false;
        //-------------------------------------------------------------- country
        $this->country = "";
    }


	public function run() {
        //------------------------------------------- interactive execution mode
        if($this->interactive){
            $this->get_user_input("country");
            $this->get_user_input("customer");
            if($this->wwsCustomerId != 'all' && $this->override){
                $this->override = $this->get_yn_input("Customer is no light- or full- prospect. Do you wanna override anyway?");
            }
            $this->_convertProspect();
        } else {
            //----------------------------------------- automated execution mode
            if($this->getArg('country') && (strlen($this->getArg('country')) == 2 || trim(strtolower($this->getArg('country'))) == "all") ){
                $this->country = trim(strtolower($this->getArg('country')));
                $found = strpos($this->countries, $this->country);
                if($found > 0 or $this->country == "all"){
                    if($this->country == "all"){
                        $countries = array("at","ba","be","bg","co","cz","de","hr","hu","nl","pl","ro","rs","ru","sa","si","sk");
                        for ($i = 0; $i < count($countries); $i++){
                            $this->country = $countries[$i];
                            $this->_convertProspect();
                        }
                    } else {
                        $this->_convertProspect();
                    }
                } else {
                    die ("Wrong input. Entered country '".$this->country."' not available!\n");
                }
            } else {
                $this->usageHelp();
            }
        }
	}

	protected function _convertProspect() {
        //------------------------------------------------------ SQL query build
        $select = " SELECT COUNT(*) AS customers_found FROM `magento_".
            $this->country."`.`customer_entity`";
        //----------------------------------------------------------------------
        $conversionType = $this->interactive
                        ? "user_converted"
                        : "system_converted";
        //----------------------------------------------------------------------
        $updBy = $this->interactive
               ? "shop_admin"
               : "shop_convert_prospect";
        //----------------------------------------------------------------------
        $update = " UPDATE `magento_".$this->country."`.`customer_entity` SET"
                . " `schrack_customer_type` = '".$conversionType."', updated_at = NOW(), updated_by = '".$updBy."' ";
        //----------------------------------------------------------------------
        $wws_customer_id = $this->wwsCustomerId == "all"
                         ? " IS NOT NULL AND schrack_wws_customer_id != ''"
                         : " = '".$this->wwsCustomerId."'";
        //----------------------------------------------------------------------
        $filter = " WHERE schrack_wws_customer_id"
                . $wws_customer_id
                . " AND schrack_wws_contact_number != -1"
                ;
        //----------------------------------------------------------------------
        $filter_prospect_only = " AND schrack_customer_type IN ('light-prospect', 'full-prospect')";
        //----------------------------------------------------------------------
        if ($this->wwsCustomerId != "all"){
            $filter .= " AND schrack_wws_customer_id = " . $this->wwsCustomerId;
        }
        //----------------------------------------------------------------------
        if (!$this->override){
            $filter .= $filter_prospect_only;
        }
        $filter .= ";";
        //--------------------------------------------------------------- OUTPUT
        if ($this->sim ) {
            echo "*** SIMULATION STARTED ***\n\n";
            echo $select.$filter."\n\n";
        }
        echo "--- SEARCHING FOR CUSTOMER(S) in ".$this->country." ---\n\n";
        //----------------------------------------------------------------------
        $dbRes = $this->readConnection->fetchAll($select.$filter);
        if($dbRes[0]['customers_found'] == 0){
            if ($this->sim) {
                echo "No customers found.\n";
            }
            die("No updates were performed. Bye.\n");
        } else {
            //----------------------------------------------------------- OUTPUT
            echo $dbRes[0]['customers_found']. " CUSTOMER(S) FOUND\n";
            //--------------------------------------------- collect informations
            $select = " SELECT entity_id, schrack_wws_customer_id, schrack_customer_type, email FROM `magento_".
                $this->country."`.`customer_entity`";
            $dbRes = $this->readConnection->fetchAll($select.$filter);
            $i = 1;
            //----------------------------------------------------------- OUTPUT
            foreach ($dbRes as $k => $v){
                $value = "";
                foreach ($v as $k2 => $v2) {
                    $value .= " ----- [".$k2."] => ".$v2."\n";
                }
                echo $i.". [".$k."] =================== \n";
                echo $value."\n";
                $i++;
            }
        }
        //----------------------------------------------------------------------
        if ( !$this->sim ) {
            //----------------------------------------------------------- OUTPUT
            echo "*** UPDATING CUSTOMER(S) ***\n";
            $this->writeConnection->beginTransaction();
            //------------------------------------------------------------------
            try {
                $this->writeConnection->query($update.$filter);
                $this->writeConnection->commit();
            } catch ( Exception $ex ) {
                $this->writeConnection->rollback();
                throw $ex;
            }
        } else {
            //----------------------------------------------------------- OUTPUT
            echo $update.$filter."\n";
            echo "*** SIMULATION ENDED - NO CUSTOMER(S) UPDATED ***\n";
        }
	}

    private function get_user_input($case = "none"){
        //----------------------------------------------------------------------
        if($case == "country"){
            echo "Please enter country?: ";
            //------------------------------------------------------- user input
            $this->country = trim(strtolower(fgets(STDIN)));
            $found = strpos($this->countries, $this->country);
            //------------------------------------------------------------------
            if($found == 0){
                die ("Wrong input. Entered country '".$this->country."' not found!\n");
            }
        }
        //----------------------------------------------------------------------
        if($case == "customer") {
            //------------------------------------------------------------------
            echo "To convert a single user enter WWS Customer ID ".
                 "or just hit enter to convert ".
                 "all users in '".$this->country."': ";
            //------------------------------------------------------- user input
            $wwsCustomerId = trim(fgets(STDIN));
            if(is_numeric($wwsCustomerId) && strlen($wwsCustomerId) == 6){
                //--------------------------------------------------------------
                $select = " SELECT entity_id, schrack_wws_customer_id, schrack_customer_type FROM"
                        . " `magento_".$this->country."`.`customer_entity`"
                        . " WHERE schrack_wws_customer_id = '".$wwsCustomerId . "'"
                        . " AND schrack_wws_contact_number != -1;"
                        ;
                //--------------------------------------------------------------
                $dbRes2 = $this->readConnection->fetchAll($select);
                //--------------------------------------------------------------
                $j = 0;
                echo $select."\n";
                echo $dbRes2."\n";
                echo "Entries in array = " .count($dbRes2)."\n";
                foreach ($dbRes2 as $kx => $vx){
                    $value = "";
                    foreach ($vx as $k2 => $v2) {
                        $value .= " ----- [".$k2."] => ".$v2."\n";
                    }
                    echo $j.". [".$kx."] =================== \n";
                    echo $value."\n";
                    $j++;
                }


                if(count($dbRes2) == 1) {
                    $this->wwsCustomerId = $wwsCustomerId;
                }

                if(count($dbRes2) == 1 &&
                    $dbRes2[0]['schrack_customer_type'] != 'light-prospect' &&
                    $dbRes2[0]['schrack_customer_type'] != 'full-prospect') {
                    $this->override = true;
                }

            } else {
                die ("Wrong input. Enter a correct WWS Customer ID!\n");
            }
        }
    }

    private function get_yn_input($txt){
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


    public function usageHelp() {
		return <<<USAGE
Usage:  sudo php convertProspect.php [--interactive] [--sim] [--country <country>] 

Converts one or all found wws customers which are still marked as light- or full- prospect.
The interactive option can be used for manual update of full country or single customer in country.
 
  --interactive Enables guided execution.
  --sim Simulates execution and shows result of affected data's.
  --country <country> Country has to be set in default mode.
  help                This help

USAGE;
	}

}

$shell = new Schracklive_Shell_ConvertProspect();
$shell->run();


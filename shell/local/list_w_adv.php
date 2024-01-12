<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_list_w_adv extends Mage_Shell_Abstract {

	var $_readConnection = null;
	var $_writeConnection = null;

    function __construct() {
        parent::__construct();
	    $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

	public function run() {
        $query = ' SELECT CEV.value AS firstname, CEV2.value AS lastname, CE.entity_id, CE.email, CE.schrack_user_principal_name FROM customer_entity AS CE'
               . ' LEFT JOIN customer_entity_varchar as CEV ON CEV.entity_id = CE.entity_id AND CEV.attribute_id = ( SELECT attribute_id FROM eav_attribute WHERE attribute_code = "firstname" AND entity_type_id=1 )'
               . ' LEFT JOIN customer_entity_varchar as CEV2 ON CEV2.entity_id = CE.entity_id AND CEV2.attribute_id = ( SELECT attribute_id FROM eav_attribute WHERE attribute_code = "lastname" AND entity_type_id=1 )'
               . ' WHERE group_id = 4 ORDER BY email ASC';
        $res = $this->_readConnection->fetchAll($query);
        $resString = '';

        $list = [];
        foreach($res as $key => $val){
            $resString .= $val['email']."\n";
            $x = stripos($val['email'], '@');
            $name = substr($val['email'], 0 , $x);

            if ( ! isset($list[$name]) ){
                $list[$name] = [];
            }
            $list[$name][] = $val;

        }

        $query = "SELECT distinct SUBSTRING_INDEX(advisor_principal_name, '/', 1) FROM account WHERE wws_customer_id > 0 ORDER BY advisor_principal_name ASC";
        $res = $this->_readConnection->fetchCol($query);

        $found_advisor = [];
        $wrong_email_found = [];

        foreach($res as $key => $val){
            $x = stripos($val, '@');
            $name = substr($val, 0 , $x) == '' ? 'notfound' : substr($val, 0 , $x);
            $found_advisor[$name] = $val;
        }

        $not_found = [];
        foreach($found_advisor as $key => $val) {
            if (isset($list[$key])) {
                $found = false;
                foreach($list[$key] as $key2 => $val2){
                    if($val2['email'] == $val || $val2['schrack_user_principal_name']){
                        $found = true;
                    }
                }
                if(!$found){
                    $wrong_email_found[] = '['.strtolower(Mage::getStoreConfig('schrack/general/country')).']: '. $val .' ersetzen durch: '.$val2['email'];
                }

            } else {
                $not_found[] = '['.strtolower(Mage::getStoreConfig('schrack/general/country')).']: '.$val .' nicht existent';
            }


        }

        echo "\n\n\n\n";
        echo "--------------------- not identified principal advisors ------------------------------ \n";
        print_r($not_found);

        echo "\n\n\n\n";
        echo "--------------------- WRONG EMAIL for principal advisors ------------------------------ \n";
        print_r($wrong_email_found);


		echo "\n".'DAS WARS FÜR ['.strtolower(Mage::getStoreConfig('schrack/general/country')).'] :-)' . PHP_EOL;
	}

}

$shell = new Schracklive_Shell_list_w_adv();
$shell->run();

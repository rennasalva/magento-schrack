<?php
/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 20.10.2016
 * Time: 16:07
 */ 
class Schracklive_SchrackCustomer_Model_Mysql4_Mailinglisttype extends Mage_Core_Model_Mysql4_Abstract
{

    protected function _construct () {
        $this->_init('schrackcustomer/mailinglisttype', 'entity_id');
    }

    public function loadByCode ( Schracklive_SchrackCustomer_Model_Mailinglisttype $mlType, $code ) {
        return $this->load($mlType,$code,'code');
    }

}
<?php
/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 06.08.2015
 * Time: 14:57
 */ 
class Schracklive_SchrackCustomer_Model_Mysql4_Acceptoffertracking extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct () {
        $this->_init('schrackcustomer/acceptoffertracking','tracking_id');
        $this->_connections['read'] = $this->_connections['write'] = Mage::getSingleton('core/resource')->getConnection('common_db'); // duh, haven't found a clean way of doing this for just this one resource model
    }

}
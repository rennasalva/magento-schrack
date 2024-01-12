<?php
class Schracklive_SchrackCustomer_Model_Mysql4_Tracking extends Mage_Core_Model_Mysql4_Abstract {
    protected function _construct() {
        $this->_init('schrackcustomer/tracking', 'tracking_id');
        $this->_connections['read'] = $this->_connections['write'] = Mage::getSingleton('core/resource')->getConnection('common_db'); // duh, haven't found a clean way of doing this for just this one resource model
    }
}
?>

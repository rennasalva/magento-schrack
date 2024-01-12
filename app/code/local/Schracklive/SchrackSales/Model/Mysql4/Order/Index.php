<?php

class Schracklive_SchrackSales_Model_Mysql4_Order_Index extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {   
        $this->_init('schracksales/order_index', 'entity_id');
    }
}

?>

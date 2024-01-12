<?php

class Schracklive_SchrackSales_Model_Mysql4_Order_Index_Position extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {   
        $this->_init('schracksales/order_index_position', 'entity_id');
    }
}
?>

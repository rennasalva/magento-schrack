<?php

class Schracklive_SchrackSales_Model_Order_Index extends Mage_Core_Model_Abstract {
    public function _construct() {
        parent::_construct();
        $this->_init('schracksales/order_index');
    }
}

?>

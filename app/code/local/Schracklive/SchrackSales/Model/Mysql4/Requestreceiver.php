<?php

class Schracklive_SchrackSales_Model_Mysql4_Requestreceiver extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {   
        $this->_init('schracksales/requestreceiver', 'receiver_id');
    }
}

?>

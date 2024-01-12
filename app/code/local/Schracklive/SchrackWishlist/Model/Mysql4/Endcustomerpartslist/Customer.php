<?php

class Schracklive_SchrackWishlist_Model_Mysql4_Endcustomerpartslist_Customer extends Mage_Core_Model_Mysql4_Abstract {
    protected function _construct() {
        $this->_init('schrackwishlist/endcustomerpartslist_customer', 'id_key');
        $this->_isPkAutoIncrement = false;
    }
}
?>

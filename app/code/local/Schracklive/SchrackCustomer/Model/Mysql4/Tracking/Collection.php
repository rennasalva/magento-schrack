<?php

class Schracklive_SchrackCustomer_Model_Mysql4_Tracking_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource
     */
    protected function _construct()
    {
        $this->_init('schrackcustomer/tracking');
    }
}

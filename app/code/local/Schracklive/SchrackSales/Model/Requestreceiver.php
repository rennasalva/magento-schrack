<?php

class Schracklive_SchrackSales_Model_Requestreceiver extends Mage_Core_Model_Abstract {

    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schracksales/requestreceiver');
    }

}
?>
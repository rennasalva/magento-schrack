<?php

class Schracklive_SchrackPaypal_Model_Api_Standard extends Mage_Paypal_Model_Api_Standard {
    function __construct() {
        parent::__construct();
        $this->_globalMap['invoice'] = 'schrack_wws_order_number';
    }
}

?>

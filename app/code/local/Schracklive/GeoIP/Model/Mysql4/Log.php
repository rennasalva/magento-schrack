<?php

class Schracklive_GeoIP_Model_Mysql4_Log extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct()
    {   
        $this->_init('geoip/log', 'log_id');
    }
}

?>
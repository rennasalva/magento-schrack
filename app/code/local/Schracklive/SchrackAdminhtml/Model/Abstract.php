<?php

abstract class Schracklive_SchrackAdminhtml_Model_Abstract extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('schrackadminhtml/admin');
    }

    public function getConfigParam($param)
    {
        return Mage::getStoreConfig('schrackadminhtml/' . $param, $this->getStore());
    }


}
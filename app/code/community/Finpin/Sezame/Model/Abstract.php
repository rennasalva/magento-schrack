<?php

abstract class Finpin_Sezame_Model_Abstract extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('sezame/admin');
    }

    public function getConfigParam($param)
    {
        return Mage::getStoreConfig('sezame/' . $param, $this->getStore());
    }


}
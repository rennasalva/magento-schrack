<?php

class Schracklive_Schrack_Model_StandardRouter extends Mage_Core_Controller_Varien_Router_Standard {

    protected function _shouldBeSecure($path)
    {
        return     Mage::getStoreConfigFlag('web/secure/use_in_frontend')
                && substr(Mage::getStoreConfig('web/secure/base_url'), 0, 5) == 'https'
                && Mage::getConfig()->shouldUrlBeSecure($path);
    }

}
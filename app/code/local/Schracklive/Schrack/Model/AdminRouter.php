<?php

class Schracklive_Schrack_Model_AdminRouter extends Mage_Core_Controller_Varien_Router_Admin {

    protected function _shouldBeSecure($path) {
        return  Mage::getStoreConfigFlag('web/secure/use_in_adminhtml', Mage_Core_Model_App::ADMIN_STORE_ID)
                && substr((string)Mage::getConfig()->getNode('default/web/secure/base_url'), 0, 5) === 'https';
    }

}
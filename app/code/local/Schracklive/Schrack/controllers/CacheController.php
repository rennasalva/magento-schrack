<?php

class Schracklive_Schrack_CacheController extends Mage_Core_Controller_Front_Action {

    public function flushAction () {
        Mage::log('Cache FLushed Succesfully', null, 'Cachesuccess.log');
		Mage::app()->getCacheInstance()->flush();
        die("OK");
    }

    public function cleanAction () {
		Mage::app()->cleanCache();
        die("OK");
    }

    public function setSearchBarFlushedAction() {
        if ( !$this->_validateFormKey()) {
            // die('key wrong'); // Should not be communicated to foreigners (only internal use)
            die('');
        }

        if (!$this->getRequest()->isAjax()) {
            // die('ajax missing'); // Should not be communicated to foreigners (only internal use)
            die('');
        }
        if (intval(Mage::getStoreConfig('schrack/performance/search_bar_refresh_on_missing')) == 1) {
            Mage::app()->getCacheInstance()->flush();

            echo json_encode('flushed');
        } else {
            echo json_encode('ignored');
        }
    }
}

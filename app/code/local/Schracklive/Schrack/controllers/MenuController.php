<?php

class Schracklive_Schrack_MenuController extends Mage_Core_Controller_Front_Action {

    private $_readConnection;
    private $_storeId;
    private $_customerImage;
    private $_customerName;
    private $_customerId;
    private $_baseURL;


    public function init() {
        $this->_storeId        = Mage::app()->getStore()->getStoreId();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_baseURL        = str_replace('index.php/', '', Mage::getBaseUrl());

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            if ($customer && $customer->getId()) {
                $this->_customerId   = $customer->getSchrackWwsCustomerId();
                $this->_customerName = $customer->getName();
            } else {
                $this->_customerId = '';
            }
        } else {
            $this->_customerId   = '';
            $this->_customerName = '';
        }

        // TODO : must be implemented later, after customer is able to upload his own image
        $this->_customerImage =  $this->_baseURL . 'skin/frontend/schrack/default/schrackdesign/Public/Images/dmmuuserImg.png';
    }


    public function getMegaMenuAction() {
        if (!$this->getRequest()->isPost() || !$this->getRequest()->isAjax()) {
            die();
        }
        $this->init();
        $params = $this->getRequest()->getParams();
        $completeMegaMenuHMTL = $this->getMegaMenu();
        $responseMenuHTML = array();

        if(isset($params['responsive']) && $params['responsive'] == 'force_update') {
            $responseMenuHTML['responsive'] = $completeMegaMenuHMTL;
            echo json_encode($responseMenuHTML);
        }
        die();
    }

    public function getMegaMenuRefreshServiceAction() {
        if (!$this->getRequest()->isPost() || !$this->getRequest()->isAjax()) {
            die();
        }
        $this->init();
        $params = $this->getRequest()->getParams();
        $completeMegaMenuHMTL = $this->getMegaMenu();
        $responseMenuHTML = array();

        if (isset($params['refreshMegaMenuForceTimeLastChange'])) {
            $currentMenuTimestamp = $params['refreshMegaMenuForceTimeLastChange'];
            $latestMenuTimestamp  = Mage::helper('schrackcatalog/megamenu')->getMegamenuChangedTimestamp();

            if ($latestMenuTimestamp > $currentMenuTimestamp) {
                $responseMenuHTML['state']                   = 'changed';
                $responseMenuHTML['completeMegaMenu']        = $completeMegaMenuHMTL;
                $responseMenuHTML['latestMegaMenuTimestamp'] = $latestMenuTimestamp;
            } else {
                $responseMenuHTML['state']                   = 'unchanged';
                $responseMenuHTML['latestMegaMenuTimestamp'] = $currentMenuTimestamp;
            }
        } else {
            $responseMenuHTML['state'] = 'missing parameter : refreshMegaMenuForceTimeLastChange';
        }

        echo json_encode($responseMenuHTML);

        die();
    }

    private function getMegaMenu() {
        $cacheKey = 'megamenu_data_tree';
        $cache = Mage::app()->getCache();
        $tsCache = $cache->test($cacheKey);
        $overrideCache = false;
        if ( $tsCache ) {
            $tsMegaMenu = Mage::helper('schrackcatalog/megamenu')->getMegamenuChangedTimestamp();
            $overrideCache = ( $tsMegaMenu > $tsCache );
        }
        return $this->getCachedResultOrSaveToCache($cacheKey, $overrideCache);
    }


    private function getCachedResultOrSaveToCache($key, $overrideCache = false) {
        $lifetimeHours = 24;
        $cache = Mage::app()->getCache();
        $stringRes = $cache->load($key);
        if ($stringRes && $overrideCache == false) {
            $res = $stringRes;
        } else {
            $res = $this->getCompleteMegaMenuHTML();
            try {
                $stringRes = $res;
                $cache->save($stringRes, $key, array(), $lifetimeHours * 60 * 60);
            } catch (Exception $ex) {
                Mage::logException($ex);
                $res = '';
            }
        }
        return $res;
    }


    private function getCompleteMegaMenuHTML() {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query  = "SELECT content FROM shop_navigation";
        $query .= " WHERE type LIKE 'main_nav'";
        $query .= " ORDER BY created_at DESC";
        $query .= " LIMIT 1";
        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            $completeMegaMenuHMTL = array();
            foreach ($queryResult as $recordset) {
                $completeMegaMenuHMTL = base64_decode($recordset['content']);
            }
            return $completeMegaMenuHMTL;
        } else {
            return "No Menu Data Found";
        }
    }

}

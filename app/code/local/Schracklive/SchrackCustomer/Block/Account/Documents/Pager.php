<?php

/**
 * Html page block
 *
 * @category    Mage
 * @package    Mage_Page
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @todo        separate order, mode and pager
 */
class Schracklive_SchrackCustomer_Block_Account_Documents_Pager extends Mage_Page_Block_Html_Pager
{    
    
    protected function _construct()
    {
        parent::_construct();        
    }

    public function getCurrentPage()
    {
        $_sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
        if ($page = (int) Mage::helper('schrackcore/array')->arrayDefault($_sessionParams, $this->getPageVarName())) {
            return $page;
        }
        return 1;
    }

    private function _saveLimit($limit) {
        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            try {
                $customer = $session->getCustomer();
                if ( intval($customer->getListPagerLimit()) != intval($limit) ) {
                    $customer->setListPagerLimit($limit);
                    $customer->save();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            $session->setListPagerLimit($limit);
        }
    }

    private function _loadLimit() {
        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            try {
                $limit = $session->getCustomer()->getListPagerLimit();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            $limit = $session->gettListPagerLimit();
        }
        return $limit;
    }

    public function getLimit()
    {
        if ($this->_limit !== null) {
            return $this->_limit;
        }
        $limits = $this->getAvailableLimit();
        $_sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
        if ($limit = Mage::helper('schrackcore/array')->arrayDefault($_sessionParams, $this->getLimitVarName())) {
            if (isset($limits[$limit])) {
                $this->_saveLimit($limit);
                return $limit;
            }
        } else {
            $limit = $this->_loadLimit();
            if ( isset($limit) && $limit !== null ) {
                return $limit;
            }
        }
        $limits = array_keys($limits);
        return $limits[0];
    }

    
}

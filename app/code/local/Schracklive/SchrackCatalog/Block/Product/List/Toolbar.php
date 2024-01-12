<?php
/**
 * Created by IntelliJ IDEA.
 * User: c.friedl
 * Date: 27.08.2014
 * Time: 11:02
 */

class Schracklive_SchrackCatalog_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar {
    public function getDefaultPerPageValue() {
        $session = Mage::getSingleton('customer/session');
        $limit = null;
        if ( $session->isLoggedIn() ) {
            try {
                $limit = $session->getCustomer()->getListPagerLimit();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            $limit = $session->gettListPagerLimit();
        }
        if ( $limit !== null ) {
            return $limit;
        } else {
            return parent::getDefaultPerPageValue();
        }
    }

    public function getLimit() {
        $limit = $this->getRequest()->getParam($this->getLimitVarName());
        if ( isset($limit) ) {
            $session = Mage::getSingleton('customer/session');
            if ( $session->isLoggedIn() ) {
                try {
                    $customer = $session->getCustomer();
                    $customer->setListPagerLimit($limit)->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            } else {
                $session->setListPagerLimit($limit);
            }
        }
        return parent::getLimit();
    }
    /**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml()
    {
        $pagerBlock = $this->getChild('product_list_toolbar_pager');

        if ($pagerBlock instanceof Varien_Object) {

            /* @var $pagerBlock Mage_Page_Block_Html_Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

	    // Custom Code:
	    $localeCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
	    if (in_array($localeCode, array('ru', 'sa'))) {
	    	$boolUseContainer = true;
	    } else {
	    	$boolUseContainer = false; // Standard Value Of Magento
	    }

            $pagerBlock->setUseContainer($boolUseContainer)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setFrameLength(Mage::getStoreConfig('design/pagination/pagination_frame'))
                ->setJump(Mage::getStoreConfig('design/pagination/pagination_frame_skip'))
                ->setCollection($this->getCollection());

            return $pagerBlock->toHtml();
        }

        return '';
    }
} 
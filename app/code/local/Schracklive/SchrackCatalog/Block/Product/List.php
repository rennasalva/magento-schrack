<?php

class Schracklive_SchrackCatalog_Block_Product_List extends Mage_Catalog_Block_Product_List {

    protected function _construct() {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime'    => 3600,
        ));
        $this->setCacheKey('catalog_product_list_'. md5(serialize(Mage::app()->getRequest()->getParams())) . '_' . Mage::getSingleton('customer/session')->getSessionId());
    }

	public function getSearchHtml() {
		//return $this->getChildHtml('schrack_search');
	}
    
    public function getPriceHtml($product, $displayMinimalPrice = false, $idSuffix = '') {
        return parent::getPriceHtml($product, $displayMinimalPrice, $idSuffix);
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('product_list_toolbar');
    }
}

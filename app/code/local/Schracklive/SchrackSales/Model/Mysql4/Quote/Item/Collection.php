<?php

class Schracklive_SchrackSales_Model_Mysql4_Quote_Item_Collection extends Mage_Sales_Model_Mysql4_Quote_Item_Collection {

	protected function _afterLoad() {
		// ### $this->_afterLoadBefore();
		return parent::_afterLoad();
	}

	// a separate function is easier to handle during a Magento upgrade
	protected function _afterLoadBefore() {
		$customer=$this->getCustomer();
		$catalogInfo = Mage::helper('schrackcatalog/info');
		$qtys = array();
		foreach ($this as $item) {
			$qtys[$item->getSku()] = (int)$item->getQty();
		}
		$catalogInfo->preloadProductsInfo($this, $customer, false, $qtys);
	}

	/**
	* get the right customer via quote and only as fallback from session 
	 **/
	protected function getCustomer(){
		$customer=NULL;
		foreach ($this as $item){
			if ($customer) break;
			if ($item->getQuoteId()){
				$quote=Mage::getModel('sales/quote')->load($item->getQuoteId());
				if ($quote->getCustomer()){
					$customer=$quote->getCustomer();
				}
			}
		}
		if (!$customer) {
			$customer=Mage::getSingleton('customer/session')->getCustomer();
		}
		return $customer;
	}

}

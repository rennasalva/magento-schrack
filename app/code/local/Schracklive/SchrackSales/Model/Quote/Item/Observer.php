<?php

class Schracklive_SchrackSales_Model_Quote_Item_Observer {
	// sales_quote_item_qty_set_after
	public function setQty($observer) {
		$item = $observer->getItem();
		// @var $item Mage_Sales_Model_Quote_Item
		$customer = $item->getQuote()->getCustomer();
		// @var $customer Mage_Customer_Model_Customer
		$info = Mage::helper('schrackcatalog/info');
		// @var $info Schracklive_SchrackCatalog_Helper_Info
		$product = $item->getProduct();

		try {
			$price = $info->getTierPriceForCustomer($product, $item->getQty(), $customer);
			$basicPrice = $info->getBasicTierPriceForCustomer($product, $item->getQty(), $customer);
		} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
			$price = 0.0;
			$basicPrice = 0.0;
			$product->setSchrackInvalidity(true);
		}
		$item->setPrice($price);
		$item->setSchrackBasicPrice($basicPrice);
		$item->setSchrackSurcharge($info->getSurcharge($product));
	}
}

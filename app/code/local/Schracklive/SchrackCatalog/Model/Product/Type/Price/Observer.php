<?php

class Schracklive_SchrackCatalog_Model_Product_Type_Price_Observer {

	/**
	 * @event catalog_product_get_final_price
	 * @see Mage_Catalog_Model_Product_Type_Price
	 */
	public function setTierPriceForCustomer($observer) {
		$product = $observer->getProduct();
		$qty = $observer->getQty();
		$info = Mage::helper('schrackcatalog/info');
		/* @var $product Schracklive_SchrackCatalog_Model_Product */
		/* @var $info Schracklive_SchrackCatalog_Helper_Info */

		/* getFinalPrice() will only be called in a quote context
		 * Magento assumes the session to be active, see Mage_Catalog_Model_Product_Type_Price::_getCustomerGroupId()
		 * allow with the same mechanism the customer be set from the outside (for the Mobile module)
		 */
		$customer = $product->getCustomer();
		if (!$customer) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
		}

		try {
			$price = $info->getTierPriceForCustomer($product, $qty, $customer) / $product->getSchrackPriceunit();
		} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
			$price = 0.0;
			$product->setSchrackInvalidity(true);
		}

		$product->setFinalPrice($price);
		$product->setCalulatedFinalPrice($price);
	}

}

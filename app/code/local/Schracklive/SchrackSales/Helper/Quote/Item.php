<?php

/**
 * @see  Schracklive_SchrackCatalog_Helper_Product
 */
class Schracklive_SchrackSales_Helper_Quote_Item {

	public function hasDrums(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->hasDrums($item->getProduct(), Mage::helper('schracksales/quote')->getPickupWarehouseId($item->getQuote()));
	}

	public function getSalesUnit(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->getSalesUnit($item->getProduct(), Mage::helper('schracksales/quote')->getPickupWarehouseId($item->getQuote()));
	}

	public function getDeliveryStateText(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->getDeliveryStateText($item->getProduct());
	}

	public function getPickupStateText(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->getPickupStateText($item->getProduct(), $this->_getPickupWarehouseId($item->getQuote()));
	}

	public function getDeliveryStateImage(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->getDeliveryStateImage($item->getProduct());
	}

	public function getPickupStateImage(Schracklive_SchrackSales_Model_Quote_Item $item) {
		return Mage::helper('schrackcatalog/product')->getPickupStateImage($item->getProduct(), $this->_getPickupWarehouseId($item->getQuote()));
	}

	protected function _getPickupWarehouseId(Schracklive_SchrackSales_Model_Quote $quote) {
		$warehouseId = Mage::helper('schracksales/quote')->getPickupWarehouseId($quote);
		if (!$warehouseId) {
			$warehouseId = $quote->getCustomer()->getSchrackPickup();
		}
		if (!$warehouseId) {
			$warehouseId = Mage::getStoreConfig('schrack/general/warehouse');
		}
		return $warehouseId;
	}

}

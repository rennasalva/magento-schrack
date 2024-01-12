<?php

class Schracklive_SchrackCatalog_Model_Product_Type_Observer {

	/**
	 * Push any requested drum number into the product for later use in the cart
	 * 
	 * @event catalog_product_type_prepare_full_options
	 * @see Mage_Catalog_Model_Product_Type_Abstract
	 * @see Schracklive_SchrackCheckout_Model_Cart_Observer
	 */
	public function storeDrumNumber($observer) {
		$buyRequest = $observer->getBuyRequest(); // the query arguments
		$drumNumber = Mage::helper('schrackcatalog/drum')->getDrumNumberFromQuery($buyRequest->getData());

		if ($drumNumber && $this->_qtyIsAllowedForDrum($observer->getProduct(),$buyRequest->getQty(), $drumNumber)) {
			$observer->getProduct()->setSchrackDrumNumber($drumNumber);
		}
	}

	protected function _qtyIsAllowedForDrum($product, $qty, $drumNumber) {
		// the drum is only required to be available on any of the warehouses (available or possible)
		$warehouseIds = Mage::helper('schrackshipping')->getWarehouseIds();
		$availableDrums = Mage::helper('schrackcatalog/info')->getAvailableDrums($product, $warehouseIds, $qty);
		$possibleDrums = Mage::helper('schrackcatalog/info')->getPossibleDrums($product, $warehouseIds, $qty);
		foreach (array($availableDrums, $possibleDrums) as $drums) {
			if ($this->_isDrumAvailableInWarehouses($warehouseIds, $drums, $qty, $drumNumber)) {
				return true;
			}
		}
		return false;
	}

	protected function _isDrumAvailableInWarehouses($warehouseIds, $drums, $qty, $drumNumber) {
		foreach ($warehouseIds as $warehouseId) {
			if (isset($drums[$warehouseId]) && Mage::helper('schrackcheckout/product')->isFittingDrumAvailable($qty, $drumNumber, $drums[$warehouseId])) {
				return true;
			}
		}
		return false;
	}

}

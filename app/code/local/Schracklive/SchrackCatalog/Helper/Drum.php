<?php

class Schracklive_SchrackCatalog_Helper_Drum {

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @param                                          $qty
	 * @return array
	 */
	public function getAvailableDrumsForWarehouse(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $qty) {
		$drums = Mage::helper('schrackcatalog/info')->getAvailableDrums($product, array($warehouseId), $qty);
		return isset($drums[$warehouseId]) ? $drums[$warehouseId] : array();
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @param                                          $qty
	 * @return array
	 */
	public function getPossibleDrumsForWarehouse(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $qty) {
		$drums = Mage::helper('schrackcatalog/info')->getPossibleDrums($product, array($warehouseId), $qty);
		return isset($drums[$warehouseId]) ? $drums[$warehouseId] : array();
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @param                                          $qty
	 * @return array
	 */
	public function getDrumsForWarehouse(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $qty) {
		$drums = $this->getDrums($product, array($warehouseId), $qty);
		return isset($drums[$warehouseId]) ? $drums[$warehouseId] : array();
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param array                                    $warehouseIds
	 * @param                                          $qty
	 * @return array
	 */
	public function getDrums(Schracklive_SchrackCatalog_Model_Product $product, array $warehouseIds, $qty) {
		$drums = array();
		foreach (Mage::helper('schrackcatalog/info')->getAvailableDrums($product, $warehouseIds, $qty) as $warehouseId => $warehouseDrums) {
			foreach ($warehouseDrums as $drum) {
				$key = $drum->getWwsNumber().'|'.$drum->getSize();
				if ($drum->isLessened()) {
					$key .= '|'.$drum->getStockQty();
				}
				$drums[$warehouseId][$key] = $drum;
			}
		}
		foreach (Mage::helper('schrackcatalog/info')->getPossibleDrums($product, $warehouseIds, $qty) as $warehouseId => $warehouseDrums) {
			foreach ($warehouseDrums as $drum) {
				$key = $drum->getWwsNumber().'|'.$drum->getSize();
				if (!isset($drums[$warehouseId][$key])) {
					$drums[$warehouseId][$key] = $drum;
				}
			}
		}
		return $drums;
	}

	/**
	 * Sort array of drum objects by object property
	 * @param array $drums array of Schracklive_SchrackCatalog_Model_Drum objects
	 * @param string $prop property name
	 * @param string $order sort order ('asc' or 'desc')
	 * @return array
	 */
	public function sortDrums($drums, $prop, $order = 'asc') {
		//$comparer = (strtolower($order) === 'desc') ?
//			"return \$a->getData('{$prop}') > \$b->getData('{$prop}') ? 1 : -1;" :
//			"return \$a->getData('{$prop}') > \$b->getData('{$prop}') ? -1 : 1;";
		//usort($drums, create_function('$a, $b', $comparer));
        $comparer = (strtolower($order) === 'desc') ? (($a->getData('{$prop}') > $b->getData('{$prop}') ? 1 : -1) : (($a->getData('{$prop}') > $b->getData('{$prop}')) ? -1 : 1);
		usort($drums,fn($a,$b)=>$comparer);
		return $drums;
	}

	/**
	 * Parse schrack_drum_number=<no>|<qty>
	 * @param array $params HTTP query arguments
	 * @return int
	 */
	public function getDrumNumberFromQuery(array $params) {
		if (isset($params['schrack_drum_number'])) {
			list($drumNumber) = explode('|', $params['schrack_drum_number'], 2);
		} else {
			$drumNumber = 0;
		}
		return $drumNumber;
	}

    public function isDrumNumberInCartForProduct($product, $drum, $qty) {
        $cart = Mage::getSingleton('checkout/cart');
        $quote = $cart->getQuote();
        $items = Mage::getModel('sales/quote_item')->getCollection();
        $items->setQuote($quote);
        $items->addFieldToFilter('schrack_detailview_drum_number', Mage::helper('schrackcatalog/info')->getDrumSelectorName($drum) . '-' . $qty)
                        ->addFieldToFilter('product_id', $product->getId());
        return ( $items->count() > 0 );
    }
}

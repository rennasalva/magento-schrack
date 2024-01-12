<?php

class Schracklive_Wws_Helper_Cache_Availabilityinfo extends Schracklive_Wws_Helper_Cache_Abstract {

	const CACHE_LIFETIME = 300;

	protected $_prefix = 'avail';

	public function retrieve(array $skus = null, array $warehouses = null) {
		$allWarehouseIds = Mage::helper('schrackcataloginventory/stock')->getAllStockNumbers();
		if (is_null($skus)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		if ( is_null($warehouses) || $warehouses === Schracklive_Wws_Model_Action_Fetchavailability::ALL_WAREHOUSES ) {
			$warehouses = $allWarehouseIds;
		}
		$result = array(
			'infos' => array(),
			'misses' => array(),
			'success' => true,
		);
		$skuMisses = array();
		$warehouseMisses = array();
		$extendedWwarehouses = $warehouses;
		$hasOriginallyCentralDeliveryWarehouse = false;
		if ( false !== array_search(Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE,$warehouses) ) {
			$hasOriginallyCentralDeliveryWarehouse = true;
		}
		else {
			$extendedWwarehouses[] = Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE;
		}
		
		foreach ($skus as $sku) {
			foreach ($extendedWwarehouses as $warehouse) {
				$info = $this->_loadFromCache($this->_buildCacheKey($sku, $warehouse));
				if ($info) {
					if ( is_array($info) ) {
						$result['infos'][$sku][$warehouse] = $info;
					} else {
						$result['infos'][$sku][$warehouse] = array('qty' => 0);
					}
				} else if ( $warehouse != Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE || $hasOriginallyCentralDeliveryWarehouse ) {
					$result['success'] = false;
					$skuMisses[] = $sku;
					$warehouseMisses[] = $warehouse;
				}
			}
		}
		$skuMisses = array_unique($skuMisses);
		$warehouseMisses = array_unique($warehouseMisses);
		$result['misses']['products'] = $skuMisses;
        $result['misses']['warehouses'] = $warehouseMisses;
		return new Varien_Object($result);
	}

	public function store(array $infos, array $skus = null, array $warehouses = null) {
		if (is_null($skus) ) {
			throw new InvalidArgumentException('Missing argument.');
		}
		if ( is_null($warehouses) || $warehouses === Schracklive_Wws_Model_Action_Fetchavailability::ALL_WAREHOUSES ) {
			$warehouses =  Mage::helper('schrackshipping')->getWarehouseIds();
		}
		foreach ($skus as $sku) {
			foreach ($warehouses as $warehouseId) {
				if ( is_array($infos) && isset($infos[$sku]) && isset($infos[$sku][$warehouseId]) ) {
					$info = $infos[$sku][$warehouseId];
					$this->_storeInCache($this->_buildCacheKey($sku, $warehouseId), $info, array(), self::CACHE_LIFETIME);
				} else {
					$this->_storeInCache($this->_buildCacheKey($sku, $warehouseId), 'empty');
				}
			}
		}
		return $this;
	}

	protected function _buildCacheKey($sku = null, $warehouse = null) {
		return $this->_prefix.'_'.$sku.'_'.$warehouse;
	}

}

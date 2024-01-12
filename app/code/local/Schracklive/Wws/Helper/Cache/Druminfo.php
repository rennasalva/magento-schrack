<?php

class Schracklive_Wws_Helper_Cache_Druminfo extends Schracklive_Wws_Helper_Cache_Abstract {

	const CACHE_LIFETIME_DRUMINFO = Schracklive_Wws_Helper_Cache_Abstract::CACHE_LIFETIME;
	
	protected $_prefix = 'drum';
	
	protected function _getCacheLifeTime() {
		return self::CACHE_LIFETIME_DRUMINFO;
	}
	
	public function retrieve(array $skuQtys = null, array $warehouseIds = null) {
		if (is_null($skuQtys) || is_null($warehouseIds)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		$result = array(
			'infos' => array(),
			'misses' => array(),
			'success' => true,
		);
		$skuQtysMisses = array();
		$warehouseIdMisses = array();
		foreach ($skuQtys as $sku => $qty) {
			foreach ($warehouseIds as $warehouseId) {
				$info = $this->_loadFromCache($this->_buildCacheKey($sku,$warehouseId, $qty));
				if ($info) {
					if ( is_array($info) ) {
						// arrange as [type][warehouse] keys
						foreach ( array('available', 'possible') as $type ) {
							if ( isset($info[$type]) ) {
								$result['infos'][$sku][$type][$warehouseId] = $info[$type];
							}
						}
					}
				} else {
					$result['success'] = false;
					$skuQtysMisses[] = $sku.'|'.$qty;
					$warehouseIdMisses[] = $warehouseId;
				}
			}
		}
		foreach (array_unique($skuQtysMisses) as $skuQtysMiss) {
			list($sku, $qty) = explode('|', $skuQtysMiss);
			$result['misses']['products'][$sku] = $qty;
		}
		$result['misses']['warehouses'] = array_unique($warehouseIdMisses);
		return new Varien_Object($result);
	}

	public function store(array $infos, array $skuQtys = null, array $warehouseIds = null) {
		if (is_null($skuQtys) || is_null($warehouseIds)) {
			throw new InvalidArgumentException('Missing argument.');
		}

		// structure to store done information
		$flagTree = array();
		foreach ( $skuQtys as $sku => $qty ) {
			$flagTree[$sku] = array();
			foreach ( $warehouseIds as $warehouseId ) {
				$flagTree[$sku][$warehouseId] = false;
			}
		}

		foreach ($infos as $sku => $typeInfo) {
			$warehouseInfos = array();
			// rearrange keys from [type][warehouse] to [warehouse][type]
			foreach ($typeInfo as $type => $warehouseInfo) {
				foreach ($warehouseInfo as $warehouseId => $drumInfo) {
					$warehouseInfos[$warehouseId][$type] = $drumInfo;
				}
			}
			foreach ($warehouseInfos as $warehouseId => $warehouseInfo) {
				$this->_storeInCache($this->_buildCacheKey($sku, $warehouseId, $skuQtys[$sku]), $warehouseInfo);
				$flagTree[$sku][$warehouseId] = true;
			}
		}

		// handle un-done ones
		foreach ( $flagTree as $sku => $warehouses ) {
			foreach ( $warehouses as $warehouseId => $flag ) {
				if ( ! $flag ) {
					$this->_storeInCache($this->_buildCacheKey($sku, $warehouseId, $skuQtys[$sku]), 'empty');
				}
			}
		}
		return $this;
	}

	protected function _buildCacheKey($sku = null, $warehouseId = null, $qty = null) {
		return $this->_prefix.'_'.$sku.'_'.$warehouseId.'_'.$qty;
	}

}

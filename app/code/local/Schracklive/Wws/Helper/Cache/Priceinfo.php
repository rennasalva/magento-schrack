<?php

class Schracklive_Wws_Helper_Cache_Priceinfo extends Schracklive_Wws_Helper_Cache_Abstract {

	protected $_prefix = 'price';

	public function retrieve(Schracklive_SchrackCustomer_Model_Customer $customer=null, array $skuQtys=null) {
		if (is_null($customer) || is_null($skuQtys)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		$result = array(
			'infos' => array(),
			'misses' => array(),
			'success' => true,
		);
		$skuQtysMisses = array();
		foreach ($skuQtys as $sku => $qty) {
			$info = $this->_loadFromCache($this->_buildCacheKey($customer, $sku, $qty));
			if ($info) {
				$result['infos'][$sku] = $info;
			} else {
				$result['success'] = false;
				$result['misses']['customer'] = $customer;
				$skuQtysMisses[] = $sku.'|'.$qty;
			}
		}
		foreach (array_unique($skuQtysMisses) as $skuQtysMiss) {
			list($sku, $qty) = explode('|', $skuQtysMiss);
			$result['misses']['products'][$sku] = $qty;
		}
		return new Varien_Object($result);
	}

	public function store(array $infos, Schracklive_SchrackCustomer_Model_Customer $customer=null, array $skuQtys=null) {
		if (is_null($customer) || is_null($skuQtys)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		foreach ($skuQtys as $sku => $qty) {
			$info = isset($infos[$sku]) ? $infos[$sku] : null;
			$this->_storeInCache($this->_buildCacheKey($customer, $sku, $qty), $info);
		}
		return $this;
	}

	protected function _buildCacheKey(Schracklive_SchrackCustomer_Model_Customer $customer=null, $sku=null, $qty=null) {
		$customerNumber = Mage::helper('wws')->getWwsCustomerIdForProductInfo($customer);
		return $this->_prefix.'_'.$customerNumber.'_'.$sku.'_'.$qty;
	}

}

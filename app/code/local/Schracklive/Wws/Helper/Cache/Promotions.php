<?php

class Schracklive_Wws_Helper_Cache_Promotions extends Schracklive_Wws_Helper_Cache_Abstract {

	protected $_prefix = 'promotions';

	public function retrieve ( Schracklive_SchrackCustomer_Model_Customer $customer=null ) {
		if (is_null($customer)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		$result = array(
			'infos' => array(),
			'misses' => array(),
			'success' => true,
		);
		$info = $this->_loadFromCache($this->_buildCacheKey($customer));
		if ($info) {
			$result['infos'] = $info;
		} else {
			$result['success'] = false;
			$result['misses'] = array($customer);
		}
		return new Varien_Object($result);
	}

	public function store ( array $infos, Schracklive_SchrackCustomer_Model_Customer $customer=null ) {
		if (is_null($customer)) {
			throw new InvalidArgumentException('Missing argument.');
		}
		$this->_storeInCache($this->_buildCacheKey($customer), $infos);
		return $this;
	}

	protected function _buildCacheKey(Schracklive_SchrackCustomer_Model_Customer $customer=null, $sku=null, $qty=null) {
		$customerNumber = Mage::helper('wws')->getWwsCustomerIdForProductInfo($customer);
		return $this->_prefix.'_'.$customerNumber;
	}

}

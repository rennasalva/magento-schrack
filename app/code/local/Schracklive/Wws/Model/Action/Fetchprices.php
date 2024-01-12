<?php

class Schracklive_Wws_Model_Action_Fetchprices extends Schracklive_Wws_Model_Action_Product {

	protected $_requestName = 'getitemprice';

	/**
	 * @param array $arguments
	 */
	public function __construct(array $arguments) {
		if (array_key_exists('customer', $arguments) && array_key_exists('products', $arguments)) {
			$this->setArguments($arguments);
		}
		parent::__construct($arguments);
	}

	public function setArguments(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'customer' => 'Mage_Customer_Model_Customer',
			'products' => 'array'
				));

		$this->_requestArguments = array(
			'wwsCustomerId' => Mage::helper('wws')->getWwsCustomerIdForProductInfo($checkedArguments['customer']),
			'products' => $checkedArguments['products'],
		);
	}

	protected function _buildResponse() {
		$this->_response = $this->_request->getPriceInfos();
	}

}

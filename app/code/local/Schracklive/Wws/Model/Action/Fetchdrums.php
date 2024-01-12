<?php

class Schracklive_Wws_Model_Action_Fetchdrums extends Schracklive_Wws_Model_Action_Product {

	protected $_requestName = 'getdrumavail';

	/**
	 * @param array $arguments
	 */
	public function __construct(array $arguments) {
		if (array_key_exists('products', $arguments) && array_key_exists('warehouses', $arguments)) {
			$this->setArguments($arguments);
		}
		parent::__construct($arguments);
	}

	public function setArguments(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array('products' => 'array', 'warehouses' => 'array'));

		$this->_requestArguments = array(
			'products' => $checkedArguments['products'],
			'warehouses' => $checkedArguments['warehouses'],
		);
	}

	protected function _buildResponse() {
		$this->_response = $this->_request->getDrumInfos();
	}

}

<?php

class Schracklive_Wws_Model_Action_Fetchpromotions extends Schracklive_Wws_Model_Action_Product {

	protected $_requestName = 'getpromotions';

	/**
	 * @param array $arguments
	 */
	public function __construct(array $arguments) {
		if (array_key_exists('customer', $arguments)) {
			$this->setArguments($arguments);
		}
		parent::__construct($arguments);
	}

	public function setArguments(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'customer' => 'Mage_Customer_Model_Customer',
				));

		$this->_requestArguments = array(
			'wwsCustomerId' => Mage::helper('wws')->getWwsCustomerIdForProductInfo($checkedArguments['customer']),
		);
	}

	protected function _buildResponse() {
		$this->_response = $this->_request->getPromotionInfos();
        if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS /*&& Mage::getSingleton('customer/session')->isLoggedIn()*/ ) {
            $morePromotions = Mage::helper('schrackcatalog/preparator')->getAdditionalPromotions();
            $this->_response = array_merge($this->_response,$morePromotions);
        }
	}

}

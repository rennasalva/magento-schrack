<?php

class Schracklive_SchrackShipping_Model_Carrier_Schrackpickup extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

	protected $_code = 'schrackpickup';

	/**
	 * @param Mage_Shipping_Model_Rate_Request $request
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		$result = Mage::getModel('shipping/rate_result');
		$n = Mage::helper('schrackshipping/pickup')->getMaximumNumberOfWarehouses();
		for ($i = 1; $i <= $n; $i++) {
			if (!$this->getConfigData('id'.$i)) {
				continue;
			}

			$method = Mage::getModel('shipping/rate_result_method');

			$method->setCarrier($this->_code);
			$method->setCarrierTitle($this->getConfigData('title'));

			$method->setMethod('warehouse'.$this->getConfigData('id'.$i));
			$method->setMethodTitle($this->getConfigData('name'.$i));

			$method->setPrice(0);
			$method->setCost(0);

			$result->append($method);
		}

		return $result;
	}

	/**
	 * Get allowed shipping methods
	 *
	 * @return array
	 */
	public function getAllowedMethods() {
		$methods = array();
		$n = Mage::helper('schrackshipping/pickup')->getMaximumNumberOfWarehouses();
		for ($i = 1; $i <= $n; $i++) {
			$methods['warehouse'.$this->getConfigData('id'.$i)] = $this->getConfigData('name'.$i);
		}
		return $methods;
	}

}

<?php

class Schracklive_SchrackSales_Helper_Quote {

	public function getPickupWarehouseId(Schracklive_SchrackSales_Model_Quote $quote) {
		$address = $quote->getShippingAddress();
		if ($address) {
			$method = $address->getShippingMethod();
			if ($method) {
				return Mage::helper('schrackshipping')->getPickupWarehouseIdFromMethod($method);
			}
		}
		return 0;
	}

	public function getWarehouseId(Schracklive_SchrackSales_Model_Quote $quote) {
		$address = $quote->getShippingAddress();
		if ($address) {
			$method = $address->getShippingMethod();
			if ($method) {
				return Mage::helper('schrackshipping')->getWarehouseIdFromMethod($method);
			}
		}
		return 0;
	}

	public function getShippingType(Schracklive_SchrackSales_Model_Quote $quote) {
		$address = $quote->getShippingAddress();
		if ($address) {
			$method = $address->getShippingMethod();
			if ($method) {
				return Mage::helper('schrackshipping')->getShippingType($method);
			}
		}
		return Schracklive_SchrackShipping_Type::UNKNOWN;
	}

}

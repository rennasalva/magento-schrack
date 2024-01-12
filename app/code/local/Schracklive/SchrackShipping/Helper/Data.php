<?php

class Schracklive_SchrackShipping_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getWarehouseIds() {
		return array_unique(array(Mage::helper('schrackshipping/delivery')->getWarehouseId()) + Mage::helper('schrackshipping/pickup')->getWarehouseIds() +
            Mage::helper('schrackshipping/container')->getWarehouseIds() + Mage::helper('schrackshipping/inpost')->getWarehouseIds());
	}

	/**
	 * @param string $carrierMethod <carrier>_<method>
	 * @return int
	 */
	public function getPickupWarehouseIdFromMethod($carrierMethod) {
		if (substr($carrierMethod, 0, 14) == 'schrackpickup_') {
			return Mage::helper('schrackshipping/pickup')->getWarehouseIdFromMethod(substr($carrierMethod, 14));
		}
		return 0;
	}

    /**
	 * @param string $carrierMethod <carrier>_<method>
	 * @return int
	 */
	public function getContainerWarehouseIdFromMethod($carrierMethod) {
		if (substr($carrierMethod, 0, 17) == 'schrackcontainer_') {
			return Mage::helper('schrackshipping/container')->getWarehouseIdFromMethod($carrierMethod);
		}
		return 0;
	}

    /**
	 * @param string $carrierMethod <carrier>_<method>
	 * @return int
	 */
	public function getInpostWarehouseIdFromMethod($carrierMethod) {
		if (substr($carrierMethod, 0, 14) == 'schrackinpost_') {
			return Mage::helper('schrackshipping/inpost')->getWarehouseIdFromMethod($carrierMethod);
		}
		return 0;
	}

	/**
	 * @param string $carrierMethod <carrier>_<method>
	 * @return int 
	 */
	public function getWarehouseIdFromMethod($carrierMethod) {
		if (substr($carrierMethod, 0, 16) == 'schrackdelivery_') {
			return Mage::helper('schrackshipping/delivery')->getWarehouseId();
		} elseif (substr($carrierMethod, 0, 17) == 'schrackcontainer_') {
            return Mage::helper('schrackshipping/container')->getWarehouseId();
        } elseif (substr($carrierMethod, 0, 14) == 'schrackinpost_') {
            return Mage::helper('schrackshipping/inpost')->getWarehouseId();
        } else {
			return $this->getPickupWarehouseIdFromMethod($carrierMethod);
		}
		return 0;
	}

	/**
	 * @param string $carrierMethod <carrier>_<method>
	 * @return int 
	 */
	public function getShippingType($carrierMethod) {
		if(substr($carrierMethod, 0, 17) == 'schrackcontainer_') {
            return Scharcklive_SchrackShipping_Type::CONTAINER;
        } elseif(substr($carrierMethod, 0, 14) == 'schrackinpost_') {
            return Scharcklive_SchrackShipping_Type::INPOST;
        } elseif (substr($carrierMethod, 0, 14) == 'schrackpickup_') {
			return Schracklive_SchrackShipping_Type::PICKUP;
		} elseif (substr($carrierMethod, 0, 16) == 'schrackdelivery_') {
			return Schracklive_SchrackShipping_Type::DELIVERY;
		} else {
			return Schracklive_SchrackShipping_Type::UNKNOWN;
		}
	}

}

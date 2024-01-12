<?php

class Schracklive_SchrackShipping_Helper_Pickup extends Mage_Core_Helper_Abstract {

	public function getWarehouseIdFromMethod($method) {
		return substr($method, 9); // strip "warehouse" from beginning
	}

	public function getShippingMethodFromWarehouseId($id) {
		return 'schrackpickup_warehouse'.$id;
	}

	public function getMaximumNumberOfWarehouses() {
		return max(0, (int)Mage::getStoreConfig('carriers/schrackpickup/warehouse_number'));
	}

	public function getWarehouse($id) {
		$warehouse = null;
		$warehouseConfigIds = $this->getWarehouseIds();
		foreach ($warehouseConfigIds as $configId => $configuredId) {
			if ($configuredId == $id) {
				$warehouse = $this->getWarehouseModel($configId);
				break;
			}
		}
		return $warehouse;
	}

	public function getWarehouses() {
		$warehouses = array();
		foreach ($this->getWarehouseIds() as $configId => $configuredId) {
			$warehouses[$configuredId] = $this->getWarehouseModel($configId);
		}
		return $warehouses;
	}

	public function getWarehouseIds() {
		$ids = array();
		for ($i = 1; $i <= $this->getMaximumNumberOfWarehouses(); $i++) {
			if (!$this->getConfigData('id'.$i)) {
				continue;
			}
			$ids[$i] = $this->getConfigData('id'.$i);
		}
		return $ids;
	}

	protected function getConfigData($key) {
		return Mage::getStoreConfig('carriers/schrackpickup/'.$key);
	}

	protected function getWarehouseModel($configId) {
		$warehouse = new Varien_Object();
		$warehouse->setId(Mage::getStoreConfig('carriers/schrackpickup/id'.$configId));
		$warehouse->setName(Mage::getStoreConfig('carriers/schrackpickup/name'.$configId));
		$warehouse->setAddress(Mage::getStoreConfig('carriers/schrackpickup/address'.$configId));
		return $warehouse;
	}

}

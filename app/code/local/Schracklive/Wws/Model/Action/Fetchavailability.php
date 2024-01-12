<?php

class Schracklive_Wws_Model_Action_Fetchavailability extends Schracklive_Wws_Model_Action_Product {

	const CENTRAL_DELIVERY_WAREHOUSE = 80;
	const ALL_WAREHOUSES = null;

	protected $_requestName = 'getitemavail';

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
		$checkedArguments = $this->_checkArguments($arguments, array(
			'products' => 'array',
			'warehouses' => array('array', self::ALL_WAREHOUSES),
		));

		if ($checkedArguments['warehouses'] === self::ALL_WAREHOUSES) {
			$warehouseIds = array(Schracklive_Wws_Model_Request_Getitemavail::ALL_WAREHOUSES);
		} else {
			$warehouseIds = $checkedArguments['warehouses']; // get_item_vail request handler currently supports only one warehouse
		}

		$this->_requestArguments = array(
			'wwsCustomerId' => (string)Mage::helper('wws')->getAnonymousWwsCustomerId(),
			'warehouseIds' => $warehouseIds,
			'products' => $checkedArguments['products'],
		);
	}

	protected function _buildResponse() {
		$this->_response = array();
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $pickupWarehouseIds = $stockHelper->getPickupStockNumbers();
        $deliveryWarehouseIds = $stockHelper->getAllDeliveryStockNumbers();

        /*
		$deliveryWarehouseIds = array(Mage::helper('schrackshipping/delivery')->getWarehouseId());
		$pickupWarehouseIds = Mage::helper('schrackshipping/pickup')->getWarehouseIds();
*/
        
		$availabilityInfos = $this->_request->getAvailabilityInfos();
		foreach ($availabilityInfos as $sku => $warehouses) {
			$hadOwnDeliveryWarehouse = false;
			$hadCentralDeliveryWarehouse = false;
			foreach ($warehouses as $warehouseId => $info) {
				$this->_response[$sku][$warehouseId] = array(
					'qty' => $info['qty'],
				);
				if ($info['deliverywarehouse'] && in_array($warehouseId,$deliveryWarehouseIds) ) {
					if ( $stockHelper->getForeignDeliveryStock() && $warehouseId === $stockHelper->getForeignDeliveryStock()->getStockNumber() )
						$hadCentralDeliveryWarehouse = true;
					if ( $warehouseId === $stockHelper->getLocalDeliveryStock()->getStockNumber() )
						$hadOwnDeliveryWarehouse = true;
					$this->_response[$sku][$warehouseId]['delivery'] = array( // [$warehouseId] was: [$deliveryWarehouseId]
						'salesunit' => $info['deliverysalesunit'],
					);
				}
				if ($info['pickupwarehouse'] && in_array($warehouseId, $pickupWarehouseIds)) {
					if ( in_array($warehouseId,$deliveryWarehouseIds) && $warehouseId === $stockHelper->getLocalDeliveryStock()->getStockNumber() )
						$hadOwnDeliveryWarehouse = true;
					$this->_response[$sku][$warehouseId]['pickup'] = array(
						'salesunit' => $info['pickupsalesunit'],
					);
				}
				if (   $warehouseId != Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE 
                    && !isset($this->_response[$sku][$warehouseId]['delivery']) && !isset($this->_response[$sku][$warehouseId]['pickup'])) {
					unset($this->_response[$sku][$warehouseId]);
				}
			}
			if ( $hadCentralDeliveryWarehouse && ! $hadOwnDeliveryWarehouse ) {
				/*
				 * generating a dummy, empty local delivery wh result 
				 * coresponding to the central delivery wh
				 */
				$this->_response[$sku][Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE] = array(
					'qty' => 0,
				);
                if ( isset($this->_response[$sku][Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE]['delivery']) ) {
                    $this->_response[$sku][Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE]['delivery'] = array(
                        'salesunit' => $this->_response[$sku][Schracklive_Wws_Model_Action_Fetchavailability::CENTRAL_DELIVERY_WAREHOUSE]['delivery']['salesunit'],
                    );
                }
			}
			 
		}
	}

}

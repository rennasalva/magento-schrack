<?php

class Schracklive_Wws_Model_Request_Getitemavail extends Schracklive_Wws_Model_Request_Abstract {

	const ALL_WAREHOUSES = 0;

	protected $_soapMethod = 'get_item_avail';
	/* arguments */
	protected $_wwsCustomerId = '';
	protected $_warehouseIds = array(0);
	protected $_items = array();
	/* return values */
	protected $_availability = array();

	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'wwsCustomerId' => 'string',
			'warehouseIds' => 'array',
			'products' => 'array',
		));

		$this->_wwsCustomerId = $checkedArguments['wwsCustomerId'];
		$this->_warehouseIds = $checkedArguments['warehouseIds'];
		$this->_items = $checkedArguments['products'];
		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$arguments = array(
			'tt_item' => array(),
			'CustomerNumber' => $this->_wwsCustomerId,
		);
		$xrow = 1;
		foreach ($this->_items as $sku) {
			foreach ($this->_warehouseIds as $warehouseId) {
				$arguments['tt_item'][] = array(
					'xrow' => $xrow++,
					'WarehouseID' => $warehouseId,
					'ItemID' => $sku,
				);
			}
		}
		$this->_soapArguments = $arguments;
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		$this->_isStatusOfAllRowsValid('tt_avail');

		$this->_checkReturnedFieldsOfAllRows(
				'tt_avail', array(
			'ItemID',
			'WarehouseID',
			'Qty',
			'IsDeliveryStock',
			'IsPickupStock',
			'PickupSalesUnit',
			'DeliverySalesUnit',
			'DeliveryState',
			'PickupState',
			'IsValid',
			'IsHidden',
		));

		return true;
	}

	protected function _processResponse() {
		foreach ($this->_soapResponse['tt_avail'] as $availInfo) {
			if (!in_array($availInfo->ItemID, $this->_items)) {
				throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected item id: '.$availInfo->ItemID);
			}
			if ($availInfo->_ok) {
				$this->_availability[$availInfo->ItemID][$availInfo->WarehouseID] = array(
					'qty' => (int)$availInfo->Qty,
					'deliverywarehouse' => (bool)$availInfo->IsDeliveryStock,
					'deliverysalesunit' => $availInfo->DeliverySalesUnit,
					'deliverystate' => $availInfo->DeliveryState,
					'pickupwarehouse' => (bool)$availInfo->IsPickupStock,
					'pickupsalesunit' => $availInfo->PickupSalesUnit,
					'pickupstate' => $availInfo->PickupState,
				);
			}
		}

		return true;
	}

	public function getAvailabilityInfos() {
		return $this->_availability;
	}

	protected function shouldMockWws () {
        $flag = Mage::getStoreConfig('schrack/wws/mock_soap_calls');
        return isset($flag) && intval($flag) === 1;
    }

	protected function mockWws () {
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $this->_soapResponse = array();
        $this->_soapResponse['exit_code'] = 1;
        $this->_soapResponse['exit_msg'] = '';
        $this->_soapResponse['tt_avail'] = array();
        foreach ( $this->_soapArguments['tt_item'] as $requestItem ) {
            $obj = new stdClass();
            $obj->xrow = $requestItem['xrow'];
            $sku = $requestItem['ItemID'];
            $obj->ItemID = $sku;
            $wh = $requestItem['WarehouseID'];
            $obj->WarehouseID = $wh;
            $stock = $stockHelper->getStockByNumber($wh);
            $obj->Qty = intval(rand(0,2000));
            $obj->IsDeliveryStock = $stock->getIsDelivery();
            $obj->IsPickupStock = $stock->getIsPickup();
            $obj->PickupSalesUnit = '1';
            $obj->DeliverySalesUnit = '1';
            $obj->DeliveryState = '2';
            $obj->PickupState = '2';
            $obj->IsValid = 1;
            $obj->IsHidden = 0;
            $obj->xstatus = 1;
            $obj->xerror = '';
            $this->_soapResponse['tt_avail'][] = $obj;
        }

    }
}

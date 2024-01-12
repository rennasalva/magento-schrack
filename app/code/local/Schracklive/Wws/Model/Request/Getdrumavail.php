<?php

class Schracklive_Wws_Model_Request_Getdrumavail extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'get_drum_avail';
	/* arguments */
	protected $_items = array();
	protected $_warehouses = array();
	/* return values */
	protected $_drumInfos = array();

	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'products' => 'array',
			'warehouses' => 'array'
				));

		$this->_items = $checkedArguments['products'];
		$this->_warehouses = $checkedArguments['warehouses'];
		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$arguments = array(
			'tt_item' => array(),
		);
		$xrow = 1;
		foreach ($this->_items as $sku => $qty) {
			foreach ($this->_warehouses as $warehouse) {
				$arguments['tt_item'][] = array(
					'xrow' => $xrow,
					'WarehouseID' => (int)$warehouse,
					'ItemID' => $sku,
					'Qty' => (int)$qty,
				);
				$xrow++;
			}
		}
		$this->_soapArguments = $arguments;
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		$this->_isStatusOfAllRowsValid('tt_drum');

		$this->_checkReturnedFieldsOfAllRows(
				'tt_drum', array(
			'WarehouseID',
			'ItemID',
			'Sort',
			'UnitNumber',
			'UnitID',
			'Description',
			'Type',
			'Number',
			'TargetQty',
			'Qty',
			'LessenShip',
			'LessenPick',
				)
		);

		$this->_checkReturnedFieldsOfAllRows(
				'tt_possibledrum', array(
			'WarehouseID',
			'ItemID',
			'Sort',
			'UnitNumber',
			'UnitID',
			'Description',
			'Size',
			'Qty',
			'LessenShip',
			'LessenPick',
				)
		);

		return true;
	}

	protected function _processResponse() {
		foreach ($this->_soapResponse['tt_drum'] as $drumInfo) {
			if (!isset($this->_items[$drumInfo->ItemID])) {
				throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected item id: '.$drumInfo->ItemID);
			}
			$debugInfo = '';
			if (Mage::getStoreConfigFlag('schrackdev/development/debug_messages')) {
				$debugInfo = ' ['.$drumInfo->Type.($drumInfo->LessenShip ? '/L' : '').($drumInfo->TargetQty ? '' : '/0').']';
			}
			if ($drumInfo->_ok) {
				$this->_drumInfos[$drumInfo->ItemID]['available'][$drumInfo->WarehouseID][(int)$drumInfo->Sort] = Mage::getModel('schrackcatalog/drum', array(
							'wws_number' => $drumInfo->UnitNumber,
							'name' => $drumInfo->UnitID,
							'description' => $drumInfo->Description.$debugInfo,
							'type' => $drumInfo->Type,
							'drum_qty' => $drumInfo->Number, // number of drums
							'size' => (int)$drumInfo->TargetQty,
							'stock_qty' => (int)$drumInfo->Qty, // per sales unit (eg. "m")
							'lessen_delivery' => (boolean)$drumInfo->LessenShip,
							'lessen_pickup' => (boolean)$drumInfo->LessenPick,
						));
			}
		}
		foreach ($this->_soapResponse['tt_possibledrum'] as $drumInfo) {
			if (!isset($this->_items[$drumInfo->ItemID])) {
				throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected item id: '.$drumInfo->ItemID);
			}
			$debugInfo = '';
			if (Mage::getStoreConfigFlag('schrackdev/development/debug_messages')) {
				$debugInfo = ($drumInfo->LessenShip ? ' [L]' : '');
			}
			$this->_drumInfos[$drumInfo->ItemID]['possible'][$drumInfo->WarehouseID][(int)$drumInfo->Sort] = Mage::getModel('schrackcatalog/drum', array(
						'wws_number' => $drumInfo->UnitNumber,
						'name' => $drumInfo->UnitID,
						'description' => $drumInfo->Description.$debugInfo,
						'type' => 'F',
						'size' => (int)$drumInfo->Size,
						'stock_qty' => (int)$drumInfo->Qty,
						'lessen_delivery' => (boolean)$drumInfo->LessenShip,
						'lessen_pickup' => (boolean)$drumInfo->LessenPick,
					));
		}
		return true;
	}

	public function getDrumInfos() {
		return $this->_drumInfos;
	}


    /**
     * HACK for telling WWS that Warehouse Magic (<local delivere store> - 80 - 999) should NOT be done.
     * 
     * REMOVE THAT AFTER RELEASE!!!!! 
     */
	protected function _addStandardArguments() {
		$wwsAuth = Mage::helper('wws')->getWwsAuthentication();
		$countryPrefix = '';
		$senderIdPrefix = '';
		if ($this->_config['test']) {
			$countryPrefix = 'Test_';
			$senderIdPrefix = 'Test_';
		}
		if ($this->_config['qa']) {
			$countryPrefix = 'Dev_';
		}

		array_unshift($this->_soapArguments, $countryPrefix.$this->_config['country'], $senderIdPrefix.$wwsAuth->getSenderId().'.v2,TX='.$this->_logId, $wwsAuth->getUser(), $wwsAuth->getPassword());
	}

    
}

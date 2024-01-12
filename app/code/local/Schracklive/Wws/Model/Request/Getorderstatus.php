<?php

class Schracklive_Wws_Model_Request_GetOrderStatus extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'get_order_status';
	/* arguments */
	protected $_wwsOrderNumber = '';
	/* return values */
	protected $_states = array();

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'wwsOrderNumber');
		$this->_wwsOrderNumber = $arguments['wwsOrderNumber'];
		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$this->_soapArguments = array(
			'tt_status' => array(
				array(
					'xrow' => 1,
					'OrderNumber' => $this->_wwsOrderNumber,
				),
			),
		);
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		if (!$this->_isStatusOfOneRowValid('tt_wwsorder')) {
			return false;
		}

		$this->_checkReturnedFieldsOfOneRow(
				'tt_wwsorder', array(
			'OrderNumber',
			'CustomerNumber',
			'IsOrder',
			'IsActive',
			'IsOrdered',
			'xstatus',
				)
		);

		return true;
	}

	protected function _processResponse() {
		foreach ($this->_soapResponse['tt_wwsorder'] as $status) {
			$this->_states[] = (object)array(
						'wwsOrderNumber' => $status->OrderNumber,
						'wwsCustomerNumber' => $status->CustomerNumber,
						'isOrder' => $status->IsOrder ? true : false,
						'isFinalized' => $status->IsOrdered ? true : false,
						'mayBeChanged' => $status->IsActive ? true : false
			);
		}
		if (count($this->_states) != 1) {
			throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected number of rows.');
		}
		if ($this->_states[0]->wwsOrderNumber != $this->_wwsOrderNumber) {
			throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected order number.');
		}
		return true;
	}

	public function getOrderStatus() {
		return $this->_states[0];
	}

}


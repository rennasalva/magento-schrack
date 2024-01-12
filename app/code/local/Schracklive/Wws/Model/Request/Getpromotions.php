<?php

class Schracklive_Wws_Model_Request_Getpromotions extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'get_cust_promotions';
	/* arguments */
	protected $_wwsCustomerId = '';
	/* return values */
	protected $_promotionInfos = array();

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'wwsCustomerId');
		$this->_wwsCustomerId = $arguments['wwsCustomerId'];
		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$arguments = array(
			$this->_wwsCustomerId,
		);
		$this->_soapArguments = $arguments;
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		$this->_checkReturnedFieldsOfAllRows(
				'tt_promotions', array(
			'ItemID',
			'PromoTypes',
				)
		);

		return true;
	}

	protected function _processResponse() {
		foreach ($this->_soapResponse['tt_promotions'] as $promotion) {
			$this->_promotionInfos[$promotion->ItemID] = explode(',',$promotion->PromoTypes);
		}
		return true;
	}

	public function getPromotionInfos() {
		return $this->_promotionInfos;
	}

	protected function shouldMockWws () {
        $flag = Mage::getStoreConfig('schrack/wws/mock_soap_calls');
        return isset($flag) && intval($flag) === 1;
    }

	protected function mockWws () {
        $this->_soapResponse = array();
        $this->_soapResponse['exit_code'] = 1;
        $this->_soapResponse['exit_msg'] = '';
        $this->_soapResponse['tt_promotions'] = array();
        $obj = new stdClass();
        $obj->ItemID = 'AS201040-5';
        $obj->PromoTypes = '710193';
        $this->_soapResponse['tt_promotions'][] = $obj;
        $obj = new stdClass();
        $obj->ItemID = 'BM018102--';
        $obj->PromoTypes = '710193';
        $this->_soapResponse['tt_promotions'][] = $obj;
    }
}

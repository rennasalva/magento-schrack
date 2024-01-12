<?php

class Schracklive_Wws_Model_Request_InsertWebCustomer extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'insert_web_customer';
	/* arguments */
	protected $_wwsCustomer = array();
	protected $_creatorEmail;
	protected $_memo;
    protected $_shop;
	/* return values */
	protected $_wwsCustomerId = null;

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'name1');

		$attributes = array('title', 'name1', 'name2', 'name3', 'street', 'postcode', 'city', 'country_id', 'telephone', 'fax', 'email');
		foreach ($attributes as $attribute) {
			if (isset($arguments[$attribute])) {
				$this->_wwsCustomer[$attribute] = $arguments[$attribute];
			} else {
				$this->_wwsCustomer[$attribute] = '';
			}
		}

		$this->_creatorEmail = $this->_checkArgument($arguments, 'creator_email', array('string', ''));
		$this->_memo = $this->_checkArgument($arguments, 'memo', array('string', ''));
        $this->_shop = Mage::getStoreConfig('schrack/general/country');

		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$this->_soapArguments = array(
			'tt_customer' => array(
				array(
					'xrow' => 1,
					'CustTitle' => $this->_wwsCustomer['title'],
					'CustName1' => $this->_wwsCustomer['name1'],
					'CustName2' => $this->_wwsCustomer['name2'],
					'CustName3' => $this->_wwsCustomer['name3'],
					'CustStr' => $this->_wwsCustomer['street'],
					'CustZip' => $this->_wwsCustomer['postcode'],
					'CustCity' => $this->_wwsCustomer['city'],
					'CustCtry' => $this->_wwsCustomer['country_id'],
					'CustPhone' => $this->_wwsCustomer['telephone'],
					'CustFax' => $this->_wwsCustomer['fax'],
					'CustEmail' => $this->_wwsCustomer['email'],
					'CreateByUser' => $this->_creatorEmail,
					'Memo' => $this->_memo,
                    'shop' => $this->_shop
				),
			),
		);
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		if (!$this->_isStatusOfOneRowValid('tt_wwscust')) {
			return false;
		}

		$this->_checkReturnedFieldsOfOneRow(
				'tt_wwscust', array(
			'xrow',
			'CustomerNumber',
			'xstatus',
				)
		);

		return true;
	}

	protected function _processResponse() {
		if (count($this->_soapResponse['tt_wwscust']) != 1) {
			throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected number of rows.');
		}

		$this->_wwsCustomerId = $this->_soapResponse['tt_wwscust'][0]->CustomerNumber;

		return true;
	}

	public function getWwsCustomerId() {
		return $this->_wwsCustomerId;
	}

}

<?php

class Schracklive_Wws_Model_Signal_Configuration {

	const TYPE_DROP_REQUEST = 'drop';
	const TYPE_FORCE_RETRY = 'recreate';
	const TYPE_MAIL_ACTION = 'mail';
	const DEFAULT_ERROR_CODE = 400;
	const DEFAULT_WARNING_CODE = 300;

	protected $_methodPrefix;
	/** @var Varien_Data_Collection */
	protected $_signals = null;

	public function __construct($method) {
		switch ($method) {
			case 'insert_update_order':
				$this->_methodPrefix = 'change';
				break;
			case 'ship_order':
				$this->_methodPrefix = 'ship';
				break;
			default:
				throw new InvalidArgumentException('Invalid method given in argument 1.');
		}
	}

	public function getCodesToIgnore() {
		$codes = array();
		foreach ($this->_getSignals() as $signal) {
			if ($signal->getData($this->_methodPrefix.'_message') == '') {
				$codes[] = $signal->getCode();
			}
		}
		return $codes;
	}

	public function getCodesDroppingRequest() {
		return $this->_getCodesByType(self::TYPE_DROP_REQUEST);
	}

	public function getCodesForcingRetryWithEmptyOrderNumber() {
		return $this->_getCodesByType(self::TYPE_FORCE_RETRY);
	}

	public function getCodesWithMailAction() {
		return $this->_getCodesByType(self::TYPE_MAIL_ACTION);
	}

	protected function _getCodesByType($type) {
		$codes = array();
		foreach ($this->_getSignals() as $signal) {
			if ($signal->getData($this->_methodPrefix.'_'.$type)) {
				$codes[] = $signal->getCode();
			}
		}
		return $codes;
	}

	protected function _getSignals() {
		if (!$this->_signals) {
			$this->_signals = Mage::getResourceModel('wws/signal_collection');
			$this->_signals->load();
		}
		return $this->_signals;
	}

	public function getMessageTextForCode($code) {
		return $this->_getFieldForCode($code, 'message');
	}

	public function getMailSubjectForCode($code) {
		return $this->_getFieldForCode($code, $this->_methodPrefix.'_mail_subject');
	}

	public function getMailBodyForCode($code) {
		return $this->_getFieldForCode($code, $this->_methodPrefix.'_mail_body');
	}

	public function _getFieldForCode($code, $field) {
		foreach ($this->_getSignals() as $signal) {
			if ($code == $signal->getCode()) {
				return $signal->getData($field);
			}
		}
		return null;
	}

	public function setSignalsForTesting(Varien_Data_Collection $signals) {
		$this->_signals = $signals;
	}

}

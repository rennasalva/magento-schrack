<?php

/**
 * This class will translate a shop action into a SOAP request.
 */
abstract class Schracklive_Wws_Model_Action_Abstract extends Schracklive_Schrack_Model_Abstract implements Schracklive_Wws_Model_Action {

	protected $_requestName = '';
	protected $_requestArguments = array();
	/* @var $_request Schracklive_Wws_Model_Request_Abstract */
	protected $_request = null;
	protected $_response = null;
	protected $_messages = null;
	protected $_soapClient = null;

	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'soapClient' => array('Schracklive_Schrack_Model_Soap_Client', null)
				));
		$this->_soapClient = $checkedArguments['soapClient'];
	}

	public function execute() {
		$this->_buildArguments();
		$this->_addStandardArguments();
		if ($this->_call()) {
			$this->_buildResponse();
		} else {
			throw new Schracklive_Wws_RequestErrorException('Error in SOAP call '.$this->_request->getSoapMethodName().': '
					.$this->_messages->toString());
		}
		return $this->_response;
	}

	protected function _buildArguments() {

	}

	protected function _addStandardArguments() {
		$this->_requestArguments['client'] = $this->_createSoapClient();
	}

	protected function _createSoapClient() {
		if (is_null($this->_soapClient)) {
			$this->_soapClient = Mage::helper('wws')->createSoapClient();
		}
		return $this->_soapClient;
	}

	protected function _call() {
		$this->_createRequest();
		$success = $this->_request->call();
		$this->_messages = $this->_request->getMessages();
		return $success;
	}

	abstract protected function _buildResponse();

	/**
	 * Parse memo field populating the message collection
	 *
	 * @param string $memo
	 * @return array List of non-message codes
	 */
	protected function _parseMemoIntoMessages($memo) {
		$codes = array();

		if (strlen(trim($memo)) == 0) {
			return $codes;
		}

        if (stristr($memo, 'SPLIT=TRUE')) {
            $memo = str_replace('SPLIT=TRUE', '203=Translated Split Text From wws_signal (code = 203 in all databases)', $memo);
        }

		foreach (explode(';', $memo) as $note) {
			@list($code, $text) = explode('=', $note);
			if (preg_match('/^\d+$/', $code)) {
				$message = $this->_request->parseWwsMessage($code, $text);
				if ($message) {
					$this->_messages->add($message);
				}
			} else {
				$codes[$code][] = $text;
			}
		}

		return $codes;
	}

	protected function _createRequest() {
		if (!$this->_request) {
			$this->_request = Mage::getModel('wws/request_'.$this->_requestName, $this->_requestArguments);
		}
	}

	/**
	 *
	 * @return Mage_Core_Model_Message_Collection
	 */
	public function getMessages() {
		return $this->_messages;
	}

	protected function _requestException($message) {
		$e = $this->_request->exception($message);
		if (!($e instanceof Exception)) { // a mock may return NULL
			$e = Mage::exception('Schracklive_Wws', $message);
		}
		return $e;
	}

}

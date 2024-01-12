<?php

class Schracklive_Schrack_Exception extends Mage_Core_Exception {

	protected $_mustBeLogged = true;

	public function __construct($message = '', $code = 0, Exception $previous = null, $mustBeLogged = true) {
		parent::__construct($message, $code, $previous);
		$this->_mustBeLogged = $mustBeLogged;
	}

	public function mustBeLogged() {
		return $this->_mustBeLogged;
	}

}

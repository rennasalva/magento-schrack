<?php

class Schracklive_Schrack_Helper_Logger {

	public function error($message) {
		$this->_log('error', $message);
	}

	public function debug($message) {
		$this->_log('debug', $message);
	}

	public function authentication($message) {
		$this->_log('auth', $message);	
	}

	protected function _log($type, $message) {
		$log = fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_'.$type.'.log', 'a');
		fwrite($log, date('c').' '.$message.chr(10));
		fclose($log);
	}

}

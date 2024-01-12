<?php

class Schracklive_Wws_Model_Action_Cache extends Schracklive_Schrack_Model_Abstract implements Schracklive_Wws_Model_Action {

	/** @var Schracklive_Wws_Model_Action_Abstract */
	protected $_action;
	/** @var Schracklive_Wws_Helper_Cache_Abstract */
	protected $_cache;
	protected $_fetchedInfos = array();
	protected $_cacheResult = array();

    private static $_suppressWwsCalls = false;
    
	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'arguments' => 'array',
			'action' => 'Schracklive_Wws_Model_Action_Abstract',
			'cache' => 'Schracklive_Wws_Helper_Cache_Abstract',
				));

		$this->_retrieveFromCacheArguments = $checkedArguments['arguments'];
		$this->_action = $checkedArguments['action'];
		$this->_cache = $checkedArguments['cache'];
	}

	public function execute() {
		$this->_cacheResult = $this->_retrieveFromCache();
		if (!$this->_cacheResult->getSuccess() && ! self::$_suppressWwsCalls ) {
			try {
				$this->_action->setArguments($this->_cacheResult->getMisses());
				$this->_fetchedInfos = $this->_action->execute();
				$this->_storeInCache();
			} catch (Schracklive_Wws_RequestErrorException $e) {
				Mage::log($e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), Zend_Log::ERR);
			}
		}
		$infos = Mage::helper('schrackcore/array')->arrayMergeRecursiveWithKeys($this->_cacheResult->getInfos(), $this->_fetchedInfos);
		return $infos;
	}

	/**
	 *
	 * @return Mage_Core_Model_Message_Collection
	 */
	public function getMessages() {
		return $this->_action->getMessages();
	}

	protected function _retrieveFromCache() {
		return call_user_func_array(array($this->_cache, 'retrieve'), $this->_retrieveFromCacheArguments);
	}

	protected function _storeInCache() {
		$args = $this->_cacheResult->getMisses();
		array_unshift($args, $this->_fetchedInfos);
		call_user_func_array(array($this->_cache, 'store'), $args);
	}

    public static function suppressWwsCalls ( $yes = true ) {
        self::$_suppressWwsCalls = $yes;
    }

}

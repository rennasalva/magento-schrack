<?php

abstract class Schracklive_Wws_Model_Action_Product extends Schracklive_Wws_Model_Action_Abstract {

	protected $_response = array();

	public function __construct(array $arguments) {
		foreach ($arguments as $argumentName => $argumentValue) {
			if (is_int($argumentName)) {
				throw new InvalidArgumentException('Arguments must be passed as associative array.');
			}
		}
		$this->_constructorArguments = $arguments;
		parent::__construct($arguments);
	}

	abstract public function setArguments(array $arguments);

}

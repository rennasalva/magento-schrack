<?php

namespace SezameLib\Exception;

class Parameter extends \Exception
{
	/** @var \stdClass */
	private $_errorInfo;

	public function getErrorInfo()
	{
		return $this->_errorInfo;
	}

	public function setErrorInfo($errorInfo)
	{
		$this->_errorInfo = $errorInfo;
	}
}
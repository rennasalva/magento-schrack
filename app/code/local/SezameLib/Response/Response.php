<?php

namespace SezameLib\Response;

abstract class Response
{
	/** @var \stdClass */
	protected $_data = null;

	/** @var \Buzz\Message\Response */
	protected $_response = null;

	/**
	 * @return \Buzz\Message\Response
	 */
	public function http()
	{
		return $this->_response;
	}

	public function getData()
	{
		return $this->_data;
	}

	public function isNotfound()
	{
		return $this->_data == null;
	}

}

<?php

namespace SezameLib\Request;

abstract class Request
{
	/** @var \SezameLib\Client */
	protected $_client = null;

	/**
	 * @param \SezameLib\Client $client
	 */
	public function setClient(\SezameLib\Client $client)
	{
		$this->_client = $client;
	}

	/**
	 * @return \SezameLib\Client
	 */
	public function getClient()
	{
		return $this->_client;
	}

}

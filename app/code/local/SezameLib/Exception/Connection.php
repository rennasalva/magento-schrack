<?php

namespace SezameLib\Exception;

class Connection extends \Exception
{
	/**
	 * Request object
	 *
	 * @var \Buzz\Message\RequestInterface
	 */
	private $request;

	/**
	 * @return \Buzz\Message\RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param \Buzz\Message\RequestInterface $request
	 */
	public function setRequest(\Buzz\Message\RequestInterface $request)
	{
		$this->request = $request;
	}

}
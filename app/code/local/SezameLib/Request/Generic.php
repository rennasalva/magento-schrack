<?php

namespace SezameLib\Request;

class Generic extends Request
{
	protected $_params = Array();

	public function __construct(\SezameLib\Client $client)
	{
		$this->setClient($client);
	}


}

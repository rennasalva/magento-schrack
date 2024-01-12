<?php

namespace SezameLib\Response;

class Generic extends Response
{
	public function __construct($response, $data)
	{
		$this->_response = $response;
		$this->_data = $data;
	}
}

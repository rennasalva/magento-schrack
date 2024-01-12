<?php

namespace SezameLib\Request;

class Cancel extends Generic
{
	public function send()
	{
		$response = $this->_client->post('client/cancel');
		$data = $this->_client->checkResponse($response, true);
		return new \SezameLib\Response\Cancel($response, $data);
	}
}

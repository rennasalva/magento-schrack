<?php

namespace SezameLib\Request;

class Link extends Generic
{
	public function setUsername($username)
	{
		$this->_params['username'] = (string) $username;
		return $this;
	}

	public function send()
	{
		$response = $this->_client->post('client/link', $this->_params);
		if ($response->getStatusCode() == 409)
			return new \SezameLib\Response\Link($response, null);

		$data = $this->_client->checkResponse($response);
		return new \SezameLib\Response\Link($response, $data);
	}
}

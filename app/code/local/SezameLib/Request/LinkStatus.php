<?php

namespace SezameLib\Request;

class LinkStatus extends Generic
{
	public function setUsername($username)
	{
		$this->_params['username'] = (string) $username;
		return $this;
	}

	public function send()
	{
		$response = $this->_client->post('client/link/status', $this->_params);
		$data = $this->_client->checkResponse($response);
		return new \SezameLib\Response\LinkStatus($response, $data);
	}
}

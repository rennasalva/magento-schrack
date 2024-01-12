<?php

namespace SezameLib\Request;

class Status extends Generic
{
	protected $_authId = null;

	public function setAuthId($authId)
	{
		$this->_authId = (string) $authId;
		return $this;
	}

	public function send()
	{
		$response = $this->_client->get('auth/status/' . (string) $this->_authId);
		$data = $this->_client->checkResponse($response);
		return new \SezameLib\Response\Status($response, $data);
	}
}

<?php

namespace SezameLib\Request;

class Auth extends Generic
{
	protected $_extraParams = Array();

	public function setUsername($username)
	{
		$this->_params['username'] = (string) $username;
		return $this;
	}

	public function setMessage($message)
	{
		$this->_params['message'] = (string) $message;
		return $this;
	}

	public function setTimeout($timeout)
	{
		$this->_params['timeout'] = (int) $timeout;
		return $this;
	}

	public function setCallback($callback)
	{
		$this->_params['callback'] = (string) $callback;
		return $this;
	}

	public function setType($type)
	{
		$this->_params['type'] = (string) $type;
		return $this;
	}

    public function setExtraParam($p, $v)
    {
        $this->_extraParams[$p] = $v;
        return $this;
    }

	public function send()
	{
		$this->_params['params'] = $this->_extraParams;
		$response = $this->_client->post('auth/login', $this->_params);
		$data = $this->_client->checkResponse($response);
		return new \SezameLib\Response\Auth($response, $data);
	}
}

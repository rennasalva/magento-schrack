<?php

namespace SezameLib\Request;

class Register extends Generic
{
	public function setEmail($email)
	{
        $this->_params['email'] = (string) $email;
		return $this;
	}

    public function setName($name)
    {
        $this->_params['name'] = (string) $name;
        return $this;
    }

    public function send()
	{
		$response = $this->_client->post('client/register', $this->_params);
		$data = $this->_client->checkResponse($response, true);
		return new \SezameLib\Response\Register($response, $data);
	}
}

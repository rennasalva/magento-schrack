<?php

namespace SezameLib\Request;

class Sign extends Generic
{
	public function setCSR($csr)
	{
        $this->_params['csr'] = (string) $csr;
		return $this;
	}

    public function setSharedSecret($secret)
    {
        $this->_params['sharedsecret'] = (string) $secret;
        return $this;
    }

    public function send()
	{
		$response = $this->_client->post('client/sign', $this->_params);
		$data = $this->_client->checkResponse($response, true);
		return new \SezameLib\Response\Sign($response, $data);
	}
}

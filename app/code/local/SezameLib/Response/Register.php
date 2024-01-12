<?php

namespace SezameLib\Response;

class Register extends Generic
{
	public function getClientCode()
	{
		return $this->_data->clientcode;
	}

    public function getSharedSecret()
    {
        return $this->_data->sharedsecret;
    }

}

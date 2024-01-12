<?php

namespace SezameLib\Response;

class Auth extends Generic
{
	public function getId()
	{
		return $this->_data->id;
	}

	public function getStatus()
	{
		return $this->_data->status;
	}

	public function isOk()
	{
		return $this->_data->status == 'initiated';
	}

}

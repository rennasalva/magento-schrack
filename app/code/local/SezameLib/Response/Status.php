<?php

namespace SezameLib\Response;

class Status extends Generic
{
	public function getId()
	{
		return $this->_data->id;
	}

	public function getStatus()
	{
		return $this->_data->status;
	}

	public function getMessage()
	{
		return $this->_data->message;
	}

	public function isAuthorized()
	{
		return $this->_data->status == 'authorized';
	}

	public function isDenied()
	{
		return $this->_data->status == 'denied';
	}

	public function isPending()
	{
		return $this->_data->status == 'initiated';
	}

}

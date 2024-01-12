<?php

namespace SezameLib\Response;

class Sign extends Generic
{
	public function getCertificate()
	{
		return $this->_data->cert;
	}

}

<?php

namespace SezameLib\Response;

class LinkStatus extends Generic
{
	public function isLinked()
	{
		return $this->_data;
	}

}

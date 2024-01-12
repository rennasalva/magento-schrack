<?php

class Schracklive_SchrackCatalog_Model_Mysql4_Attachment extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('schrackcatalog/attachment', 'attachment_id');
	}


	protected function _beforeSave(Mage_Core_Model_Abstract $data)
	{
		if (!$data->getId()) {
			$data->setCreatedAt(now());
		}
		$data->setUpdatedAt(now());
		return $this;
	}

}

?>

<?php

class Schracklive_SchrackCatalog_Model_Mysql4_Filedata extends Mage_Core_Model_Mysql4_Abstract
{
	protected function _construct()
	{
		$this->_init('schrackcatalog/filedata', 'filedata_id');
		
	}

	public function loadByUrl(Schracklive_SchrackCatalog_Model_Filedata $data, $url)
	{
        if ($id = $this->getIdByUrl($url)) {
            $this->load($data, $id);
        }
        else {
            $data->setData(array());
        }
        return $this;
	}

	public function getIdByUrl($url)
	{
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('schrackcatalog/filedata', $this->getIdFieldName()))
            ->where('url=:url');
        return $this->_getReadAdapter()->fetchOne($select, array('url' => $url));
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

<?php

class Schracklive_SchrackCatalog_Model_Product_Image extends Mage_Catalog_Model_Product_Image {

	/**
	 * First check if this is a Net resource
	 * Then check this file on FS
	 * If it doesn't exist - try to download it from DB
	 *
	 * @param string $filename
	 * @return bool
	 */
	protected function _fileExists($filename) {
		if (preg_match('|^https?://|', $filename)) {
			return true;
		} elseif (file_exists($filename)) {
			return true;
            } else {
			return Mage::helper('core/file_storage_database')->saveFileToFilesystem($filename);
                    }
                }


	public function setBaseFile($file)
	{
		if (preg_match('|^https?://|', $file)) {
        $this->_baseFile = $file;
        }
		else parent::setBaseFile($file);
    }

}

<?php

class Schracklive_SchrackCatalog_Model_Category_Attribute_Backend_Image extends Mage_Catalog_Model_Category_Attribute_Backend_Image {

	public function afterSave($object) {
		if (empty($_FILES)) {
			return;
	}
		parent::afterSave($object);
	}

}

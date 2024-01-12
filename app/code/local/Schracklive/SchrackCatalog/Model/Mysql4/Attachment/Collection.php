<?php

class Schracklive_SchrackCatalog_Model_Mysql4_Attachment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

	protected function _construct() {
		$this->_init('schrackcatalog/attachment');
	}

	public function setCategoryFilter($category) {
		if ($category->getId()) {
			$this->addFieldToFilter('entity_id', Array('eq' => $category->getId()));
			$this->addFieldToFilter('entity_type_id', Array('eq' => $category->getEntityTypeId()));
		}
		return $this;
	}

	public function setProductFilter($product) {
		if ($product->getId()) {
			$this->addFieldToFilter('entity_id', Array('eq' => $product->getId()));
			$this->addFieldToFilter('entity_type_id', Array('eq' => $product->getEntityTypeId()));
		}
		return $this;
	}

	public function setEntityTypeFilter($entity) {
		if ($entity->getId()) {
			$this->addFieldToFilter('entity_type_id', Array('eq' => $category->getEntityTypeId()));
		}
		return $this;
	}

}

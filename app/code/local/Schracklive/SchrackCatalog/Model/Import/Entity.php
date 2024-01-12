<?php

abstract class Schracklive_SchrackCatalog_Model_Import_Entity {

	protected $entityCode = '';
	protected $skeletonAttributeSetName = "Default"; // comes with Magento

	public function __construct() {
		$this->attributeSetModel = Mage::getModel('eav/entity_attribute_set');
		$this->attributeModel = Mage::getModel('catalog/resource_eav_attribute');
		$this->entityType = Mage::getModel('eav/entity')->setType($this->entityCode)->getTypeId();
		$this->optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection');
		$this->skeletonAttributeSetId = $this->getSkeletonAttributeSetId();
		$this->stores = $this->getStores();
	}

	public function getSkeletonAttributeSetId() {
		$sets = clone $this->attributeSetModel;
		$sets = $sets->getResourceCollection()
						->setEntityTypeFilter($this->entityType)
						->addFieldToFilter("attribute_set_name", $this->skeletonAttributeSetName)
						->load()->toArray();
		return $sets['items'][0]['attribute_set_id'];
	}

	public function getStores() {
		$stores = array();
		foreach (Mage::app()->getStores() as $store) {
			$stores[] = $store->getId();
		}
		return $stores;
	}

	public function getAttribute($name) {
		$attribute = clone $this->attributeModel;
		if (is_numeric($name)) {
			$attribute->load($name);
		} else {
			$attribute = Mage::getSingleton('eav/config')->getAttribute($this->entityCode, $name);
		}
		if (!is_numeric($attribute->getAttributeId())) {
			return false;
		} else {
			return $attribute;
		}
	}

	public function addAttributeToSet($attr, $attset) {
		if (!is_numeric($attset)) {
			$attset = $this->getAttributeSetId($attset);
		}
		if ($attset == false) return false;
		$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()
						->setAttributeSetFilter($attset)
						->load()->toArray();
		$attribute = $this->getAttribute($attr);
		$attribute->setAttributeGroupId($groups['items'][0]["attribute_group_id"])
				->setAttributeSetId($attset)->save();
	}

	public function getAttributeSetId($attset) {
		$collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
						->setEntityTypeFilter($this->entityType);
		foreach ($collection as $item) {
			if ($item->getAttributeSetName() == $attset) {
				return $item->getAttributeSetId();
			}
		}
		return false;
	}

	public function createAttributeSet($name) {
		$id = $this->getAttributeSetId($name);
		if ($id != false) return $id;
		$modelSet = clone $this->attributeSetModel;
		$modelSet->setAttributeSetName($name)->setEntityTypeId($this->entityType)->save();
		$modelSet->initFromSkeleton($this->skeletonAttributeSetId)->save();
		return $modelSet->getAttributeSetId();
	}

	public function getAttributeValueId($name, $value) {
		$attribute = $this->getAttribute($name);
		$optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
						->setAttributeFilter($attribute->getId())
						->setPositionOrder('desc', true)
						->load();
		$optionCollection = $optionCollection->toOptionArray();
		foreach ($optionCollection as $option) {
			if ($option['label'] == $value) return $option['value'];
		}
		return false;
	}

}
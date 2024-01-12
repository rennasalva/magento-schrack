<?php

class Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Url extends Mage_Catalog_Model_Resource_Eav_Mysql4_Url {

	/**
	* Retrieve Product data objects
	*
	* @param int|array $productIds
	* @param int $storeId
	* @param int $entityId
	* @param int $lastEntityId
	* @return array
	*/
	protected function _getProducts($productIds = null, $storeId, $entityId = 0, &$lastEntityId) {
		$products = parent::_getProducts($productIds, $storeId, $entityId, $lastEntityId);

		if ($products) {
			foreach (array('status') as $attributeCode) {
				$attributes = $this->_getProductAttribute($attributeCode, array_keys($products), $storeId);
				foreach ($attributes as $productId => $attributeValue) {
					$products[$productId]->setData($attributeCode, $attributeValue);
				}
			}
		}

		return $products;
	}
}
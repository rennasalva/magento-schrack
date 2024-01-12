<?php

class Schracklive_SchrackSitemap_Model_Mysql4_Catalog_Product extends Mage_Sitemap_Model_Mysql4_Catalog_Product {

    protected function _construct() {
        parent::_construct();
    }

	/**
	 * Add attribute to filter
	 * Almost identical to parent, only added != condition type
	 *
	 * @param int    $storeId
	 * @param string $attributeCode
	 * @param mixed  $value
	 * @param string $type
	 * @return Zend_Db_Select
	 * @see Mage_Sitemap_Model_Mysql4_Catalog_Product::_addFilter
	 */
/* Code seems to have no effect

	protected function _addFilter($storeId, $attributeCode, $value, $type = '=') {
		if (!isset($this->_attributesCache[$attributeCode])) {
			$attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute($attributeCode);

			$this->_attributesCache[$attributeCode] = array(
				'entity_type_id' => $attribute->getEntityTypeId(),
				'attribute_id' => $attribute->getId(),
				'table' => $attribute->getBackend()->getTable(),
				'is_global' => $attribute->getIsGlobal() == Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
				'backend_type' => $attribute->getBackendType()
			);
		}

		$attribute = $this->_attributesCache[$attributeCode];

		if (!$this->_select instanceof Zend_Db_Select) {
			return false;
		}

		switch ($type) {
			case '=':
				$conditionRule = '=?';
				break;
			case 'in':
				$conditionRule = ' IN(?)';
				break;
			// Needed for schrack_sts_statuslocal != 'tot'
			case '!=':
				$conditionRule = '!=?';
				break;
			default:
				return false;
				break;
		}

		if ($attribute['backend_type'] == 'static') {
			$this->_select->where('e.'.$attributeCode.$conditionRule, $value);
		} else {
			$this->_select->join(
				array('t1_'.$attributeCode => $attribute['table']),
				'e.entity_id=t1_'.$attributeCode.'.entity_id AND t1_'.$attributeCode.'.store_id=0',
				array()
			)
			              ->where('t1_'.$attributeCode.'.attribute_id=?', $attribute['attribute_id']);

			if ($attribute['is_global']) {
				$this->_select->where('t1_'.$attributeCode.'.value'.$conditionRule, $value);
			} else {
				$this->_select->joinLeft(
					array('t2_'.$attributeCode => $attribute['table']),
					$this->_getWriteAdapter()->quoteInto('t1_'.$attributeCode.'.entity_id = t2_'.$attributeCode.'.entity_id AND t1_'.$attributeCode.'.attribute_id = t2_'.$attributeCode.'.attribute_id AND t2_'.$attributeCode.'.store_id=?', $storeId),
					array()
				)
				              ->where('IF(t2_'.$attributeCode.'.value_id>0, t2_'.$attributeCode.'.value, t1_'.$attributeCode.'.value)'.$conditionRule, $value);
			}
		}

		return $this->_select;
	}
*/
	/**
	 * Get category collection array
	 * Almost identical to parent, only added schrack_sts_statuslocal filter
	 *
	 * @return array
	 * @see Mage_Sitemap_Model_Mysql4_Catalog_Product::getCollection
	 */

/* Code seems to have no effect
	public function getCollection($storeId) {
		$products = array();

		$store = Mage::app()->getStore($storeId);
*/
		/* @var $store Mage_Core_Model_Store */
/* Code seems to have no effect
		if (!$store) {
			return false;
		}

		$urCondions = array(
			'e.entity_id=ur.product_id',
			'ur.category_id IS NULL',
			$this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
			$this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
		);
		$this->_select = $this->_getWriteAdapter()->select()
		                      ->from(array('e' => $this->getMainTable()), array($this->getIdFieldName()))
		                      ->join(
			                      array('w' => $this->getTable('catalog/product_website')),
			                      'e.entity_id=w.product_id',
			                      array()
		                      )
		                      ->where('w.website_id=?', $store->getWebsiteId())
		                      ->joinLeft(
			                      array('ur' => $this->getTable('core/url_rewrite')),
			                      join(' AND ', $urCondions),
			                      array('url' => 'request_path')
		                      );

		$this->_addFilter($storeId, 'visibility', Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds(), 'in');
		$this->_addFilter($storeId, 'status', Mage::getSingleton('catalog/product_status')->getVisibleStatusIds(), 'in');
		// Exclude dead products from sitemap
        $this->_addFilter($storeId, 'schrack_sts_statuslocal', 'tot', '!=');
        $this->_addFilter($storeId, 'schrack_sts_statuslocal', 'strategic_no', '!=');
        $this->_addFilter($storeId, 'schrack_sts_statuslocal', 'unsaleable', '!=');

		$query = $this->_getWriteAdapter()->query($this->_select);
		while ($row = $query->fetch()) {
			$product = $this->_prepareProduct($row);
			$products[$product->getId()] = $product;
		}

		return $products;
	}
*/
}

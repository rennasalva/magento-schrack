<?php

class Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection {

	/**
	 * Add a filter on products to collection select
	 *
	 * @param $products
	 * @return Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Product_Collection
	 */
    public function addProductFilter($products) {
        if (is_array($products) && !empty($products)) {
			// e = catalog_product_entity
            $this->getSelect()->where('e.entity_id IN (?)', $products);
		} elseif (is_string($products) || is_numeric($products)) {
            $this->getSelect()->where('e.entity_id=?', $products);
        }

        return $this;
    }
    
    public function addCategoryFilter(Mage_Catalog_Model_Category $category) {
        $rv = parent::addCategoryFilter($category);
        unset($this->_productLimitationFilters['category_is_anchor']);
        return $rv;
    }
            

	/**
	 * Retrieve all ids for collection in the original query's order
	 *
	 * @see app/code/core/Mage/Catalog/Model/Resource/Eav/Mysql4/Product/Collection.php
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
    public function getAllIds($limit=null, $offset=null) {
        $idsSelect = clone $this->getSelect();
		
        $idsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        
        $idsSelect->columns('e.'.$this->getEntity()->getIdFieldName());
        $idsSelect->limit($limit, $offset);
        
        
        $x = $idsSelect->__toString();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
    
    public function addAttributeToSort($attribute, $dir='asc')
    {
        if ($attribute == 'cat_index_position') {
            $this->getSelect()->order("{$attribute} {$dir}");
            return $this;
        } else {
            return parent::addAttributeToSort($attribute, $dir);
        }
    }
}

<?php

class Schracklive_SchrackCatalog_Helper_Category extends Mage_Catalog_Helper_Category {
/**
     * Retrieve current store categories
     *
     * @param   boolean|string $sorted
     * @param   boolean $asCollection
     * @return  Varien_Data_Tree_Node_Collection|Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection|array
     */
    public function getStoreCategories($sorted=false, $asCollection=false, $toLoad=true)
    {
        $parent     = Mage::app()->getStore()->getRootCategoryId();
        $cacheKey   = sprintf('%d-%d-%d-%d', $parent, $sorted, $asCollection, $toLoad);
        
        if (!isset($this->_storeCategories[$cacheKey])) {

            /**
             * Check if parent node of the store still exists
             */
            $category = Mage::getModel('catalog/category');
            /* @var $category Mage_Catalog_Model_Category */
            if (!$category->checkId($parent)) {
                if ($asCollection) {
                    return new Varien_Data_Collection();
                }
                return array();
            }

            $recursionLevel  = max(0, (int) Mage::app()->getStore()->getConfig('catalog/navigation/max_depth'));
            $storeCategories = $category->getCategories($parent, $recursionLevel, $sorted, $asCollection, $toLoad);
            $this->_storeCategories[$cacheKey] = $storeCategories;
        }
        $storeCategories = $this->_storeCategories[$cacheKey];
        $pillar = Mage::app()->getRequest()->getParam('schrackStrategicPillar');
        if (!isset($pillar) || !strlen($pillar)) {
            $ccId = Mage::app()->getRequest()->getParam('category', false);
            if ( $ccId ) {
                $cc = Mage::getModel('catalog/category')->load($ccId);
            } else {
                $cc = $this->getCurrentCategory();
            }
            $pillar = $cc->getRealPillar();
        }
        if ($pillar) {
            $filteredSCs = array();
            foreach ($storeCategories as $cat) {
                if (preg_match('/'.$pillar.'$/', $cat->getSchrackStrategicPillar())) {
                        $filteredSCs[] = $cat;
                }
            }
            $tree = new Varien_Data_Tree_Node_Collection($category);
            /** @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
            foreach ($filteredSCs as $sc) {
                    $tree->add($sc);
            }
            return $tree;
        } else
            return $storeCategories;
    }    

    
    /**
     * Enter description here...
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCurrentCategory()
    {
        if (Mage::getSingleton('catalog/layer')) {
            return Mage::getSingleton('catalog/layer')->getCurrentCategory();
        }
        return false;
    }

    /**
     * Retrieve child categories of current category
     *
     * @return Varien_Data_Tree_Node_Collection
     */
    public function getCurrentChildCategories()
    {
        $layer = Mage::getSingleton('catalog/layer');
        $category   = $layer->getCurrentCategory();
        /* @var $category Mage_Catalog_Model_Category */
        $categories = $category->getChildrenCategories();
        return $categories;
    }
}

?>

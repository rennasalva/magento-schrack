<?php

class MageDeveloper_TYPO3connect_CategoryController extends Mage_Core_Controller_Front_Action {    
/**
     * Category view action
     */
    public function viewAction() {
        if ($category = $this->_initCatagory()) {
            Mage::getSingleton('catalog/session')->setLastViewedCategoryId($category->getId());
        }
        $this->loadLayout();
        $html = $this->getLayout()->getBlock('catalog.leftnav')->toHtml();
        header('Content-Type:text/html; charset=UTF-8');
        echo($html);
        die;
    }
    
    /**
     * Initialize requested category object
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCatagory()
    {
        Mage::dispatchEvent('catalog_controller_category_init_before', array('controller_action' => $this));
        $categoryId = (int) $this->getRequest()->getParam('id', false);
        if (!$categoryId && !strlen($categoryId)) {
            return false;
        }

        $category = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($categoryId);

        if (!Mage::helper('catalog/category')->canShow($category)) {
            return false;
        }
        Mage::getSingleton('catalog/session')->setLastVisitedCategoryId($category->getId());
        Mage::register('current_category', $category);
        try {
            Mage::dispatchEvent(
                'catalog_controller_category_init_after',
                array(
                    'category' => $category,
                    'controller_action' => $this
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $category;
    }
}

?>

<?php

class Schracklive_SchrackCatalog_Block_Category_View extends Mage_Catalog_Block_Category_View {

    protected function _construct() {
        $this->setCacheKey('schracklive_schrackcatalog_category_view_'
            . $this->getRequest()->getParam('schrackStrategicPillar') . '_'
            . $this->getRequest()->getParam('id')
        );
    }
    
	protected function _prepareLayout() {
		$layout = parent::_prepareLayout();
        $category = $this->getCurrentCategory();

        Mage::helper('schrackcatalog/megamenu')->setPillarFromCategory($category);

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            // Query solr common core for info from other stores
            /** @var Schracklive_Search_Model_Search $solrSearch */
            $solrSearch = Mage::getModel('search/search');
            /** @var Solarium\QueryType\Select\Result\Document[] $hrefLangDocs */
            $hrefLangDocs = $solrSearch->getHrefLangDocs($category->getSchrackGroupId());
            if ($hrefLangDocs) {
                foreach ($hrefLangDocs as $hrefLangDoc) {
                    $data = $hrefLangDoc->getFields();
                    if ($data['country_stringS'] != 'com') {
                        $locale = $data['locale_stringS'];
                    } else {
                        $locale = 'x-default';
                    }
                    $headBlock->addItem('link_rel', $data['url_stringS'], 'rel="alternate" hreflang="'.$locale.'"');
                }
            }

            $currentCategory = Mage::registry('current_category');
            if ($currentCategory) {
                $helper = Mage::helper('typo3/category');
                $helper->setSchrackGroupId($currentCategory->getData('schrack_group_id'));
            }
		}
		return $layout;
	}
    
    /**
     * Get url for category data
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    public function getCategoryUrl($category)
    {
        if ($category instanceof Mage_Catalog_Model_Category) {
            $url = $category->getUrl();
        } else {
            $url = $this->_getCategoryInstance()
                ->setData($category->getData())
                ->getUrl();
        }

        return $url;
    }
    
    
    /**
     * Retrieve child categories of current category
     *
     * @return Varien_Data_Tree_Node_Collection
     */
    protected function getCurrentChildCategories()
    {
        return Mage::helper('catalog/category')->getCurrentChildCategories();
    }
}

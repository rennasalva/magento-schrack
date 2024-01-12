<?php

class Schracklive_SchrackCatalog_Block_Product_View extends Mage_Catalog_Block_Product_View {

	protected function _prepareLayout() {
		$layout = parent::_prepareLayout();
		$headBlock = $this->getLayout()->getBlock('head');
		if ($headBlock) {
			$product = $this->getProduct();
            Mage::helper('schrackcatalog/megamenu')->setPillarFromProduct($product);
            /** @var Schracklive_Search_Model_Search $solrSearch */
            $solrSearch = Mage::getModel('search/search');
            /** @var Solarium\QueryType\Select\Result\Document[] $hrefLangDocs */
            $hrefLangDocs = $solrSearch->getHrefLangDocs($product->getSku());
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
		}
		return $layout;
	}

    /**
     * Retrieve current product model
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if ( (!Mage::registry('product') && $this->getProductId()) ) {
            $product = Mage::getModel('catalog/product')->load($this->getProductId()); // Nagarro added new condition from 1.9.x core
            Mage::register('product', $product);
        }
        return Mage::registry('product');
    }
}

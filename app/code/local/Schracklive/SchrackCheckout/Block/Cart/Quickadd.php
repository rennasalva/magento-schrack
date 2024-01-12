<?php

/**
 * author: Erdinc Ayvere
 */

class Schracklive_SchrackCheckout_Block_Cart_Quickadd extends Mage_Catalog_Block_Product_Abstract
{
    protected function _toHtml()
    {
        $html = '';
        $suggestData = $this->getSuggestData();
        if (!($count = count($suggestData))) {
                return $html;
        }
        $index=0;
        $count--;
        $html = '<ul><li style="display:none"></li>';
        foreach( $suggestData as $product ) {            
            if( $index % 2 == 0 ) {
                $classtype = 'even';
            }
            else {
                $classtype = 'odd';
            }            
            if ($index == 0) {
                    $classtype .= ' first';
            }
            if ($index == $count) {
                    $classtype .= ' last';
            }			
            $html .= '<li title="'.$this->htmlEscape($product->getSku()).'" class="'.$classtype.'">'
                            .$this->htmlEscape($product->getSku()).'<span class="description">'.$product->getShortDescription().'</span></li>';            
            $index++;
        }		
        $html.= '</ul>';
        return $html;
    }
	
    public function getSuggestData() {
        $queryvarname = Mage::helper('schrackcheckout/quickadd')->getQueryParamName();
        $needle = $this->getQueryFromRequest();
        
        $productCollection = Mage::getModel('schrackcatalog/product')->getCollection();
        $productCollection = $productCollection->addAttributeToFilter( $queryvarname, array( 'like' => $needle .'%' ))->addAttributeToSort( $queryvarname, 'ASC' )->addStoreFilter();
        
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
		
        $check_limit = Mage::getStoreConfig("schrack/mobile/quickadd_articles");			
        if ((is_null($check_limit)) || !(is_numeric($check_limit)) || $check_limit <= 0) {			
                $check_limit = 12;
        }

        $productCollection->setPageSize($check_limit);
        return $productCollection;
    }
    
    public function getQueryFromRequest() {
        $searchTerm = trim(preg_replace('/\s+/', ' ',  Mage::app()->getRequest()->getParam(Mage::helper('schrackcheckout/quickadd')->getQueryParamName())));
        return $searchTerm;
    }
}

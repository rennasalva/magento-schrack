<?php

class Schracklive_SolrSearch_Helper_SchrackCatalogSearch_Data extends Schracklive_SchrackCatalogSearch_Helper_Data {

	public function getSuggestions() {
		$suggestions = new Varien_Data_Collection();
		foreach (Mage::getModel('solrsearch/autocomplete')->getSuggestions() as $suggestion) {
			$suggestions->addItem(new Varien_Object(array(
						'query_text' => $suggestion['title'],
						'num_results' => $suggestion['count'],
					)));
		}
		return $suggestions;
	}

	public function getProductCollection(Mage_Catalog_Model_Category $category) {
		$collection = false;
		$solr = Mage::getSingleton('solrsearch/search')->initData();
		$solrProducts = $solr->getProductIds();
		if (is_array($solrProducts)) {
			// Add dummy entity ID if solr result is empty, otherwise mage will return whole category
			if (count($solrProducts) == 0) {
				$solrProducts = array(9999999);
			}
			// $collection = Mage::getModel('catalog/product')->getCollection()
			$collection = Mage::getResourceModel('catalog/product_collection')
							->setStoreId($category->getStoreId())
							->addCategoryFilter($category)
							->addProductFilter($solrProducts);
            if ( $category->isDiscontinuedProductsCategory() ) {
                $collection->addAttributeToFilter('schrack_sts_forsale', array('=' => 1));
            }
		}

		return $collection;
	}

}

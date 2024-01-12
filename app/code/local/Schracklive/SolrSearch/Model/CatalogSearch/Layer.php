<?php

class Schracklive_SolrSearch_Model_CatalogSearch_Layer extends Schracklive_SchrackCatalogSearch_Model_Layer {

	/**
	 * Prepare product collection
	 *
	 * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
	 * @return Schracklive_SchrackCatalogSearch_Model_Layer
	 */
	public function prepareProductCollection($collection) {
		parent::prepareProductCollection($collection);
		$collection->setPageSize(0);
		return $this;
	}

}

?>
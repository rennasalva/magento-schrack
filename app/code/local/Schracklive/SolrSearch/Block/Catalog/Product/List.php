<?php

class Schracklive_SolrSearch_Block_Catalog_Product_List extends Schracklive_SchrackCatalog_Block_Product_List {

	protected $_requestFacets = null;

	public function showFacets() {
		return $this->isCategory() && $this->isFacetQuery();
	}

	public function isCategory() {
		$currentCategory = Mage::registry('current_category');
		return (is_object($currentCategory) && $currentCategory->getId());
	}

	/**
	 * Return if any facets were selected
	 *
	 * @return boolean
	 */
	public function isFacetQuery() {
		$this->_requestFacets = $this->_getFacetFieldsFromRequest();
		return (count($this->_requestFacets) > 0);
	}

	/**
	 * Get array with translated facets and matches
	 *
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @return array
	 */
	public function getFacetMatches($product) {
		$facetMatches = array();
		foreach ($this->_requestFacets as $facetKey => $facetTerms) {
            $facetKey = str_replace('__', '_',$facetKey);
			$productOptions = $product->getAttributeText($facetKey);
			if (empty($productOptions)) {
				continue;
			}
			if (!is_array($productOptions)) {
				$productOptions = array($productOptions);
			}
			foreach ($productOptions as $productOption) {
				if (isset($facetTerms[$productOption])) {
					$facetMatches[] = $productOption;
				}
			}
		}
		sort($facetMatches);
		return $facetMatches;
	}

	/**
	 * Get sorted array with selected facet fields (not values) from query
	 *
	 * @return array
	 */
	protected function _getFacetFieldsFromRequest() {
		if (!is_array($this->_requestFacets)) {
			$facetQuery = array();
			$this->_requestFacets = array();
			$fqReq = Mage::app()->getRequest()->getParam('fq');
			// @todo filter for valid facets (use configuration)
			if (isset($fqReq) && is_array($fqReq)) {
				foreach ($fqReq as $fq) {
					if (!empty($fq)) {
						$facet = explode(':', $fq);
						$this->_requestFacets[substr($facet[0], 0, strrpos($facet[0], '_'))][$facet[1]] = $facet[1];
					}
				}
			}
		}
		return $this->_requestFacets;
	}

}

?>
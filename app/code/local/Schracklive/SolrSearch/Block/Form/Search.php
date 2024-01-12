<?php

class Schracklive_SolrSearch_Block_Form_Search extends Mage_Core_Block_Template {

	const FACET_ACTIVE = 1;
	const FACET_QUERY = 2;

	/** @var Schracklive_SolrSearch_Model_Search */
	protected $_solr;

	public function __construct() {
		$this->_solr = Mage::getSingleton('solrsearch/search')->initData();
	}

	public function categoryHasItems() {
		$categoryId = $this->getCurrentCategoryId();
		if ($categoryId > 0) {
			$category = Mage::getModel('catalog/category')->load($categoryId);
			$categoryProducts = Mage::getModel('catalog/product')->getCollection()->addCategoryFilter($category);
			if (is_object($categoryProducts) && $categoryProducts->getSize() > 0) {
				return true;
			}
		}
		return false;
	}

	public function hasFacets() {
		if (is_array($this->_solr->getSolrResponseFacets())) {
			return true;
		} else {
			return false;
		}
	}
    
    public function hasActiveFacets() {
        if (!$this->hasFacets()) {
            return false;
        }
        foreach ($this->getFacets() as $facet => $entries) {
            $check = array_filter($entries, function($e) { return ($e['type'] == Schracklive_SolrSearch_Block_Form_Search::FACET_ACTIVE);});
            if ($check) {
                return true;
            }
        }
        return false;
    }

	public function getQueryFacets() {
		return $this->_solr->getQueryFacets();
	}

	public function getFacetsRequestString($glue = '&amp;') {
		return $this->_solr->getFacetsRequestString($glue);
	}

	public function getCurrentCategoryId() {
		return (is_object(Mage::registry('current_category')) ? Mage::registry('current_category')->getId() : 0);
	}

	public function getFacets() {
		$result = array();
		
		$urlParams = array('_current' => true,
			'_use_rewrite' => true,
			'_query' => array(
				'q' => Mage::app()->getRequest()->getParam('q'),
				'fq' => Mage::app()->getRequest()->getParam('fq'),
			),
		);
		
		$categoryFacets = $this->_solr->getCategoryFacets();
		$queryFacets = $this->_solr->getQueryFacets();
		$queryFacetKeys = $this->_getFacetKeys($queryFacets);
		$solrResponseFacets = $this->_solr->getSolrResponseFacets();
		$translationHelper = Mage::helper('catalog');
		$urlHelper = Mage::helper('schrackcore/url');
		$outputLabels = array(
			'Unknown' => $translationHelper->__('Unknown'),
			'Yes' => $translationHelper->__('Yes'),
			'No' => $translationHelper->__('No')
		);
		foreach ($solrResponseFacets as $responseFacetKey => $responseFacetTerms) {
			$facetOptionKeys = array_keys($responseFacetTerms);
			if ($responseFacetKey == 'sts_forsale') {
				if (count($facetOptionKeys) === 1) {
					continue;
				}
				unset($facetOptionKeys[array_search('false', $facetOptionKeys)]);
			}
			/** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
			$attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', substr($responseFacetKey, 0, strrpos($responseFacetKey, '_facet')));
			$attributeOptions = $attribute->getSource()->getAllOptions(true, true);
			if ($facetOptionKeys && isset($facetOptionKeys[0]) && (count($facetOptionKeys) > 1 || $facetOptionKeys[0] != 'Unknown')) {
				// Facet with options
				if (count($attributeOptions) > 1) {
					$options = $attributeOptions;
					$options[] = array('label' => 'Unknown');
				// Facet without options
				} else {
					$options = $facetOptionKeys;
					// Sort them
					natsort($options);
					// Reset index keys
					$options = array_values($options);
					// Move unknown option to last position if required
					if ($options[count($options)-1] != 'Unknown') {
						$unknownPos = array_search('Unknown', $options);
						if ($unknownPos !== false) {
							unset($options[$unknownPos]);
							$options[] = 'Unknown';
						}
					}
				}
			} else {
				continue;
			}
			foreach ($options as $option) {
				// Facet with options
				if (isset($option['label']) && isset($solrResponseFacets[$responseFacetKey][trim($option['label'])])) {
					$optionLabel = trim($option["label"]);
				// Facet without options (textfield, still returns 1 option)
				} elseif (count($attributeOptions) == 1) {
					$optionLabel = $option;
				} else {
					continue;
				}
				// Check if we have different output label
				$outputLabel = '';
				if (isset($outputLabels[$optionLabel])) {
					$outputLabel = $outputLabels[$optionLabel];
				} elseif ($responseFacetKey == 'sts_forsale') {
					if ($optionLabel === "true") {
						$outputLabel = $outputLabels['Yes'];
					} else {
						$outputLabel = $outputLabels['No'];
					}
				}
				if (in_array($responseFacetKey.':'.$optionLabel, $queryFacets)) {
					$result[$categoryFacets[$responseFacetKey]][$optionLabel] = array(
						'url' => $urlHelper->getUrlWithCurrentProtocol('*/*/*', $this->_removeFacet($urlParams, $responseFacetKey.':'.$optionLabel)),
						'label' => (!$outputLabel ? $optionLabel : $outputLabel),
						'count' => ($responseFacetTerms[$optionLabel] > 0 ? $responseFacetTerms[$optionLabel] : 2),
						'type' => self::FACET_ACTIVE,
					);
				// Special handling for single facets
				} elseif (in_array($responseFacetKey, $queryFacetKeys) && strpos($responseFacetKey, '_single') !== false) {
					$result[$categoryFacets[$responseFacetKey]][$optionLabel] = array(
						'url' => $urlHelper->getUrlWithCurrentProtocol('*/*/*', $this->_replaceFacet($urlParams, $responseFacetKey, $optionLabel)),
						'label' => (!$outputLabel ? $optionLabel : $outputLabel),
						'count' => $responseFacetTerms[$optionLabel],
						'type' => self::FACET_QUERY,
					);
				} elseif ($solrResponseFacets[$responseFacetKey][$optionLabel] > 0) {
                    if ( !isset($result) ) {
                        $result = array();
                    }
                    if ( isset($categoryFacets[$responseFacetKey]) ) {
                        if ( !isset($result[$categoryFacets[$responseFacetKey]]) ) {
                            $result[$categoryFacets[$responseFacetKey]] = array();
                        }
                        $result[$categoryFacets[$responseFacetKey]][$optionLabel] = array(
                            'url' => $urlHelper->getUrlWithCurrentProtocol('*/*/*', $this->_addFacet($urlParams, $responseFacetKey . ':' . $optionLabel)),
                            'label' => (!$outputLabel ? $optionLabel : $outputLabel),
                            'count' => $responseFacetTerms[$optionLabel],
                            'type' => self::FACET_QUERY,
                        );
                    }
				}
			}
			if (in_array($responseFacetKey, $queryFacetKeys)) {
				$result[$categoryFacets[$responseFacetKey]][] = array(
					'url' => $urlHelper->getUrlWithCurrentProtocol('*/*/*', $this->_removeFacets($urlParams, $responseFacetKey)),
					'label' => $this->__('All'),
					'count' => '*',
					'type' => self::FACET_QUERY,
				);
			}
		}
		return $result;
	}

	protected function _getFacetKeys($data) {
		$result = array();
		foreach ($data as $facetLimit) {
			$facet = explode(':', $facetLimit);
			$result[$facet[0]] = $facet[0];
		}
		return $result;
	}
	
	protected function _addFacet($urlParams, $facet) {
		if (!isset($urlParams['_query']['fq']) || !is_array($urlParams['_query']['fq'])) {
			$urlParams['_query']['fq'] = array();
		}
		$urlParams['_query']['fq'][] = $facet;
		return $urlParams;
	}
	
	protected function _removeFacet($urlParams, $facet) {
		if (isset($urlParams['_query']['fq']) && is_array($urlParams['_query']['fq'])) {
			$id = array_search($facet, $urlParams['_query']['fq']);
			if ($id !== false) {
				$urlParams['_query']['fq'][$id] = null;
			}
		}
		return $urlParams;
	}
	
	protected function _removeFacets($urlParams, $facetKey) {
		if (isset($urlParams['_query']['fq']) && is_array($urlParams['_query']['fq'])) {
			foreach($urlParams['_query']['fq'] as $id => $fq) {
				if (strstr($fq, $facetKey)) {
					unset($urlParams['_query']['fq'][$id]);
				}
			}
			// Reset array keys
			$urlParams['_query']['fq'] = array_values($urlParams['_query']['fq']);
		}
		return $urlParams;
	}
	
	protected function _replaceFacet($urlParams, $facetKey, $facetOption) {
		if (isset($urlParams['_query']['fq']) && is_array($urlParams['_query']['fq'])) {
			foreach($urlParams['_query']['fq'] as $id => $fq) {
				if (strstr($fq, $facetKey)) {
					$urlParams['_query']['fq'][$id] = $facetKey.':'.$facetOption;
					break;
				}
			}
		}
		return $urlParams;
	}

}

?>

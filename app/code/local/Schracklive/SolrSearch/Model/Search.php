<?php

class Schracklive_SolrSearch_Model_Search extends Mage_Core_Model_Abstract {

	const SEARCH_TYPE_GLOBAL = 0;
	const SEARCH_TYPE_CATEGORY = 1;
	const SEARCH_TYPE_PARENT = 2;

	protected $_searchType = self::SEARCH_TYPE_GLOBAL;
	protected $_currentCategoryId = null;
	protected $_categoryFacets = array();
	protected $_queryFields = 'entity_id,entity_id_intS';
	protected $_queryNeedle = '*:*';
	protected $_queryFacets = array();
	protected $_solrResponseFacets;
	protected $_solrResponseDocs;
	protected $_solrResponseEntityIds;
	protected $_initialized = false;

	/*public function __construct() {
		return $this;
	}*/

	/**
	 * Build solr query, process and save response when empty
	 */
	public function initData($needle = null, $categoryId = 0, $facets = array(), $fields = null) {
		// Immediately return $this if already initialized
		if ($this->_initialized) {
			return $this;
		}
		// Set correct needle
		if (!$needle) {
			$needle = $this->_getSolrQueryFromRequest();
			if ($needle) {
				$this->_queryNeedle = $needle;
			}
		} else {
			$this->_queryNeedle = Mage::helper('schrackcatalogsearch')->queryToString($needle);
		}
		$this->_lastQueryNeedle = $this->_queryNeedle;

		// Set category ID
		if ($categoryId === 0) {
			$currentCategory = Mage::registry('current_category');
			if (is_object($currentCategory)) {
				$this->_currentCategoryId = intval($currentCategory->getId());
				$this->_searchType = self::SEARCH_TYPE_CATEGORY;
			} elseif((int)Mage::app()->getRequest()->getParam('category')) {
				$this->_currentCategoryId = intval(Mage::app()->getRequest()->getParam('category'));
				$this->_searchType = self::SEARCH_TYPE_PARENT;
			}
		} else {
			$this->_currentCategoryId = $categoryId;
			$this->_searchType = self::SEARCH_TYPE_CATEGORY;
		}

		// Set Facets
		if (!$facets || count($facets) === 0) {
			$this->_queryFacets = $this->getFacetsFromRequest();
		} else {
			$this->_queryFacets = $facets;
		}

		// Set Fields
		if ($fields) {
			$this->_queryFields = $fields;
		}

		// Perform query
		if (!$this->_solrResponseEntityIds) {
			//$query = $this->_getSolrQueryFromRequest();
			$extra = $this->_getSolrExtraFromRequest();
			$solrReply = $this->_getSolrData($this->_queryNeedle, 'solrserver', $extra, 10000, 1);
			$this->_processSolrData($solrReply);
		}
		$this->_initialized = true;
		return $this;
	}

	/**
	 * Query alternate URLs from common solr core by product SKU / category group ID
	 * @param $key
	 * @return $this
	 */
	public function initDataHrefLang($key) {
		$solrReply = $this->_getSolrData('key_stringS:"'.$key.'"', 'solrserver_common', null, 20, 1);
		$this->_processSolrData($solrReply);
		return $this;
	}

	public function getLabel($attribute) {
		$attribute = substr($attribute, 0, strrpos($attribute, '_'));
		$attributeModel = Mage::getModel('catalog/entity_attribute');
		$entityType = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		return $attributeModel->loadByCode($entityType, $attribute)->getFrontendLabel();
	}

	public function getCategoryFacets() {
		return $this->_categoryFacets;
	}

	/**
	 * Get sorted array with selected facets from query
	 *
	 * @return array
	 */
	public function getFacetsFromRequest() {
		$fqReq = Mage::app()->getRequest()->getParam('fq');
		$facetQuery = array();
		if (isset($fqReq) && is_array($fqReq)) {
			foreach ($fqReq as $fq) {
				if (!empty($fq)) {
					$facetQuery[] = $fq;
				}
			}
			sort($facetQuery);
		}
		return $facetQuery;
	}

	/**
	 * Return facets request string for get
	 *
	 * @param string $glue
	 * @return string
	 */
	public function getFacetsRequestString($glue = '&amp;') {
		//$facets = $this->getFacetsFromRequest();
		if (count($this->_queryFacets) > 0) {
			$fq = '';
			foreach ($this->_queryFacets as $facet) {
				$fq .= $glue.'fq[]='.urlencode($facet);
			}
			return $fq;
		} else {
			return '';
		}
	}

	public function getQueryFacets() {
		return $this->_queryFacets;
	}

	/**
	 * Returns selected facets and options in multidimensional array
	 *
	 * @return array
	 */
	public function getSplitQueryFacets() {
		$queryFacets = array();
		foreach ($this->_queryFacets as $facet) {
			$facetTerm = explode(':', $facet);
			$queryFacets[$facetTerm[0]][$facetTerm[1]] = $facetTerm[1];
		}
		return $queryFacets;
	}

	/**
	 * @return array
	 */
	public function getSolrResponseFacets() {
		return $this->_solrResponseFacets;
	}

	/**
	 * @return Schracklive_SolrSearch_Model_Solr_Response
	 */
	public function getSolrResponseDocs() {
		return $this->_solrResponseDocs;
	}

	/**
	 * @return array
	 */
	public function getProductIds() {
		return $this->_solrResponseEntityIds;
	}

	/**
	 * Return facet array from solr response data
	 *
	 * @param Schracklive_SolrSearch_Model_Solr_Response $solrData
	 *
	 * @return array
	 */
	protected function _getSolrResponseFacets($solrData) {
		if (!isset($solrData) || !($solrData instanceof Schracklive_SolrSearch_Model_Solr_Response) || !isset($solrData->facet_counts)) {
			return array();
		}
		$facets = $this->_objectToArray($solrData->facet_counts->facet_fields);
		return $facets;
	}

	/**
	 * Build and return solr ready query string
	 *
	 * @return string
	 */
	protected function _getSolrQueryFromRequest() {
		return Mage::helper('schrackcatalogsearch')->getQueryFromRequest();
	}

	/**
	 * Build and return solr filter (selected facets, available facets, return fields)
	 *
	 * @return array
	 */
	protected function _getSolrExtraFromRequest() {
		// get request values
		$extra = array();
		$facetFields = array();
		$facetQuery = $this->_queryFacets;
		// Always add category/mage facets
		$facetQueryBase = array(
			'appKey:mage',
		);
		if ($this->_searchType == self::SEARCH_TYPE_CATEGORY) {
			/** @var Schracklive_SchrackCatalog_Model_Category $currentCategory */
			$currentCategory = Mage::registry('current_category');
			if (!is_object($currentCategory)) {
				$currentCategory = Mage::getModel('catalog/category')->load($this->_currentCategoryId);
			}
			if (is_object($currentCategory)) {
				/** @var Mage_Eav_Model_Config $eavConfig */
				$eavConfig = Mage::getSingleton("eav/config");
				$facets = explode(',', $currentCategory->getSchrackFacetList());
				$eavConfig->preloadAttributes('catalog_product', $facets);
				$categoryFacets = array();
				foreach ($facets as $facet) {
					$categoryFacets[$facet.'_facet'] = $eavConfig->getAttribute('catalog_product', $facet)->getFrontendLabel();
				}
				if (!$currentCategory->isDiscontinuedProductsCategory()) {
					$categoryFacets['sts_forsale'] = Mage::helper('catalog')->__('Discontinued');
				}
				$this->_categoryFacets = $categoryFacets;
				$facetFields = array_keys($this->_categoryFacets);
			}
			$facetQueryBase[] = 'category_id_intS:'.$this->_currentCategoryId;
		} elseif ($this->_searchType == self::SEARCH_TYPE_PARENT) {
			$this->_categoryFacets = array();
			$facetQueryBase[] = 'category_id_intM:'.$this->_currentCategoryId;
			$facetQueryBase[] = 'sts_forsale:true';
		} else {
			$facetFields = array('category_stringS');
		}
		$facetQuery = array_merge($facetQuery, $facetQueryBase);

		$extra['fl'] = $this->_queryFields;
		$extra['facet'] = 'true';
		$extra['facet.field'] = $facetFields;
		$extra['facet.mincount'] = 1;
		$extra['facet.method'] = 'fcs';
		//$extra['facet.limit'] = 15;
		if (!empty($facetQuery)) {
			$selectedFacets = array();
			foreach ($facetQuery as $fq) {
				$fq = explode(':', $fq);
				$selectedFacets[$fq[0]][] = '"'.addslashes($fq[1]).'"';
			}
			$facetQuery = array();
			foreach ($selectedFacets as $key => $val) {
				$tag = '';
				if ($key != 'appKey' && $key != 'category_id_intS') {
					$tag = '{!tag='.$key.'}';
					$facetArrid = array_search($key, $extra['facet.field']);
					if ($facetArrid !== false) {
						$extra['facet.field'][$facetArrid] = '{!ex='.$key.'}'.$extra['facet.field'][$facetArrid];
					}
				}
				$facetQuery[] = $tag.$key.':('.implode(' OR ', $val).')';
			}
			$extra['fq'] = $facetQuery;
		}
		return $extra;
	}

	/**
	 * Fetch solr data and store docs, facets & entity IDs
	 *
	 * @param $data
	 * @return null|void
	 */
	protected function _processSolrData($data) {
		// Abort if response is unexpected (server unreachable/broken)
		if (!is_object($data)) {
			return null;
		}
		$this->_solrResponseDocs = $data->response->docs;
		$this->_solrResponseFacets = $this->_getSolrResponseFacets($data);
		$productIds = array();
		foreach ($this->_solrResponseDocs as $doc) {
			// Fallback to old entity_id_intS field
			if (isset($doc->entity_id) && $doc->entity_id) {
				$productIds[] = intval($doc->entity_id);
			} else {
				$productIds[] = intval($doc->entity_id_intS);
			}
		}
		$this->_solrResponseEntityIds = $productIds;
	}

	/**
	 * Send search to Solr and return result
	 *
	 *
	 * @param string $query
	 * @param string $configKey
	 * @param array $extra array()
	 * @param int $perPage 10
	 * @param int $curPage 1
	 *
	 * @return Schracklive_SolrSearch_Model_Solr_Response|false
	 */
	protected function _getSolrData($query, $configKey = 'solrserver', $extra = array(), $perPage = 10, $curPage = 1) {
		$results = null;
		if (!Mage::getStoreConfig('schrack/solr/'.$configKey)) {
			return false;
		}
		// @todo check for broken URL
		$solrServerUrl = parse_url(Mage::getStoreConfig('schrack/solr/'.$configKey));
		// Return false if path is incomplete
		if (!isset($solrServerUrl['host']) || !isset($solrServerUrl['port']) || !isset($solrServerUrl['path'])) {
			return false;
		}
		Varien_Profiler::start('Schracklive_SolrSearch_Model_Search::_getSolrData()');
		$solr = new Schracklive_SolrSearch_Model_Solr_Service($solrServerUrl['host'], $solrServerUrl['port'], $solrServerUrl['path']);
		try {
			$results = $solr->search($query, $perPage * ($curPage - 1), $perPage, $extra);
		} catch (Exception $e) {
			Mage::logException($e);
			return false;
		}
		Varien_Profiler::stop('Schracklive_SolrSearch_Model_Search::_getSolrData()');
		return $results;
	}

	/**
	 * Return array data of object
	 *
	 * @param mixed $mixed
	 * @return array
	 */
	protected function _objectToArray($mixed) {
		if (is_object($mixed)) $mixed = (array)$mixed;
		if (is_array($mixed)) {
			$new = array();
			foreach ($mixed as $key => $val) {
				$key = preg_replace("/^\\0(.*)\\0/", "", $key);
				$new[$key] = $this->_objectToArray($val);
			}
		}
		else $new = $mixed;
		return $new;
	}

}

?>

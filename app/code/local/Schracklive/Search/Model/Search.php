<?php

use Solarium\Client;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\Plugin\ParallelExecution\ParallelExecution;
use Solarium\QueryType\Select\Query\Component\FacetSet;
use Solarium\QueryType\Select\Query\Component\Spellcheck;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Grouping\ValueGroup;
use Solarium\QueryType\Select\Result\Result;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Schracklive_Search_Model_Search extends Mage_Core_Model_Abstract
{
    const STRING_GLUE_CATEGORY = "\u{2063}|\u{2063}";
    const STRING_GLUE_BREADCRUMBS = "\u{2063}#\u{2063}";

    /** Natural products, sorted by relevancy */
    const PRODUCT_TYPE_ALL = 0;
    /** Sale products, ordered by relevancy */
    const PRODUCT_TYPE_SALE = 1;
    /** Dead products, ordered by relevancy */
    const PRODUCT_TYPE_DEAD = 2;

    const QUERY_TYPE_SEARCH = 1;
    const QUERY_TYPE_LIST = 2;

    const EXPERIMENT_FUZZY_SEARCH = 'fuzzy_search';

    protected $_client;
    protected $_commonClient;

    protected $highlightFacets = [
        'high_availability' => 'schrack_sts_is_high_available_boolS',
        'on_sale' => 'sts_forsale'
    ];

    public function __construct()
    {
        parent::__construct();
        // Set the default config
        $this->setData(array(
            'query_type' => self::QUERY_TYPE_LIST,
            'select_fields' => 'id,entity_id,sku_textTS,schrack_long_text_addition_facet,short_description_textS,url_path_full_stringS,image_path_stringS,thumbnail_path_stringS,sts_forsale,category_name_textS,is_restricted_boolS,schrack_sts_is_download_facet,download_path_energy_label_stringS,download_path_datasheet_stringS,schrack_newenergieeffizienzkl_stringS,download_path_stringS,download_type_stringS,download_size_stringS,schrack_sts_statuslocal_facet,main_packing_unit_name_stringS,related_skus_textTM,schrack_main_producer,schrack_main_category_id_for_tagmanager',
            'facets' => array(),
            'sale_limit' => 5,
            'pages_limit' => 20,
            'start' => 0,
            'query' => '*',
            'query_parsed' => '*',
            'sort' => '',
            'sort_order' => 'asc'
        ));
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        $this->setQueryType(self::QUERY_TYPE_LIST);
        $client = $this->_getClient();
        if (!$this->getCategoryHasProducts()) {
            $this->setLimit(0);
            $this->setSaleLimit(0);
        }
        $query = $this->_getQueryProducts($client);
        try {
            $result = $client->select($query);
        } catch (Exception $result) { }
        $products = array(
            'status' => array(),
            'products' => array(),
            'categories' => array(),
            'breadcrumbs' => array(),
            'facets' => array()
        );
        $products['status'] = $this->_parseStatus($result);
        if (!$products['status']['error']) {
            $products['products'] = $this->_parseProducts($result);
            $categories = $this->_parseCategories($result, (int)$products['status']['count'], true);
            // We don't want the current category for the product list
            $categories = $categories['children'];
            // Strip category children for product list
            foreach ($categories as $id => $category) {
                unset($categories[$id]['children']);
            }
            $products['categories'] = $categories;
            $products['breadcrumbs'] = $this->_parseBreadcrumbs($result);
            $products['facets'] = $this->_parseFacets($result);
            $products['highlightFacets'] = $this->_parseHighlightFacets($result);
        }
        return $products;
    }

    /**
     * @return array
     */
    public function getSkus()
    {
        $this->setQueryType(self::QUERY_TYPE_SEARCH);
        $this->setLimit(1000);
        $client = $this->_getClient();

        // SKU query is preferred
        if ($this->getQueryParsedSku()
            && strlen($this->getQuery()) >= 8
            && strlen($this->getQuery()) <= 10
        ) {
            $skuQuery = $this->_getQueryProducts($client);
            $skuQuery->setQuery($this->getQueryParsedSku());
            $skuQuery->setFields(['sku']);
            $skuQuery->getFilterQuery('schrack_sts_statuslocal')->setQuery('schrack_sts_statuslocal_facet:std');
            $skuResults = $client->execute($skuQuery);
            $products['status'] = $this->_parseStatus($skuResults);
            $products['skus'] = $this->_parseSkus($skuResults);
        }

        // If there was no SKU query, or it didn't return any results, do a regular full-text search
        if (!isset($products['skus']) || !$products['skus']) {
            $productsQuery = $this->_getQueryProducts($client, self::PRODUCT_TYPE_ALL, true);
            $productsQuery->setFields(['sku']);
            $productsQuery->getFilterQuery('schrack_sts_statuslocal')->setQuery('schrack_sts_statuslocal_facet:std');
            $results = $client->execute($productsQuery);
            $products['status'] = $this->_parseStatus($results);
            $products['skus'] = $this->_parseSkus($results);
        }

        return $products;
    }

    /**
     * @return array[]
     * @throws Mage_Core_Model_Store_Exception|Mage_Core_Exception
     */
    public function getResults()
    {
        $this->setQueryType(self::QUERY_TYPE_SEARCH);
        $client = $this->_getClient();
        $productsQuery = $this->_getQueryProducts($client, self::PRODUCT_TYPE_ALL, true);
        $this->_addQueryHighlighting($productsQuery);
        $saleProductsQuery = $this->_getQueryProducts($client, self::PRODUCT_TYPE_SALE);
        $this->_addQueryHighlighting($saleProductsQuery);
        $pagesQuery = $this->_getQueryPages($client);
        $this->_addQueryHighlighting($pagesQuery, '_pages');
        /** @var ParallelExecution $parallelQuery */
        $parallelQuery = $client->getPlugin('parallelexecution');
        $parallelQuery->setOptions(array('curlmultiselecttimeout' => 2));
        if ($this->getQueryParsedCustomSku()
            || ($this->getQueryParsedSku() && strlen($this->getQuery()) >= 8 && strlen($this->getQuery()) <= 10)
        ) {
            // Create normal product query, then override term, and remove sts status and category restriction
            $skuQuery = $this->_getQueryProducts($client);
            if ( $this->getQueryParsedCustomSku() ) {
                $skuQuery->setQuery($this->getQueryParsedCustomSku());
            } else {
                $skuQuery->setQuery($this->getQueryParsedSku());
            }
            $skuQuery->removeFilterQuery('schrack_sts_statuslocal');
            $skuQuery->removeFilterQuery('category');
            $skuQuery->addSort('schrack_sts_statuslocal_facet', $skuQuery::SORT_ASC);
            $skuGroup = $skuQuery->getGrouping();
            $skuGroup->setNumberOfGroups(true);
            $skuGroup->addField('sku');
            $parallelQuery->addQuery('skuProducts', $skuQuery);
        }
        $parallelQuery->addQuery('products', $productsQuery);
        $parallelQuery->addQuery('sale_products', $saleProductsQuery);
        $parallelQuery->addQuery('pages', $pagesQuery);
        /** @var Result[] $results */
        $results = $parallelQuery->execute();
        $products = array(
            'status' => array(),
            'products' => array(),
            'categories' => array(),
            'facets' => array(),
            'saleStatus' => array(),
            'saleProducts' => array(),
            'pagesStatus' => array(),
            'pages' => array()
        );
        // If we have a SKU match, prefer that
        if (isset($results['skuProducts'])) {
            $products['status'] = $this->_parseStatus($results['skuProducts']);
            if (!$products['status']['error'] && $products['status']['count'] > 0) {
                // Check if the Products have related_skus_textTM
                $relatedSkus = false;
                foreach ($results['skuProducts']->getGrouping()->getGroup('sku') as $group) {
                    $groupDocuments = $group->getDocuments();
                    $document = reset($groupDocuments);
                    if ($document->related_skus_textTM && ! $this->getQueryParsedCustomSku()) {
                        $relatedSkus = true;
                    }
                }
                if(!$relatedSkus) {
                    $parsedSkuProducts = $this->_parseProducts($results['skuProducts']);
                    if ($parsedSkuProducts) {
                        $products['products'] = $parsedSkuProducts;
                        // Only skip rest of the parsing for exactly 1 match, otherwise continue on for filters
                        if ($products['status']['count'] === 1) {
                            return $products;
                        }
                        $categories = $this->_parseCategories($results['skuProducts'], (int)$products['status']['count'], false);
                    }
                }
                unset($documents, $relatedSkus);
            }
        }
        if (!$products['products']) {
            $products['status'] = $this->_parseStatus($results['products']);
        }
        if (!$products['status']['error']) {
            if (!$products['products']) {
                $products['products'] = $this->_parseProducts($results['products']);
                $categories = $this->_parseCategories($results['products'], (int)$products['status']['count'], false);
            }
            // Strip category children
            if (isset($categories['children']) && is_array($categories['children'])) {
                foreach ($categories['children'] as $id => $category) {
                    unset($categories['children'][$id]['children']);
                }
            }
            $products['categories'] = $categories;
            // User supplied category ID => Return info in status
            if ($this->getCategory() != Mage::app()->getStore()->getRootCategoryId()) {
                $products['status']['categoryId'] = $products['categories']['id'];
                $products['status']['categoryName'] = $products['categories']['name'];
            }
            $products['categories'] = $products['categories']['children'];
            $productsQuery = $this->_getQueryProducts($client);
            $productsQuery->setFields(array());
            $productsQuery->setRows(0);
            $productsQuery->getFacetSet()->removeFacet('category_breadcrumbs_stringS');
            $results['products'] = $client->execute($productsQuery);
            $products['facets'] = $this->_parseFacets($results['products']);
            $products['highlightFacets'] = $this->_parseHighlightFacets($results['products']);
        }
        $products['saleStatus'] = $this->_parseStatus($results['sale_products']);
        if (!$products['saleStatus']['error']) {
            $products['saleProducts'] = $this->_parseProducts($results['sale_products']);
        }
        $products['pagesStatus'] = $this->_parseStatus($results['pages']);
        if (!$products['pagesStatus']['error']) {
            $products['pages'] = $this->_parsePages($results['pages']);
        }
        // No products found, check if there are matching dead products
        if (!$products['status']['count'] && !$products['saleStatus']['count']) {
            try {
                $queryDeadProducts = $client->select($this->_getQueryProducts($client, self::PRODUCT_TYPE_DEAD));
            } catch (Exception $queryDeadProducts) { }
            $products['status'] = $this->_parseStatus($queryDeadProducts);
            if (!$products['status']['error']) {
                $products['products'] = $this->_parseProducts($queryDeadProducts);
            }
        }
        // Get spelling suggestions if still no match
        if (!$products['status']['count'] && !$products['saleStatus']['count'] && !$products['pagesStatus']['count']) {
            $querySpellcheck = $client->select($this->_getQuerySpellcheck($client));
            // Add all entered search terms as seperate potential suggestions
            $potentialSuggestions = array();
            $queryTerms = explode(' ', $this->getQuery());
            foreach ($queryTerms as $queryTerm) {
                $potentialSuggestions[] = array('query' => $queryTerm);
            }
            $potentialSuggestions = array_merge($potentialSuggestions, $this->_parseSpellcheck($querySpellcheck));
            foreach ($potentialSuggestions as $row => $spellcheckCollation) {
                $spellcheckQuery = $client->createSelect();
                $spellcheckQuery->setRows(0);
                $spellcheckQuery->addParam('qf', Mage::getStoreConfig('schrack/solr/query_fields'));
                // Use - instead of NOT, as it leads to unexpected results otherwise
                $spellcheckQuery->createFilterQuery('schrack_sts_statuslocal')->setQuery('-schrack_sts_statuslocal_facet:(tot OR strategic_no OR unsaleable)');
                $spellcheckQuery->setQuery($this->_formatQuery($spellcheckCollation['query']));
                $querySpellcheck = $client->select($spellcheckQuery);
                if (!$querySpellcheck->getNumFound()) {
                    unset($potentialSuggestions[$row]);
                } else {
                    $potentialSuggestions[$row]['hits'] = $querySpellcheck->getNumFound();
                }
            }
            $products['suggestions'] = $potentialSuggestions;
        }
        return $products;
    }

    /**
     * Query alternate URLs from common solr core by product SKU / category group ID
     * @param $key
     * @return array
     */
    public function getHrefLangDocs($key)
    {
        $result = [];

        $client = $this->_getCommonClient();
        if ($client) {
            $select = $client->createSelect();
            $select->setQuery('key_stringS:"' . $key . '"');
            $response = $client->execute($select);
            $status = $this->_parseStatus($response);
            if (!$status['error']) {
                $result = $response->getDocuments();
            }
        }

        return $result;
    }

    public function setQuery($query)
    {
        $this->setData('query', $query);
        $this->setQueryParsed($this->_formatQuery($query));
        // Only query first term as phrase for dead products
        $terms = explode(' ', str_replace(['"', '(', ')', '*'], '', $this->getQueryParsed()));
        // Remove empty array entries
        $terms = array_filter($terms, static function ($value) {
            return !is_null($value) && $value !== '';
        });
        if (!$terms) {
            $terms = array('weneverwanttofindthisanywhere');
        }
        $skuQuery = 'sku:' . str_pad(strtoupper($terms[0]), 10, '?');
        if (strlen($terms[0]) !== 10) {
            $skuQuery .= ' AND -schrack_sts_statuslocal_facet:(tot OR strategic_no OR unsaleable)';
        }
        $customSkuQuery = $this->getCustomSkuMapping($terms);
        if ( $customSkuQuery ) {
            $this->setQueryParsedCustomSku($customSkuQuery);
        }
        if (count($terms) === 5) {
            $this->setQueryParsedSku($skuQuery);
        }
        $this->setQueryParsedDead($skuQuery);
        return $this;
    }

    // Do some cleanup of the passed arguments (HTML injections!)
    public function setFacets($facets) {
        if (!$facets || !is_array($facets)) {
            return $this;
        }
        foreach ($facets as $facetName => &$facetOptions) {
            $cleanedFacetName = strip_tags($facetName);
            if ($facetName !== $cleanedFacetName) {
                unset($facets[$facetName]);
                if ($cleanedFacetName) {
                    $facets[$cleanedFacetName] = $facetOptions;
                }
            }
            foreach ($facetOptions as $facetOptionRow => &$facetOption) {
                if (stristr($facetOption, '<sub>')) {
                    // Makes an exception of cleanup for lower placed special schrack_kategorie (e.g. 'Cat. 6<sub>A</sub>')
                } else {
                    $facetOption = strip_tags($facetOption);
                }
                if (!$facetOption) {
                    unset($facetOptions[$facetOptionRow]);
                }
            }
        }
        $this->setData('facets', $facets);
        return $this;
    }

    public function getCategory() {
        $category = parent::getCategory();
        if (!$category) {
            $category = Mage::app()->getStore()->getRootCategoryId();
            $this->setRootCategoryId($category);
            parent::setCategory($category);
        }
        return $category;
    }

    public function getLimit() {
        $limit = parent::getLimit();
        if (!is_int($limit)) {
            $limit = (int)Mage::getStoreConfig('schrack/solr/query_result_limit');
            $this->setLimit($limit);
        }
        return $limit;
    }

    protected function isExperimentEnabled($experiment)
    {
        $experiments = explode(',', Mage::getStoreConfig('schrack/solr/experiments'));
        return in_array($experiment, $experiments, true);
    }

    /**
     * @return null|Client
     */
    protected function _getClient()
    {
        if (!$this->_client) {
            $serverUrl = Mage::getStoreConfig('schrack/solr/solrserver');
            if (!$serverUrl) {
                return null;
            }
            $solrServerUrl = parse_url($serverUrl);
            // Return null if path is incomplete
            if (!isset($solrServerUrl['host']) || !isset($solrServerUrl['port']) || !isset($solrServerUrl['path'])) {
                return null;
            }
            $solrServerUrl['username'] = 'read';
            $solrServerUrl['password'] = Mage::getStoreConfig('schrack/solr/read_password');
            $config = array(
                'endpoint' => array(
                    'localhost' => $solrServerUrl,
                ),
            );

            //modify sintax
            $adapter = new Solarium\Core\Client\Adapter\Curl();
            $eventDispatcher = new EventDispatcher();
            $client = new Client($adapter, $eventDispatcher,$config);
            $client->getPlugin('postbigrequest');
            $this->_client = $client;
        }

        return $this->_client;
    }

    /**
     * @return null|Client
     */
    protected function _getCommonClient()
    {
        if (!$this->_commonClient) {
            $serverUrl = Mage::getStoreConfig('schrack/solr/solrserver_common');
            if (!$serverUrl) {
                return null;
            }
            $solrServerUrl = parse_url($serverUrl);
            // Return null if path is incomplete
            if (!isset($solrServerUrl['host']) || !isset($solrServerUrl['port']) || !isset($solrServerUrl['path'])) {
                return null;
            }
            $solrServerUrl['username'] = 'read';
            $solrServerUrl['password'] = Mage::getStoreConfig('schrack/solr/read_password');
            $config = array(
                'endpoint' => array(
                    'localhost' => $solrServerUrl,
                ),
            );

            $adapter = new Solarium\Core\Client\Adapter\Curl();
            $eventDispatcher = new EventDispatcher();
            $this->_commonClient =  new Client($adapter, $eventDispatcher,$config);
        }

        return $this->_commonClient;
    }

    public function getCategoryHasProducts() {
        if (!$this->hasCategoryHasProducts()) {
            $category = $this->getCategoryModel();
            if (is_object($category) && $category->getId()) {
                $this->setCategoryHasProducts(($category->getProductCount() > 0 ? true : false));
            }
        }
        return parent::getCategoryHasProducts();
    }

    public function getCategoryFacets() {
        if ($this->hasCategoryFacets()) {
            $categoryFacets = parent::getCategoryFacets();
        } else {
            $categoryFacets = array();
            $category = $this->getCategoryModel();
            if (is_object($category) && $category->getId()) {
                /** @var Mage_Eav_Model_Config $eavConfig */
                $eavConfig = Mage::getSingleton("eav/config");
                $facetList = $category->getSchrackFacetList();
                if ($facetList) {
                    $facets = explode(',', $facetList);
                    $eavConfig->preloadAttributes('catalog_product', $facets);
                    foreach ($facets as $facet) {
                        if ($facet) {
                            $categoryFacets[$facet] = $eavConfig->getAttribute('catalog_product', $facet)->getFrontendLabel();
                        }
                    }
                }
            }
            $this->setCategoryFacets($categoryFacets);
        }

        // Always add user selected facets, no matter the category config
        $activeFacets = $this->getFacets();
        if ($activeFacets) {
            /** @var Mage_Eav_Model_Config $eavConfig */
            $eavConfig = Mage::getSingleton('eav/config');
            foreach ($activeFacets as $facetKey => $facetOptions) {
                if (!isset($categoryFacets[$facetKey])) {
                    $categoryFacets[$facetKey] = $eavConfig->getAttribute('catalog_product', $facetKey)->getFrontendLabel();
                }
            }
        }

        return $categoryFacets;
    }

    /**
     * @return Schracklive_SchrackCatalog_Model_Category
     */
    public function getCategoryModel() {
        /** @var Schracklive_SchrackCatalog_Model_Category $currentCategory */
        $category = parent::getCategoryModel();
        if (!$category) {
            $category = Mage::getModel('catalog/category')->load($this->getCategory());
            parent::setCategoryModel($category);
        }
        return $category;
    }

    /**
     * @param Client $solrClient
     * @param int $productType
     * @param bool $skipCategoryFacets
     * @return Query
     */
    private function _getQueryProducts(Client $solrClient, int $productType = self::PRODUCT_TYPE_ALL, bool $skipCategoryFacets = false)
    {
        $solrQuery = $solrClient->createSelect();
        if ($productType === self::PRODUCT_TYPE_SALE) {
            $rows = $this->getSaleLimit();
            $staticOrder = Mage::getStoreConfig('schrack/solr/query_static_order_sale');
            $boost = Mage::getStoreConfig('schrack/solr/query_boost_sale');
        } else {
            $rows = $this->getLimit();
            $staticOrder = false;
            $boost = Mage::getStoreConfig('schrack/solr/query_boost');
        }
        $solrQuery->setRows($rows);
        // Always get schrack_sts_is_accessory_boolS:false first
        $solrQuery->addSort('schrack_sts_is_accessory_boolS', 'asc');
        if (!$staticOrder) {
            $addBoost = true;
            if ($this->getSort() && $this->getSortOrder()) {
                if ($this->getSort() === 'ranking') {
                    $solrQuery->addSort('score', 'desc');
                } elseif ($this->getSort() === 'score') {
                    $solrQuery->addSort('score', 'desc');
                    $addBoost = false;
                } else {
                    $solrQuery->addSort($this->getSort(), $this->getSortOrder());
                    $addBoost = false;
                }
            }
            if ($addBoost) {
                $eDisMax = $solrQuery->getEDisMax();
                if ($boost) {
                    $eDisMax->setBoostFunctions($boost);
                }
                $boostQueryWws = Mage::getStoreConfig('schrack/solr/query_boost_query');
                if ($boostQueryWws) {
                    $eDisMax->addBoostQuery(array('key' => 0, 'query' => $boostQueryWws));
                }
            }
        } else {
            $solrQuery->addSort('schrack_wws_ranking_intS', 'asc');
        }
        $solrQuery->setStart($this->getStart());
        $solrQuery->addParam('qf', Mage::getStoreConfig('schrack/solr/query_fields'));
        $solrQuery->addParam('facet.method', 'fcs');
        $solrQuery->setFields(array($this->getSelectFields()));
        $solrQuery->createFilterQuery('type')->setQuery('type:products');
        if ($productType != self::PRODUCT_TYPE_DEAD) {
            $solrQuery->setQuery($this->getQueryParsed());
            $solrQuery->createFilterQuery('category')->setQuery('category_ids_intM:' . $this->getCategory());
            $solrQuery->createFilterQuery('schrack_sts_statuslocal')->setQuery('schrack_sts_statuslocal_facet:(std OR istausl OR wirdausl OR gesperrt)');
        } else {
            $solrQuery->createFilterQuery('sku')->setQuery($this->getQueryParsedDead());
            $solrQuery->createFilterQuery('schrack_sts_statuslocal')->setQuery('schrack_sts_statuslocal_facet:(tot OR strategic_no OR unsaleable)');
        }

        // Apply user selected facets
        $facets = $this->getFacets();
        if ($facets) {
            foreach ($facets as $facetKey => $facetOptions) {
                foreach ($facetOptions as $idx => $facetOption) {
                    $facetOptions[$idx] = $facetOption;
                }
                $solrQuery->createFilterQuery($facetKey)->setQuery($facetKey . '_facet:("'. implode('" OR "', $facetOptions) . '")')->addTag($facetKey);
            }
        }

        // Apply general filters
        if ($this->hasSkuList()) {
            $solrQuery->addParam('fq', '{!terms f=sku}' . implode(',', $this->getSkuList()));
        }

        // Apply high availability filter
        if ($this->hasHighAvailability()) {
            $solrQuery->createFilterQuery('schrack_sts_is_high_available')->setQuery('schrack_sts_is_high_available_boolS:' . (int)$this->getHighAvailability());
        }

        // Apply sale filter
        $sale = $this->getSale();
        if ($productType == self::PRODUCT_TYPE_SALE) {
            $sale = 1;
        }
        if ($sale !== null) {
            $solrQuery->createFilterQuery('sts_forsale')->setQuery('sts_forsale:' . (int)$sale);
        }

        // Group by SKU
        $group = $solrQuery->getGrouping();
        $group->setNumberOfGroups(true);
        $group->addField('sku');

        if ($productType === self::PRODUCT_TYPE_ALL) {
            /** @var FacetSet $facetSet */
            $facetSet = $solrQuery->getFacetSet();
            $facetSet->setMinCount(1);

            // Add highlight facets
            foreach ($this->highlightFacets as $alias => $solrField) {
                $facetSet->createFacetField($alias)
                    ->setField($solrField);
            }

            // Select facets configured for category
            $categoryFacets = $this->getCategoryFacets();
            if (!$skipCategoryFacets && $categoryFacets) {
                foreach ($categoryFacets as $categoryFacetName => $categoryFacetLabel) {
                    if ($categoryFacetName) {
                        $facet = $facetSet->createFacetField($categoryFacetName)
                            ->setField($categoryFacetName . '_facet');
                        if (is_array($facets) && in_array($categoryFacetName, array_keys($facets))) {
                            $facet->addExclude($categoryFacetName);
                        }
                    }
                }
            }

            // Select category facet
            $facetSet->createFacetField('category_breadcrumbs_stringS')
                ->setField('category_breadcrumbs_stringS')
                ->setLimit(500);
        }

        return $solrQuery;
    }

    /**
     * @param Client $solrClient
     * @return Query
     */
    private function _getQueryPages(Client $solrClient)
    {
        $solrQuery = $solrClient->createSelect();
        $solrQuery->setRows($this->getPagesLimit());
        $solrQuery->setStart($this->getStart());
        $solrQuery->addParam('qf', Mage::getStoreConfig('schrack/solr/query_fields_pages'));
        $solrQuery->addParam('facet.method', 'fcs');
        // Search the entered term, if one is supplied. Only fall back to * if no child category is selected
        if ($this->getQuery() || !$this->getCategory() || $this->getCategory() === Mage::app()->getStore()->getRootCategoryId()) {
            $solrQuery->setQuery($this->getQueryParsed());
        } else { // Otherwise, search the category name
            $solrQuery->setQuery($this->_formatQuery($this->getCategoryModel()->getName()));
        }
        $typo3Url = parse_url(Mage::getStoreConfig('schrack/typo3/typo3url'));
        $solrQuery->setFields(array('*'));
        $solrQuery->createFilterQuery('type')->setQuery('type:pages');
        $solrQuery->createFilterQuery('access')->setQuery('access:"c:0"');
        $solrQuery->createFilterQuery('site')->setQuery('site:"' . $typo3Url['host'] . '"');
        return $solrQuery;
    }

    /**
     * @param Client $solrClient
     * @return Query
     */
    private function _getQuerySpellcheck(Client $solrClient)
    {
        $solrQuery = $solrClient->createSelect();
        $solrQuery->setRows(0);
        $solrQuery->setQuery($this->getQuery());
        /** @var Spellcheck $spellcheck */
        $spellcheck = $solrQuery->getSpellcheck();
        $spellcheck->setCount(2);
        $spellcheck->setCollate(true);
        $spellcheck->setMaxCollations(2);
        $spellcheck->setMaxCollationTries(3);
        $spellcheck->setExtendedResults(true);
        $spellcheck->setCollateExtendedResults(true);
        $spellcheck->setOnlyMorePopular(true);
        $spellcheck->setCollateParam('mm', '100%');
        $spellcheck->setBuild(false);

        return $solrQuery;
    }

    /**
     * @param ResultInterface $solrResponse
     * @return array
     */
    private function _parseStatus($solrResponse)
    {
        $status = array(
            'limit' => $this->getLimit(),
            'start' => $this->getStart(),
            'error' => false,
            'count' => 0
        );
        if ($solrResponse instanceof \Solarium\Exception\HttpException || $solrResponse->getResponse()->getStatusCode() != 200) {
            $status['error'] = true;
            return $status;
        }
        if ($solrResponse->getGrouping() && $solrResponse->getGrouping()->getGroup('sku')) {
            $status['count'] = $solrResponse->getGrouping()->getGroup('sku')->getNumberOfGroups();
        } else {
            $status['count'] = $solrResponse->getNumFound();
        }
        return $status;
    }

    /**
     * @param  ResultInterface $resultProducts
     * @return array
     */
    private function _parseSkus($resultProducts)
    {
        $skus = [];

        /** @var ValueGroup $group */
        foreach ($resultProducts->getGrouping()->getGroup('sku') as $group) {
            $groupDocuments = $group->getDocuments();
            $document = reset($groupDocuments);
            $skus[] = $document->sku;
        }
        return $skus;
    }

    /**
     * @param  ResultInterface $resultProducts
     * @return array
     */
    private function _parseProducts($resultProducts)
    {
        $highlighting = $resultProducts->getHighlighting();

        $products = array();
        /** @var ValueGroup $group */
        foreach ($resultProducts->getGrouping()->getGroup('sku') as $group) {
            $groupDocuments = $group->getDocuments();
            $document = reset($groupDocuments);
            $detailDescription = $document->schrack_long_text_addition_facet;
            if ($detailDescription && is_array($detailDescription)) {
                $detailDescription = implode(' ', $detailDescription);
            }
            // Check if we got highlighting
            $highlights = array();
            if ($highlighting instanceof \Solarium\QueryType\Select\Result\Highlighting\Highlighting) {
                foreach ($highlighting->getResult($document->id) as $field => $highlight) {
                    $highlights[$field] = implode(' (&hellip;) ', $highlight);
                }
            }
            $isDownload = false;
            if (is_array($document->schrack_sts_is_download_facet)) {
                $isDownload = (bool)$document->schrack_sts_is_download_facet[0];
            }
            $product = array(
                'id' => $document->entity_id,
                'sku' => (isset($highlights['sku_textTS']) ? $highlights['sku_textTS'] : $document->sku_textTS),
                'name' => str_replace('-','&#8209;',isset($highlights['description_textS']) ? $highlights['description_textS'] : $document->short_description_textS),
                'image' => null,
                'thumbnail' => null,
                'category' => str_replace('-','&#8209;',isset($highlights['category_name_textS']) ? $highlights['category_name_textS'] : $document->category_name_textS),
                'detailDescription' => $detailDescription,
                'mainPackingUnit' => $document->main_packing_unit_name_stringS,
                'sale' => ($document->sts_forsale ? true : false),
                'path' => $document->url_path_full_stringS,
                'isDead' => (count(array_diff(array('tot', 'strategic_no', 'unsaleable'), $document->schrack_sts_statuslocal_facet)) !== 3 ? true : false),
                'isTotOnly' => (count(array_diff(array('tot'), $document->schrack_sts_statuslocal_facet)) !== 1 ? true : false),
                'isNotSaleable' => (count(array_diff(array('strategic_no', 'unsaleable', 'gesperrt'), $document->schrack_sts_statuslocal_facet)) !== 3 ? true : false),
                'isRestricted' => $document->is_restricted_boolS,
                'isDownload' => $isDownload,
                'downloadLink' => $document->download_path_stringS,
                'downloadDataSheetLink' => $document->download_path_datasheet_stringS,
                'downloadEnergyLabelLink' => $document->download_path_energy_label_stringS,
                'energyLabel' => $document->schrack_newenergieeffizienzkl_stringS,
                'schrackMainProducer' => $document->schrack_main_producer,
                'schrackMainCategoryIdForTagmanager' => $document->schrack_main_category_id_for_tagmanager
            );
            // Use SKU as fallback for name
            if (!$product['name']) {
                $product['name'] = $document->sku_textTS;
            }
            if ($document->thumbnail_path_stringS) {
                $product['image'] = preg_replace('#foto/#', 'thumb400/', $document->image_path_stringS);
                $product['thumbnail'] = $document->thumbnail_path_stringS;
            }
            $products[] = $product;
        }
        return $products;
    }

    /**
     * @param ResultInterface $resultPages
     * @return array
     */
    private function _parsePages($resultPages)
    {
        $highlighting = $resultPages->getHighlighting();
        $pages = array();

        /** @var Document $resultPage */
        foreach ($resultPages->getDocuments() as $resultPage) {
            // Check if we got highlighting
            $highlights = array();
            if (isset($highlighting)) {
                foreach ($highlighting->getResult($resultPage->id) as $field => $highlight) {
                    $highlights[$field] = implode(' (&hellip;) ', $highlight);
                }
            }
            if (isset($highlights['description'])) {
                $description = $highlights['description'];
            } elseif (isset($highlights['content'])) {
                $description = $highlights['content'];
            } elseif ($resultPage->description) {
                $description = $resultPage->description;
            } else {
                $description = substr($resultPage->content, 0, Mage::getStoreConfig('schrack/solr/highlighting_length'));
            }
            $page = array(
                'id' => $resultPage->uid,
                'title' => isset($highlights['title']) ? $highlights['title'] : $resultPage->title,
                'thumbnail' => $resultPage->shop_image_stringS,
                'description' => $description,
                'url' => ((strpos($resultPage->url, '/') === 0 || strpos($resultPage->url, 'http') === 0) ? '' : '/') . $resultPage->url
            );
            $pages[] = $page;
        }
        return $pages;
    }

    /**
     * @param ResultInterface $resultSpellcheck
     * @return array
     */
    private function _parseSpellcheck($resultSpellcheck)
    {
        $spellchecked = array();
        $spellcheckResult = $resultSpellcheck->getSpellcheck();
        if (!$spellcheckResult) {
            return $spellchecked;
        }
        $collations = $spellcheckResult->getCollations();
        foreach ($collations as $collation) {
            if ($collation->getHits() > 0) {
                $parsedCollation = array(
                    'query' => $collation->getQuery(),
                    'hits' => $collation->getHits(),
                );
                $spellchecked[] = $parsedCollation;
            }
        }
        return $spellchecked;
    }

    /**
     * @param ResultInterface $solrResponse
     * @param int $productCount
     * @param bool $sortByPosition
     * @return array
     */
    private function _parseCategories($solrResponse, $productCount, $sortByPosition = false)
    {
        /** @var array $facets */
        $facets = $solrResponse->getFacetSet()->getFacet('category_breadcrumbs_stringS')->getValues();
        $facetRow = 0;
        $categories = array();
        $categoryFacets = array();
        foreach ($facets as $facet => $count) {
            $breadcrumbs = explode(self::STRING_GLUE_BREADCRUMBS, $facet);
            $previousId = 0;
            end($breadcrumbs);
            $lastIndex = key($breadcrumbs);
            reset($breadcrumbs);
            foreach ($breadcrumbs as $index => $breadcrumb) {
                $breadcrumb = explode(self::STRING_GLUE_CATEGORY, $breadcrumb);
                if ($index === $lastIndex && $facetRow < 10 && isset($breadcrumb[5]) && $breadcrumb[5]) {
                    $breadcrumbFacets = explode(',', $breadcrumb[5]);
                    foreach ($breadcrumbFacets as $position => $breadcrumbFacet) {
                        if (!isset($categoryFacets[$breadcrumbFacet])) {
                            $categoryFacets[$breadcrumbFacet] = $position;
                        }
                    }
                }
                $categoryCount = $count;
                if (isset($categories[(int)$breadcrumb[1]])) {
                    $categoryCount += $categories[(int)$breadcrumb[1]]['count'];
                }
                $categories[(int)$breadcrumb[1]] = array(
                    'id' => (int)$breadcrumb[1],
                    'name' => str_replace('-', '&#8209;', $breadcrumb[0]),
                    'position' => (int)$breadcrumb[4],
                    // Never show that a category has more hits than the total number of products found (grouping!)
                    'count' => ($categoryCount < $productCount) ? $categoryCount : $productCount,
                    'path' => $breadcrumb[2],
                    'image' => ($breadcrumb[3] ? $breadcrumb[3] : null),
                    'parent' => $previousId,
                    'children' => array()
                );
                $previousId = (int)$breadcrumb[1];
            }
            $facetRow++;
        }
        // Sort by position
        uasort($categoryFacets, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        $categoryFacets = array_keys($categoryFacets);
        /** @var Mage_Eav_Model_Config $eavConfig */
        $eavConfig = Mage::getSingleton("eav/config");
        $eavConfig->preloadAttributes('catalog_product', $categoryFacets);
        foreach ($categoryFacets as $row => $facet) {
            if ($facet) {
                unset($categoryFacets[$row]);
                $categoryFacets[$facet] = $eavConfig->getAttribute('catalog_product', $facet)->getFrontendLabel();
            }
        }
        $this->setCategoryFacets($categoryFacets);
        if ($sortByPosition) {
            // Sort by position
            uasort($categories, function ($a, $b) {
                if ($a['position'] == $b['position']) {
                    return 0;
                }
                return ($a['position'] < $b['position']) ? -1 : 1;
            });
        } else {
            // Sort by count
            uasort($categories, function ($a, $b) {
                if ($a['count'] == $b['count']) {
                    return 0;
                }
                return ($a['count'] > $b['count']) ? -1 : 1;
            });
        }

        // Build the tree
        $nested = array();
        foreach ($categories as $category) {
            if (!$category['parent']) {
                $nested[$category['id']] = $category;
                continue;
            }
            if (!isset($nested[$category['parent']])) {
                $nested[$category['parent']] = array();
            }
            if (!isset($nested[$category['id']])) {
                $nested[$category['id']] = $category;
            } elseif (!isset($nested[$category['id']]['id'])) {
                unset($category['children']);
                $nested[$category['id']] = array_merge($nested[$category['id']], $category);
            }
            $nested[$category['parent']]['children'][$category['id']] = &$nested[$category['id']];
        }
        return $nested[$this->getCategory()];
    }

    /**
     * @param ResultInterface $solrResponse
     * @return array
     */
    private function _parseBreadcrumbs($solrResponse)
    {
        $result = array();
        /** @var array $facets */
        $facets = $solrResponse->getFacetSet()->getFacet('category_breadcrumbs_stringS')->getValues();
        if (is_array($facets)) {
            reset($facets);
            $breadcrumbs = key($facets);
            $breadcrumbs = explode(self::STRING_GLUE_BREADCRUMBS, $breadcrumbs);
            foreach ($breadcrumbs as $breadcrumb) {
                $breadcrumb = explode(self::STRING_GLUE_CATEGORY, $breadcrumb);
                if (isset($breadcrumb[3]) && strpos($breadcrumb[2], '.html') !== false) {
                    $result[] = array(
                        'id' => $breadcrumb[1],
                        'name' => $breadcrumb[0],
                        'path' => $breadcrumb[2],
                        'image' => ($breadcrumb[3] ? $breadcrumb[3] : null)
                    );
                }
                if ($breadcrumb[1] == $this->getCategory()) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param ResultInterface $solrResponse
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _parseHighlightFacets($solrResponse)
    {
        $result = [];

        /** @var \Solarium\QueryType\Select\Result\Facet\Field[] $solrResponseFacets */
        $solrResponseFacets = $solrResponse->getFacetSet()->getFacets();

        foreach ($this->highlightFacets as $alias => $solrField) {
            $result[$alias] = false;
            if ($solrResponseFacets[$alias]) {
                $values = $solrResponseFacets[$alias]->getValues();
                if ($values['true']) {
                    $result[$alias] = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param ResultInterface $solrResponse
     * @return array
     * @throws Mage_Core_Exception
     */
    private function _parseFacets($solrResponse)
    {
        $result = array();
        $facetCounts = array();
        $categoryFacets = $this->getCategoryFacets();
        $activeFacets = $this->getFacets();
        /** @var \Solarium\QueryType\Select\Result\Facet\Field[] $solrResponseFacets */
        $solrResponseFacets = $solrResponse->getFacetSet()->getFacets();
        $translationHelper = Mage::helper('catalog');
        $outputLabels = array(
            'Yes' => $translationHelper->__('Yes'),
            'No' => $translationHelper->__('No')
        );
        foreach ($solrResponseFacets as $responseFacetKey => $responseFacetTerms) {
            $facetOptionValues = $responseFacetTerms->getValues();
            // Always add user selected option back to available values
            if (isset($activeFacets[$responseFacetKey])) {
                foreach ($activeFacets[$responseFacetKey] as $activeFacetOption) {
                    if (!isset($facetOptionValues[$activeFacetOption])) {
                        $facetOptionValues[$activeFacetOption] = 0;
                    }
                }
            }
            if (!$facetOptionValues) {
                continue;
            }
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product',
                substr($responseFacetKey, 0, strrpos($responseFacetKey, '_facet')));
            $attributeOptions = $attribute->getSource()->getAllOptions(true, true);
            if (count($attributeOptions) === 1 && $attributeOptions[0]['label'] === '') {
                $attributeOptions = array();
            }
            // Facet with options
            if ($attributeOptions) {
                $options = $attributeOptions;
            // Facet without options
            } else {
                $options = array_keys($facetOptionValues);
                // Sort them
                natcasesort($options);
                // Reset index keys
                $options = array_values($options);
            }
            $facetOptions = array();
            $facetCounts[$responseFacetKey] = 0;
            foreach ($options as $option) {
                // Facet with options
                if (isset($option['label'], $activeFacets[trim($option['label'])])) {
                    $option = trim($option['label']);
                } elseif ($attributeOptions) {
                    continue;
                }
                // Check if we have different output label
                $outputLabel = '';
                if (isset($outputLabels[$option])) {
                    $outputLabel = $outputLabels[$option];
                }
                $optionType = '';
                // Have to make sure to pass $option as string, otherwise in_array does stupid things (e.g. match 3 on "3+N")
                if (isset($activeFacets[$responseFacetKey]) && in_array((string)$option, $activeFacets[$responseFacetKey])) {
                    $facetCounts[$responseFacetKey] += 100000;
                    $optionType = 'active';
                } elseif ($facetOptionValues[$option] > 0 && isset($categoryFacets[$responseFacetKey])) {
                    $facetCounts[$responseFacetKey] += $facetOptionValues[$option];
                    $optionType = 'available';
                }
                if ($optionType) {
                    $facetOptions[$option] = array(
                        'label' => (!$outputLabel ? $option : $outputLabel),
                        'type' => $optionType,
                    );
                }
            }
            if ($facetOptions) {
                $result[$responseFacetKey] = array(
                    'label' => $categoryFacets[$responseFacetKey],
                    'options' => $facetOptions
                );
            }
            if ($activeFacets !== null && in_array($responseFacetKey, array_keys($activeFacets))) {
                $result[$responseFacetKey]['options']['all'] = array(
                    'label' => $translationHelper->__('All'),
                    'count' => '*',
                    'type' => 'clear',
                );
            }
        }

        // Sort by count, then position
        $facetOrder = array_flip(array_keys($categoryFacets));
        uksort($result, function ($a, $b) use ($facetCounts, $facetOrder) {
            if ($facetCounts[$a] == $facetCounts[$b]) {
                return ($facetOrder[$a] < $facetOrder[$b]) ? -1 : 1;
            }
            return ($facetCounts[$a] > $facetCounts[$b]) ? -1 : 1;
        });

        return array_slice($result, 0, 20);
    }

    /**
     * @param $query
     * @return string
     */
    protected function _formatQuery($query)
    {
        $query = str_replace([' or ', ' and ', ' not '], [' OR ', ' AND ', ' NOT '], strtolower(stripcslashes($query)));
        $prefixMap = [
            '|' => 'OR ',
            '+' => 'AND ',
            '-' => 'NOT '
        ];
        $queryTerms = [];
        if (!preg_match_all('/[+|-]?"(?:\\\\.|[^\\\\"])*|\S+/', $query, $queryTerms)) {
            return '';
        }
        $parsedTerms = [];
        $cntValidTerms = 0;
        foreach ($queryTerms[0] as $idx => $queryTerm) {
            $queryTerm = trim(stripslashes($queryTerm));
            $prefix = substr($queryTerm, 0, 1);
            if (in_array($prefix, ['|', '+', '-'])) {
                $queryTerm = substr($queryTerm, 1);
            } else {
                $prefix = '+';
            }
            $unqoted = trim($queryTerm, '"');
            if ($idx > 0) {
                switch ($queryTerms[0][$idx - 1]) {
                    case 'OR':
                        $prefix = '|';
                        break;
                    case 'AND':
                        $prefix = '+';
                        break;
                    case 'NOT':
                        $prefix = '-';
                        break;
                }
            }
            if (!$unqoted || in_array($unqoted, ['OR', 'AND', 'NOT'])) {
                continue;
            }
            $escaped = $this->_escapeSpecialCharacters($unqoted);
            // Only add wildcard chars or quotes if term contains no wildcard
            if (strpos($escaped, '*') === false) {
                if (!$this->isExperimentEnabled(self::EXPERIMENT_FUZZY_SEARCH)) {
                    $finalTerm = '("' . $escaped . '" OR ' . $escaped . '*)';
                } else {
                    $finalTerm = '("' . $escaped . '" OR ' . $escaped . '* OR ' . $escaped . '~)';
                }
            } else {
                $finalTerm = $escaped;
            }
            if ($cntValidTerms > 0) {
                $parsedTerms[] = $prefixMap[$prefix] . $finalTerm;
            } else {
                $parsedTerms[] = $finalTerm;
            }
            $cntValidTerms++;
        }

        return '(' . $this->_escapeSpecialCharacters($query) . ') OR (' . implode(' ', $parsedTerms) . ')';
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function _escapeSpecialCharacters($value)
    {
        // list taken from http://lucene.apache.org/java/3_3_0/queryparsersyntax.html#Escaping%20Special%20Characters
        // not escaping *, &&, ||, ?, -, !, + though
        $pattern = '/(\(|\)|\{|}|\[|]|\^|"|~|:|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * @param Query $query
     * @param string $configPostfix
     * @return mixed
     */
    protected function _addQueryHighlighting($query, $configPostfix = '')
    {
        if (Mage::getStoreConfig('schrack/solr/highlighting_enabled' . $configPostfix)) {
            /** @var \Solarium\QueryType\Select\Query\Component\Highlighting\Highlighting $hl */
            $hl = $query->getHighlighting();
            $hl->setFields(Mage::getStoreConfig('schrack/solr/highlighting_fields' . $configPostfix));
            $hl->setFragSize(Mage::getStoreConfig('schrack/solr/highlighting_length'));
            if (Mage::getStoreConfig('schrack/solr/highlighting_snippets')) {
                $hl->setSnippets(Mage::getStoreConfig('schrack/solr/highlighting_snippets'));
            }
            $hlWrap = explode('|', Mage::getStoreConfig('schrack/solr/highlighting_wrap'));
            $hl->setSimplePrefix($hlWrap[0]);
            $hl->setSimplePostfix($hlWrap[1]);
        }
        return $query;
    }

    private function getCustomSkuMapping ( array $terms ) {
        if ( ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
            return false;
        }
        $customerID = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
        if ( ! $customerID || $customerID <= ' ' ) {
            return false;
        }
        $customSkuMap = $this->getCustomSkuMap($customerID);
        // for now, we check only the first term:
        $term0uc = strtoupper($terms[0]);
        if ( isset($customSkuMap[$term0uc]) ) {
            if ( count($customSkuMap[$term0uc]) == 1 ) {
                return 'sku:' . str_pad($customSkuMap[$term0uc][0], 10, '?');
            } else {
                $res = false;
                foreach ( $customSkuMap[$term0uc] as $sku ) {
                    if ( $res ) {
                        $res .= ' OR ';
                    } else {
                        $res = "sku:(";
                    }
                    $res .= $sku;
                }
                return $res . ")";
            }
        }
        return false;
    }

    private function getCustomSkuMap ( $customerID ) {
        $cacheKey = "custom_skus_$customerID";
        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        if ( $cacheRes = $cache->load($cacheKey) ) {
            return unserialize($cacheRes);
        } else {
            $sql = "SELECT UPPER(custom_sku) AS custom_sku, UPPER(sku) AS sku FROM schrack_custom_sku WHERE wws_customer_id = ?";
            $dbRes = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql,$customerID);
            $res = array();
            foreach ( $dbRes as $row ) {
                if ( isset($res[$row['custom_sku']]) ) {
                    $res[$row['custom_sku']][] = $row['sku'];
                } else {
                    $res[$row['custom_sku']] = array($row['sku']);
                }
            }
            $cache->save(serialize($res),$cacheKey,array(), 6 * 60 * 60); // lifetime 6 hours
            return $res;
        }
    }
}

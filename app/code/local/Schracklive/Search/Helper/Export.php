<?php

use Solarium\Client;

class Schracklive_Search_Helper_Export extends Mage_Core_Helper_Abstract
{

    const COLLECTION_PAGE_SIZE = 1000;
    /** @var string */
    const STRING_GLUE_CATEGORY = "\u{2063}|\u{2063}";
    /** @var string */
    const STRING_GLUE_BREADCRUMBS = "\u{2063}#\u{2063}";
    /** @var Mage_Core_Model_Resource */
    var $dbResource;
    /** @var Varien_Db_Adapter_Pdo_Mysql */
    var $dbReadAdapter;
    /** @var int Starting timestamp */
    var $timeStart;
    /** @var Schracklive_SchrackCore_Model_Translate */
    var $translate;
    /** @var int Store ID */
    var $store = 1;
    /** @var string */
    var $query = '';
    /** @var string */
    var $solrUrl = '';
    /** @var bool|Client Shop-specific solr connector */
    var $solr;
    /** @var bool|Client Inter-shop solr connector */
    var $solrCommon;
    /** @var string Shop TLD */
    var $country;
    /** @var string Shop locale */
    var $locale;
    /** @var string Shop URL */
    var $baseUrl;
    /** @var string exportProducts logfile */
    var $logFile = "solr.log";
    /** @var string Filename of serialized full product info solr documents */
    var $solrDocsFile = "solrDocs_%u.gz";
    /** @var string Filename of serialized base product info solr documents for SEO */
    var $solrDocsCommonFile = "solrDocsCommon_%u.gz";
    var $magentoOptions;
    // Filled in and used by run for field types
    var $fixedTypeMap = array(
        'schrack_ean' => array('type' => 'textT', 'count' => 'M'),
        'sku' => array('type' => 'textT', 'count' => 'S'),
        'name' => array('type' => 'text', 'count' => 'S'),
        'description' => array('type' => 'text', 'count' => 'S'),
        'category_name' => array('type' => 'text', 'count' => 'S'),
        'category_id' => array('type' => 'int', 'count' => 'S'),
        'category' => array('type' => 'string', 'count' => 'S'),
        'keyword' => array('type' => 'text', 'count' => 'M'),
        'schrack_wws_ranking' => array('type' => 'int', 'count' => 'S'),
        'schrack_detail_description' => array('type' => 'text', 'count' => 'S'),
        'schrack_long_text_addition' => array('type' => 'text', 'count' => 'S'),
        'short_description' => array('type' => 'text', 'count' => 'S'),
        'url_path' => array('type' => 'string', 'count' => 'S'),
        'main_packing_unit_name' => array('type' => 'string', 'count' => 'S'),
        'schrack_newenergieeffizienzkl' => array('type' => 'string', 'count' => 'S'),
    );
    var $attributes = array();
    // Fields not sent to solr
    var $skipFields = array(
        'meta_keyword',
        'entity_type_id',
        'attribute_set_id',
        'type_id',
        'has_options',
        'created_at',
        'updated_at',
        'tax_class_id',
        'visibility',
        'enable_googlecheckout',
        'options_container',
        'special_from_date',
        'weight',
        'price',
        'schrack_url_key_without_sku',
        'url_key',
        'schrack_printkattext',
        'categories',
        'schrack_optionales_zubehoer',
        'schrack_accessories_optional',
        'schrack_accessories_necessary',
        'schrack_keyword_foreign',
        'schrack_keyword_foreign_hidden',
        'schrack_main_producer',
        'schrack_optional_accessories',
        'schrack_necessary_accessories',
        'schrack_substitute',
        'schrack_catalognr',
        'schrack_preis',
        'schrack_sts_isaccessory',
        'schrack_sts_main_article_sku',
        'schrack_sts_sub_article_skus',
        'schrack_qtyunit_id',
        'schrack_sts_webshop_saleable'
    );

    /** @var array Field names that should not be copied into the common content field */
    var $ignoredContentFields = array(
        'schrack_category_names',
        'url_key',
        'url_path',
        'schrack_wws_ranking',
        'schrack_productgroup',
    );
    /** @var Schracklive_SchrackCatalog_Model_Category[] */
    var $categories = array();
    var $categoryNames = array();
    var $categoryUrls = array();
    var $categoryKeywords = array();
    var $categoryDescriptions = array();
    var $categoryFacets = array();
    var $eavOptionValues = array();
    var $relatedSkus = [];
    var $searchableAttributes = [];

    public function __construct()
    {
        echo getcwd();
        $this->magentoOptions = Mage::getConfig()->getOptions();
        // Check magento tmp dir
        if (!file_exists($this->magentoOptions['tmp_dir'])) {
            $this->_log('magento tmp dir does not exist, trying to create it', true);
            if (!mkdir($this->magentoOptions['tmp_dir'], 0777)) {
                $this->_fail('could not create magento tmp dir, cannot continue', true);
            }
        }
        if (!is_writable($this->magentoOptions['tmp_dir'])) {
            $this->_fail('magento tmp dir ist not writeable, cannot continue (are you running the script as root?)',
                true);
        }
    }

    private function _init($skipAvailabilityCheck = false)
    {
        $this->_log('[_init] Starting up & checking connectivity', true);
        $this->timeStart = time();
        if (!$skipAvailabilityCheck) {
            $this->solr = $this->_getSolrClient();
            if (!$this->solr) {
                $this->_fail('[_init] Country specific solr URL not found or invalid in Magento Backend', true);
            }
            $this->solrCommon = $this->_getSolrClient('solrserver_common');
            if (!$this->solrCommon) {
                $this->_fail('[_init] Common solr URL not found or invalid in Magento Backend', true);
            }
        }
        $this->setStore($this->store);
        if (!$skipAvailabilityCheck) {
            if (!$this->solr) {
                $this->_fail('[_init] solr config incorrect, cannot continue, please check backend settings', true);
            }
            if (!$this->solr->ping($this->solr->createPing())) {
                $this->_fail('[_init] ping to solr path ' . $this->solr->getEndpoint()->getPath() . ' failed, please check solr server availability',
                    true);
            }
        }
        $this->dbResource = Mage::getSingleton('core/resource');
        $this->dbReadAdapter = $this->dbResource->getConnection('core_read');
        $this->translate = Mage::getSingleton('core/translate')->setLocale(Mage::app()->getLocale()->getLocaleCode())->init('frontend',
            true);
        $this->_log('[_init] Init done', true);
        $this->_log('======================================', true);
    }

    public function setStore($store)
    {
        $this->store = $store;
        $this->country = Mage::getStoreConfig('schrack/general/country', $this->store);
        $this->locale = str_replace('_', '-', Mage::getStoreConfig('general/locale/code', $this->store));
        $this->baseUrl = Mage::getStoreConfig('web/unsecure/base_url', $this->store);
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    private function _buildSolrData($query = '', $toStdOut = false)
    {
        $this->_log('[_buildSolrData] Building solr request', true);
        /** @var $urlRewriteResource Mage_Core_Model_Mysql4_Url_Rewrite */
        $urlRewriteResource = Mage::getResourceSingleton('core/url_rewrite');

        $allProductsAreSaleable = !Mage::getStoreConfig('schrack/general/use_webshop_saleable');

        $page = 1;
        /** @var Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToFilter('visibility', array('neq' => 1));
        if ($query) {
            $collection->getSelect()->where($query);
        }
        // Get all EAV attributes, used for processing later on
        $this->attributes = $collection->getConnection()->fetchAssoc(
            'SELECT ea.attribute_id, ea.attribute_code, ea.frontend_input, cea.is_searchable 
            FROM eav_attribute AS ea
            LEFT JOIN catalog_eav_attribute AS cea ON (cea.attribute_id = ea.attribute_id)'
        );
        foreach ($this->attributes as $attribute) {
            if ((int)$attribute['is_searchable'] === 1) {
                $this->searchableAttributes[] = $attribute['attribute_code'];
            }
        }
        // Fetch info for SKU relations, need a complete set no matter how we process products
        $subArticleSkus = $collection->getConnection()->fetchAssoc(
            'SELECT entity_id, sku, schrack_sts_sub_article_skus 
            FROM catalog_product_entity WHERE schrack_sts_sub_article_skus != \'\'');
        foreach ($subArticleSkus as $subArticleSku) {
            $subSkus = explode(';', $subArticleSku['schrack_sts_sub_article_skus']);
            $this->relatedSkus[$subArticleSku['sku']] = $subSkus;
            foreach ($subSkus as $subSku) {
                $this->relatedSkus[$subSku] = $subSkus;
                unset($this->relatedSkus[$subSku][array_search($subSku, $this->relatedSkus[$subSku], true)]);
                $this->relatedSkus[$subSku][] = $subArticleSku['sku'];
            }
        }
        unset($subArticleSkus);
        $productCount = 0;
        while (true) {
            $productsQuery = $collection->getSelect()->limitPage($page++, self::COLLECTION_PAGE_SIZE);
            $productsData = $collection->getConnection()->fetchAssoc($productsQuery);
            // No more products
            if (!$productsData) {
                unset($productsData);
                break;
            }
            unset($productsQuery);
            // Fetch product category IDs
            $productsCategoriesQuery = $collection->getConnection()->select()
                ->from($collection->getResource()->getTable('catalog/category_product'), array('product_id'))
                ->columns(array('category_id', 'position', 'schrack_sts_is_accessory'))
                ->where('product_id IN (?)', array_keys($productsData));
            $productsCategoriesResource = $collection->getConnection()->query($productsCategoriesQuery);
            while ($productCategoryData = $productsCategoriesResource->fetch()) {
                if (!is_array($productsData[$productCategoryData['product_id']]['categories'])) {
                    $productsData[$productCategoryData['product_id']]['categories'] = array();
                }
                $productsData[$productCategoryData['product_id']]['categories'][$productCategoryData['category_id']] = array(
                    'category_id' => $productCategoryData['category_id'],
                    'position' => $productCategoryData['position'],
                    'schrack_sts_is_accessory' => $productCategoryData['schrack_sts_is_accessory']
                );
            }
            unset($productId, $productCategoriesData, $productsCategoriesData);
            // Fetch product attributes
            $productEntityTypeId = $collection->getEntity()->getEntityType()->getEntityTypeId();
            $productIds = implode(',', array_keys($productsData));
            $productsAttributesQuery = "SELECT `entity_id`, `attribute_id`, `value`, `store_id`
                FROM `catalog_product_entity_int`
                WHERE (entity_type_id =$productEntityTypeId) AND (entity_id IN ($productIds)) AND (store_id IN(0,{$this->store})) UNION ALL
                SELECT `entity_id`, `attribute_id`, `value`, `store_id`
                FROM `catalog_product_entity_decimal`
                WHERE (entity_type_id =$productEntityTypeId) AND (entity_id IN ($productIds)) AND (store_id IN(0,{$this->store})) UNION ALL
                SELECT `entity_id`, `attribute_id`, `value`, `store_id`
                FROM `catalog_product_entity_varchar`
                WHERE (entity_type_id =$productEntityTypeId) AND (entity_id IN ($productIds)) AND (store_id IN(0,{$this->store})) UNION ALL
                SELECT `entity_id`, `attribute_id`, `value`, `store_id`
                FROM `catalog_product_entity_datetime`
                WHERE (entity_type_id =$productEntityTypeId) AND (entity_id IN ($productIds)) AND (store_id IN(0,{$this->store})) UNION ALL
                SELECT `entity_id`, `attribute_id`, `value`, `store_id`
                FROM `catalog_product_entity_text`
                WHERE (entity_type_id =$productEntityTypeId) AND (entity_id IN ($productIds)) AND (store_id IN(0,{$this->store}))
                ORDER BY entity_id, attribute_id, store_id";
            $productsAttributesResource = $collection->getConnection()->query($productsAttributesQuery);
            while ($productAttributeData = $productsAttributesResource->fetch()) {
                if (isset($this->attributes[$productAttributeData['attribute_id']])) {
                    $attributeValue = $this->resolveAttributeValue($productAttributeData['attribute_id'], $productAttributeData['value']);
                    $productsData[$productAttributeData['entity_id']][$this->attributes[$productAttributeData['attribute_id']]['attribute_code']] = $attributeValue;
                }
            }
            unset($productsAttributesResource, $productAttributeData, $productsAttributesData);

            foreach ($productsData as $productId => &$productData) {
                // Enrich product data
                if ($productData['schrack_sts_main_article_sku']) {
                    $productsData[$productId]['main_packing_unit_name'] = $this->translate->translate(array($productData['schrack_sts_main_vpe_type']));
                } else if ( $productData['schrack_sts_sub_article_skus'] ) {
                    $productsData[$productId]['main_packing_unit_name'] = $this->translate->translate(array('Yard ware'));
                }

                // Create solr required fields
                $solrDocBase = array(
                    'appKey' => 'mage',
                    'type' => 'products',
                    'entity_id' => $productData['entity_id']
                );

                // Create and add doc for common inter-shop solr core
                $solrDocCommon = array(
                    'id' => 'mage_product_' . $this->country . '_' . $this->store . '_0_' . $productData['entity_id'],
                    'key_stringS' => $productData['sku'],
                    'country_stringS' => $this->country,
                    'locale_stringS' => $this->locale,
                    'url_stringS' => $this->baseUrl . $productData['url_path'],
                );
                if (!$this->_writeSolrDoc($this->solrDocsCommonFile, $this->_arrayToXmlFragment(array_merge($solrDocBase, $solrDocCommon)), $toStdOut)) {
                    $this->_log('[_buildSolrData] Couldn\'t create solr common data, aborting!', true);
                    exit;
                }
                unset($solrDocCommon);

                // thumbnails, fotos, attachments
                $attachments = $this->dbReadAdapter->fetchAll('SELECT filetype, url, attachment_id 
                    FROM catalog_attachment 
                    WHERE entity_type_id = 4 AND entity_id =' . $productData['entity_id'] . ' 
                    ORDER BY attachment_id DESC');
                $attachmentSizes = $this->dbReadAdapter->fetchAll('SELECT * FROM schrack_file_size');
                $attachmentSizes = array_combine(array_column($attachmentSizes, 'path'), $attachmentSizes);
                $downloadFileAttachmentUrl = false;
                foreach ($attachments as $attachment) {
                    $attachmentSolrFieldName = '';
                    $attachmentSizeSolrFieldName = '';
                    $fetchFileSize = true;
                    switch ($attachment['filetype']) {
                        case 'onlinekatalog':
                            $attachmentSolrFieldName = 'catalog_path_stringS';
                            $attachmentSizeSolrFieldName = 'catalog_size_stringS';
                            break;
                        case 'thumbnails':
                            $fetchFileSize = false;
                            $attachmentSolrFieldName = 'thumbnail_path_stringS';
                            break;
                        case 'foto':
                            $fetchFileSize = false;
                            $attachmentSolrFieldName = 'image_path_stringS';
                            break;
                        case 'katalogseiten':
                        case 'produktkataloge':
                            $attachmentSolrFieldName = 'catalog_download_path_stringS';
                            $attachmentSizeSolrFieldName = 'catalog_download_size_stringS';
                            break;
                        case 'bedienungsanleitungen':
                            $attachmentSolrFieldName = 'manual_download_path_stringS';
                            $attachmentSizeSolrFieldName = 'manual_download_size_stringS';
                            break;
                        case 'energieeffizienzklasse' :
                            $attachmentSolrFieldName = 'download_path_energy_label_stringS';
                            break;
                        case 'onlinedatasheet' :
                            $attachmentSolrFieldName = 'download_path_datasheet_stringS';
                            break;
                    }
                    // Only fetch file size if not already in db, and contains a file extension
                    if ($fetchFileSize
                        && $attachment['url']
                        && strstr($attachment['url'], '.') !== false
                        && !isset($attachmentSizes[$attachment['url']])
                        && $attachmentSizeSolrFieldName
                    ) {
                        // Use fixed domain instead of schrackcdn to get the correct file size (no cloudflare)
                        $url = 'https://image.schrack.com/' . $attachment['url'];
                        $curl = curl_init($url);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_HEADER, true);
                        curl_setopt($curl, CURLOPT_NOBODY, true);
                        curl_exec($curl);
                        $fileSize = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                        if ($fileSize) {
                            $attachmentSizes[$attachment['url']] = [
                                'path' => $attachment['url'],
                                'updated_at' => date('Y-m-d H:i:s'),
                                'size' => $fileSize
                            ];
                            $this->dbReadAdapter->insert(
                                'schrack_file_size',
                                $attachmentSizes[$attachment['url']]
                            );
                        }
                    }
                    if (!$downloadFileAttachmentUrl) {
                        if ($attachment['url'] && strlen($attachment['url']) > 4 && strtolower(substr($attachment['url'], -4)) === '.pdf' ) {
                            $downloadFileAttachmentUrl = $attachment['url'];
                        } elseif (!in_array($attachment['filetype'], array('onlinekatalog', 'thumbnails', 'foto'))) {
                            $downloadFileAttachmentUrl = $attachment['url'];
                        }
                    }
                    if ($attachmentSolrFieldName && $attachment['url']) {
                        $solrDocBase[$attachmentSolrFieldName] = $attachment['url'];
                        unset($attachmentSolrFieldName);

                        if($attachmentSizeSolrFieldName && isset($attachmentSizes[$attachment['url']])) {
                            $solrDocBase[$attachmentSizeSolrFieldName] = $attachmentSizes[$attachment['url']]['size'];
                            unset($attachmentSizeSolrFieldName);
                        }
                    }
                    unset($attachment);
                }
                if ($downloadFileAttachmentUrl) {
                    $solrDocBase['download_path_stringS'] = $downloadFileAttachmentUrl;
                }
                unset($downloadFileAttachmentUrl);
                unset($attachments);

                // Restricted check
                $solrDocBase['is_restricted_boolS'] = false;
                if ($productData['schrack_sts_statuslocal'] === 'gesperrt' || $productData['schrack_sts_statusglobal'] === 'gesperrt'
                    || $productData['schrack_sts_managed_inventory'] !== 'bestand'
                    || ($productData['schrack_sts_is_download'] == 1 && !$solrDocBase['download_path_stringS'])) {
                    $solrDocBase['is_restricted_boolS'] = true;
                }

                // Saleable (if not, show product without prices and buy option)
                $solrDocBase['schrack_sts_webshop_saleable_boolS'] = $allProductsAreSaleable || (bool)$productData['schrack_sts_webshop_saleable'];

                // Product keywords (; separated)
                foreach (
                    [
                        'meta_keyword' => 'keyword_textM',
                        'schrack_keyword_foreign' => 'schrack_keyword_foreign_textM',
                        'schrack_keyword_foreign_hidden' => 'schrack_keyword_foreign_hidden_textM'
                    ] as $productField => $solrField
                ) {
                    $prodKeywords = explode(';', $productData[$productField]);
                    $solrDocBase[$solrField] = array();
                    foreach ($prodKeywords as $prodKeyword) {
                        $prodKeyword = trim($prodKeyword);
                        if (!empty($prodKeyword)) {
                            $solrDocBase[$solrField][] = $prodKeyword;
                        }
                    }
                }
                $content = "";
                foreach ($productData as $key => $value) {
                    if (!is_object($value)
                        && is_string($value)
                        && $value !== ''
                        && !in_array($key, $this->skipFields, true)
                    ) {
                        $vals = explode(chr(31), $value);
                        foreach ($vals as $val) {
                            $val = trim(html_entity_decode($val));
                            if ($val === '') {
                                continue;
                            }

                            // Add to output
                            if (strpos($key, 'schrack_') === 0) {
                                $attribute = $key . '_facet';
                                if (isset($solrDocBase[$attribute])) {
                                    if (!is_array($solrDocBase[$attribute])) {
                                        $solrDocBase[$attribute] = array($solrDocBase[$attribute]);
                                    }
                                    $solrDocBase[$attribute][] = $val;
                                } else {
                                    $solrDocBase[$attribute] = $val;
                                }
                                if (in_array($key, $this->searchableAttributes, true)) {
                                    $solrDocBase['articleattributes_textM'][] = $val;
                                }
                            }
                            if (isset($this->fixedTypeMap[$key])) {
                                $attribute = $key . '_' . $this->fixedTypeMap[$key]['type'] . $this->fixedTypeMap[$key]['count'];
                                if (!in_array($key, $this->ignoredContentFields, true)) {
                                    $content .= $val . ' ';
                                }
                                if ($this->fixedTypeMap[$key]['count'] === 'S') {
                                    $solrDocBase[$attribute] = $val;
                                } else {
                                    if (!isset($solrDocBase[$attribute])) {
                                        $solrDocBase[$attribute] = array();
                                    }
                                    $solrDocBase[$attribute][] = $val;
                                }
                            }
                            unset($attribute);
                        }
                        unset($vals);
                    }
                    unset($key, $val, $value);
                    // Set sale facet
                    if (isset($productData['schrack_sts_forsale']) && $productData['schrack_sts_forsale']) {
                        $solrDocBase['sts_forsale'] = true;
                    } else {
                        $solrDocBase['sts_forsale'] = false;
                    }
                    $solrDocBase['schrack_sts_is_high_available_boolS'] = (bool)$productData['schrack_sts_is_high_available'];
                }
                if ($productData['schrack_qtyunit_id']) {
                    $solrDocBase['schrack_qtyunit_stringS'] = Schracklive_SchrackCatalog_Model_Product::getQtyLabelFromQtyUnitId($productData['schrack_qtyunit_id']);
                }
                $solrDocBase['schrack_main_producer'] = $productData['schrack_main_producer'];
                $solrDocBase['schrack_main_category_id_for_tagmanager'] = Schracklive_SchrackCatalog_Model_Category::prepareId4googleTagManager(
                    Schracklive_SchrackCatalog_Model_Product::getSchrackMainCategoryStsIdFromValue($productData['schrack_main_category_id'])
                );
                $solrDocBase['content'] = $content;
                unset($content);
                if ($productData['categories']) {
                    $processedCategories = 0;
                    foreach ($productData['categories'] as $categoryId => $categoryInfo) {
                        $processedCategories++;
                        if (isset($this->categories[$categoryId])) {
                            /** @var $category Schracklive_SchrackCatalog_Model_Category */
                            $category = $this->categories[$categoryId];
                        } else {
                            /** @var $category Schracklive_SchrackCatalog_Model_Category */
                            $category = Mage::getModel('catalog/category')->load($categoryId);
                            foreach ($category->getPathIds() as $pathId) {
                                if (!isset($this->categories[$pathId])) {
                                    $this->categories[$pathId] = Mage::getModel('catalog/category')->load($pathId);
                                    $this->categoryNames[$pathId] = $this->categories[$pathId]->getName();
                                    $this->categoryUrls[$pathId] = $this->categories[$pathId]->getUrlPath();
                                    $this->categoryKeywords[$pathId] = explode(',', $this->categories[$pathId]->getMetaKeywords());
                                    $this->categoryDescriptions[$pathId] = $this->categories[$pathId]->getDescription();
                                    $schrackFacetList = $this->categories[$pathId]->getSchrackFacetList();
                                    if ($schrackFacetList) {
                                        $this->categoryFacets[$pathId] = explode(',', $schrackFacetList);
                                    } else {
                                        $this->categoryFacets[$pathId] = array();
                                    }
                                }
                            }
                        }
                        foreach ($category->getPathIds() as $pathId) {
                            if ($this->categories[$pathId]->getSchrackStrategicPillar() == '88') {
                                continue 2;
                            }
                        }
                        $solrDoc = $solrDocBase;
                        $solrDoc['id'] = 'mage_product_' . $this->store . '_' . $categoryId . '_' . $productData['entity_id'];
                        $urlFull = $urlRewriteResource->getRequestPathByIdPath('product/' . $productData['entity_id'] . '/' . $category->getId(),
                            $this->store);
                        if (!$urlFull) {
                            $urlFull = 'catalog/product/view/s/' . $productData['url_key'] . '/id/' . $productData['entity_id'] . '/category/' . $category->getId() . '/';
                        }
                        $solrDoc['url_path_full_stringS'] = $urlFull;
                        unset($urlFull);
                        $solrDoc['category_id_intS'] = $categoryId;
                        $solrDoc['category_ids_intM'] = $category->getPathIds();
                        $solrDoc['category_name_textS'] = $this->categoryNames[$categoryId];
                        $solrDoc['category_image_stringS'] = $category->getSchrackImageUrl();
                        $categoryBreadcrumbs = array();
                        $categoryNames = array();
                        $categoryStsIds = [];
                        $categoryShortStsIds = [];
                        foreach ($category->getPathIds() as $categoryPathId) {
                            $categoryNames[] = $this->categoryNames[$categoryPathId];
                            $categoryStsIds[] = $this->categories[$categoryPathId]->getSchrackGroupId();
                            $categoryShortStsIds[] = $this->categories[$categoryPathId]->getSchrackShortGroupId();
                            $categoryBreadcrumbs[] = $this->categoryNames[$categoryPathId] .
                                self::STRING_GLUE_CATEGORY . $categoryPathId .
                                self::STRING_GLUE_CATEGORY . $this->categoryUrls[$categoryPathId] .
                                self::STRING_GLUE_CATEGORY . $this->categories[$categoryPathId]->getSchrackImageUrl() .
                                self::STRING_GLUE_CATEGORY . $this->categories[$categoryPathId]->getPosition() .
                                self::STRING_GLUE_CATEGORY . $this->categories[$categoryPathId]->getSchrackFacetList() .
                                self::STRING_GLUE_CATEGORY . $this->categories[$categoryPathId]->getSchrackShortGroupId();
                        }
                        $solrDoc['category_sts_ids_stringM'] = array_slice($categoryStsIds, 2);
                        $solrDoc['category_sts_short_ids_stringM'] = array_slice($categoryShortStsIds, 2);
                        $solrDoc['category_stringS'] = end($categoryBreadcrumbs);
                        $solrDoc['category_names_search_textM'] = array_slice($categoryNames, 4);
                        $solrDoc['category_breadcrumbs_stringS'] = implode(self::STRING_GLUE_BREADCRUMBS, $categoryBreadcrumbs);
                        $solrDoc['category_stringM'] = $category->getPathIds();
                        foreach ($this->categoryKeywords[$categoryId] as $keyword) {
                            $keyword = trim($keyword);
                            if (!empty($keyword)) {
                                $solrDoc['keyword_textM'][] = $keyword;
                            }
                            unset($keyword);
                        }
                        $solrDoc['category_description_textS'] = $this->categoryDescriptions[$categoryId];
                        if ($category->isDiscontinuedProductsCategory()) {
                            $solrDoc['discontinued_category'] = true;
                        } else {
                            $solrDoc['discontinued_category'] = false;
                        }
                        $solrDoc['schrack_sts_is_accessory_boolS'] = (bool)$categoryInfo['schrack_sts_is_accessory'];
                        $solrDoc['position_intS'] = (int)$categoryInfo['position'];
                        if (isset($this->relatedSkus[$productData['sku']])) {
                            $solrDoc['related_skus_textTM'] = $this->relatedSkus[$productData['sku']];
                        }
                        if (!$this->_writeSolrDoc($this->solrDocsFile, $this->_arrayToXmlFragment($solrDoc), $toStdOut)) {
                            $this->_log('[_buildSolrData] Couldn\'t create solr data, not submitting new data to solr', true);
                            exit;
                        }
                        $category->clearInstance();
                        unset($solrDoc);
                        unset($category);
                    }
                    unset($categoryId);
                    unset($categoryInfo);
                    unset($processedCategories);
                    // Allow only dead products to get pushed to solr without category infos
                } elseif (isset($productData['schrack_sts_statuslocal']) && in_array($productData['schrack_sts_statuslocal'],
                        array('tot', 'strategic_no', 'unsaleable'))
                ) {
                    $solrDoc = $solrDocBase;
                    $solrDoc['id'] = 'mage_product_' . $this->store . '_0_' . $productData['entity_id'];
                    $urlFull = $urlRewriteResource->getRequestPathByIdPath('product/' . $productData['entity_id'],
                        $this->store);
                    if (!$urlFull) {
                        $urlFull = 'catalog/product/view/s/' . $productData['url_key'] . '/id/' . $productData['entity_id'] . '/';
                    }
                    $solrDoc['url_path_full_stringS'] = $urlFull;
                    unset($urlFull);
                    if (isset($this->relatedSkus[$productData['sku']])) {
                        $solrDoc['related_skus_textTM'] = $this->relatedSkus[$productData['sku']];
                    }
                    if (!$this->_writeSolrDoc($this->solrDocsFile, $this->_arrayToXmlFragment($solrDoc), $toStdOut)) {
                        $this->_log('[_buildSolrData] Couldn\'t write solr data to file, aborting!', true);
                        exit;
                    }
                    unset($solrDoc);
                }
                unset($solrDocBase, $productData, $productsData[$productId], $productId);
                $productCount++;
            }
            unset($key, $options, $type, $val);
        }
        $collection->clear();
        unset($collection, $productsCategoriesQuery, $page, $productId, $productData);

        foreach ($this->categories as $category) {
            if (!$category->getSchrackGroupId()) {
                continue;
            }
            $solrDocCommon = array(
                // Create solr required fields
                'appKey' => 'mage',
                'type' => 'categories',
                // Create and add doc for common inter-shop solr core
                'id' => 'mage_category_' . $this->country . '_' . $this->store . '_0_' . $category->getSchrackGroupId(),
                'key_stringS' => $category->getSchrackGroupId(),
                'country_stringS' => $this->country,
                'locale_stringS' => $this->locale,
                'url_stringS' => $this->baseUrl . $category->getUrlPath()
            );

            if (!$this->_writeSolrDoc($this->solrDocsCommonFile, $this->_arrayToXmlFragment($solrDocCommon), $toStdOut)) {
                $this->_log('[_buildSolrData] Couldn\'t create solr common data, aborting!', true);
                exit;
            }
            $category->clearInstance();
            unset($category, $solrDocCommon);
        }

        $this->_log('[_buildSolrData] Processed ' . $productCount . ' products.', true);

        if (!$this->_writeSolrDoc($this->solrDocsFile, '', $toStdOut, true)) {
            $this->_log('[_buildSolrData] Couldn\'t write solr data to file, aborting!', true);
            exit;
        }
        if (!$this->_writeSolrDoc($this->solrDocsCommonFile, '', $toStdOut, true)) {
            $this->_log('[_buildSolrData] Couldn\'t write common solr data to file, aborting!', true);
            exit;
        }
        // Log the last build time
        $fh = fopen($this->magentoOptions['tmp_dir'] . DS . 'lastbuild', 'w');
        if ($fh) {
            fwrite($fh, $this->timeStart);
            fclose($fh);
        }
        $this->_log('[_buildSolrData] Build solr data done', true);
        $this->_log('======================================', true);
    }

    public function runAll()
    {
        $this->_init();
        $this->_buildSolrData();
        if (intval(Mage::getStoreConfig('schrack/solr/solrexport_synonyms_active',$this->store)) == 1 ){
            $this->_updateSynonyms();
        } else {
            $this->_log('ATTENTION : Synonyms deactivated', true);
        }
        $this->_updateDictionary();
        $this->_postToSolr();
        $this->_postToSolrCommon();
        $this->_updateFacets();
        $this->_logRuntime();
    }

    public function runPost()
    {
        $this->_init();
        $this->_updateSynonyms();
        $this->_updateDictionary();
        $this->_postToSolr();
        $this->_postToSolrCommon();
        $this->_updateFacets();
        $this->_logRuntime();
    }

    public function runBuild()
    {
        $this->_init(true);
        $this->_buildSolrData();
        $this->_logRuntime();
    }

    public function runDebug($query = '')
    {
        $this->_init(true);
        $this->_buildSolrData($query, true);
    }

    public function runSynonyms()
    {
        $this->_init();
        $this->_updateSynonyms();
    }

    public function runDictionary()
    {
        $this->_init();
        $this->_updateDictionary();
    }

    public function runFacets()
    {
        $this->_init();
        $this->_updateFacets();
        $this->_logRuntime();
    }

    protected function _logRuntime()
    {
        $timeEnd = time();
        $passedMinutes = ($timeEnd - $this->timeStart) / 60;
        $this->_log('Runtime: ' . $passedMinutes . ' minutes', true);
        $this->_log('Max RAM used: ' . memory_get_peak_usage(), true);
        $this->_log('Export finished', true);
    }

    public function _log($data, $echo = false)
    {
        $data = "[" . date("y.m.d H:i:s") . "]" . $data . "\n";
        if ($echo) {
            echo $data;
        }
        $log = fopen($this->magentoOptions['log_dir'] . DS . $this->logFile, 'a');
        fwrite($log, $data);
        fclose($log);
    }

    protected function _fail($data, $echo = false)
    {
        $this->_log($data, $echo);
        $this->_log('Export aborted due to exception', true);
        exit;
    }

    protected function _writeSolrDoc($docFile, $doc, $toStdOut = false, $closeFile = false)
    {
        if (!$toStdOut) {
            static $file = array();
            if (!isset($file[$docFile])) {
                $file[$docFile] = gzopen($this->magentoOptions['tmp_dir'] . DS . sprintf($docFile, $this->store), 'w5');
            }
            if (!$file[$docFile]) {
                $this->_log('[_writeSolrDoc] Can\'t open ' . sprintf($docFile, $this->store), true);
                return false;
            }
            if (!gzwrite($file[$docFile], str_replace("\n", "###newline###", $doc) . "\n")) {
                $this->_log('[_writeSolrDoc] Can\'t write ' . sprintf($docFile, $this->store), true);
                gzclose($file[$docFile]);
                unset($file[$docFile]);
                return false;
            }
            if ($closeFile) {
                $this->_log('[_writeSolrDoc] ' . sprintf($docFile, $this->store) . ' write successful', true);
                gzclose($file[$docFile]);
                unset($file[$docFile]);
            }
        } else {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($doc);
            echo $dom->saveXML() . "\n";
        }

        return true;
    }

    protected function getSolrDocumentFromGzFile($fileBase)
    {
        static $file = array();
        $solrDocument = '';
        if (!isset($file[$fileBase])) {
            $file[$fileBase] = gzopen($this->magentoOptions['tmp_dir'] . DS . sprintf($fileBase, $this->store), 'r');
        }
        if ($file[$fileBase]) {
            if (!gzeof($file[$fileBase])) {
                $solrDocument = trim(str_replace('###newline###', "\n", gzgets($file[$fileBase])));
            } else {
                gzclose($file[$fileBase]);
            }
        }
        return $solrDocument;
    }

    protected function _updateSynonyms()
    {
        $this->_log('[_updateSynonyms] Updating synonyms', true);
        $reloadRequired = false;
        // Fetch synonyms from local DB
        $synonymsQuery = 'SELECT * FROM synonyms';
        $dbSynonyms = $this->dbReadAdapter->fetchAll($synonymsQuery);

        // Prepare synonyms
        $newSynonyms = array();
        foreach ($dbSynonyms as $dbSynonym) {
            $dbSynonym['term'] = trim($dbSynonym['term']);
            if (!$dbSynonym['term']) {
                continue;
            }
            $terms = explode(',', $dbSynonym['synonyms']);
            $terms[] = $dbSynonym['term'];
            $terms = array_unique($terms);
            sort($terms);
            foreach ($terms as $idx => $term) {
                $terms[$idx] = trim($term);
                if (!$terms[$idx] || preg_match('/[\/]/', $terms[$idx])) {
                    $this->_log('[_updateSynonyms] Skipping invalid term ' . $term, true);
                    unset($terms[$idx]);
                }
            }
            foreach ($terms as $term) {
                if (!isset($newSynonyms[$term])) {
                    $newSynonyms[$term] = array();
                }
                $newSynonyms[$term] = array_merge($newSynonyms[$term], $terms);
            }
        }

        list($managedLanguage, $schemaData) = $this->_getSchemaData('synonyms');

        // Delete all current synonyms
        $synonyms = $schemaData['synonymMappings']['managedMap'];
        $this->_log('[_updateSynonyms] Deleting old synonyms', true);
        foreach ($synonyms as $synonymKey => $synonym) {
            if (!isset($newSynonyms[$synonymKey]) || array_diff($synonym, $newSynonyms[$synonymKey])) {
                $reloadRequired = true;
                $this->_log('[_updateSynonyms] Deleting ' . $managedLanguage . '/' . $synonymKey, true);
                $this->_curlDelete($this->solr, 'schema/analysis/synonyms/' . $managedLanguage . '/' . urlencode($synonymKey));
            }
        }
        // Ensure stopwords and synonyms ignore case
        if (!$schemaData['synonymMappings']['initArgs']['ignoreCase']) {
            $reloadRequired = true;
            $this->_log('[_updateSynonyms] Set ignoreCase', true);
            $this->_curlPost($this->solr, 'schema/analysis/synonyms/' . $managedLanguage,
                '{"initArgs":{"ignoreCase":true}}');
            $this->_curlPost($this->solr, 'schema/analysis/stopwords/' . $managedLanguage,
                '{"initArgs":{"ignoreCase":true}}');
        }
        // Send new synonyms to solr
        $this->_log('[_updateSynonyms] Adding new synonyms', true);
        foreach ($newSynonyms as $newSynonymKey => $newSynonym) {
            if (!isset($synonyms[$newSynonymKey]) || array_diff($newSynonym, $synonyms[$newSynonymKey])) {
                $reloadRequired = true;
                $this->_log('[_updateSynonyms] Adding ' . $managedLanguage . '/' . $newSynonymKey, true);
                $this->_curlPost($this->solr, 'schema/analysis/synonyms/' . $managedLanguage,
                    json_encode(array($newSynonymKey => $newSynonym)));
            }
        }
        // Reload the solr core to make the changes current
        if ($reloadRequired) {
            $this->_reloadCore('synonyms');
        } else {
            $this->_log('[_updateSynonyms] No change in synonyms, skipping core reload', true);
        }
        $this->_log('[_updateSynonyms] Update Synonyms done', true);
        $this->_log('======================================', true);
    }

    protected function _updateDictionary()
    {
        $this->_log('[_updateDictionary] Updating dictionary', true);
        $reloadRequired = false;
        // Fetch synonyms from local DB
        $dictionaryQuery = 'SELECT * FROM schrack_dictionary';
        try {
            $dbDictionaryTerms = $this->dbReadAdapter->fetchAll($dictionaryQuery);
        } catch (\Exception $e) {
            $this->_log('[_updateDictionary] Error reading dictionary table, skipping', true);
            return;
        }

        // Prepare dictionary terms
        $newDictionaryTerms = [];
        foreach ($dbDictionaryTerms as $dbDictionaryTerm) {
            $dbDictionaryTerm = trim($dbDictionaryTerm['term']);
            if (!$dbDictionaryTerm
                || preg_match('/[\/]/', $dbDictionaryTerm)
            ) {
                $this->_log('[_updateDictionary] Skipping invalid term ' . $dbDictionaryTerm, true);
                continue;
            }
            $newDictionaryTerms[$dbDictionaryTerm] = $dbDictionaryTerm;
        }

        list($managedLanguage, $schemaData) = $this->_getSchemaData('dictionary');

        if ($schemaData === null) {
            $this->_log('[_updateDictionary] Skipping processing, no managed dictionary found', true);
            return;
        }

        // Get current solr terms
        $dictionaryTerms = [];
        if ($schemaData['wordSet']['managedList'] && is_array($schemaData['wordSet']['managedList'])) {
            // Set keys = values
            $dictionaryTerms = array_combine($schemaData['wordSet']['managedList'], $schemaData['wordSet']['managedList']);
            if (!$dictionaryTerms) {
                $this->_log('[_updateDictionary] Error loading dictionary terms from solr', true);
                return;
            }
        }

        if ($dictionaryTerms) {
            // Delete stale current terms
            $this->_log('[_updateDictionary] Deleting old dictionary terms', true);
            foreach ($dictionaryTerms as $dictionaryTerm) {
                if (!isset($newDictionaryTerms[$dictionaryTerm])) {
                    $reloadRequired = true;
                    $this->_log('[_updateDictionary] Deleting ' . $managedLanguage . '/' . $dictionaryTerm, true);
                    $this->_curlDelete($this->solr, 'schema/analysis/dictionary/' . $managedLanguage . '/' . urlencode($dictionaryTerm));
                    unset($dictionaryTerms[$dictionaryTerm]);
                }
            }
        }
        // Ensure dictionary ignores case
        if (!$schemaData['wordSet']['initArgs']['ignoreCase']) {
            $reloadRequired = true;
            $this->_log('[_updateDictionary] Set ignoreCase', true);
            $this->_curlPost($this->solr, 'schema/analysis/dictionary/' . $managedLanguage,
                '{"initArgs":{"ignoreCase":true}}');
        }
        // Send new dictionary terms to solr
        $this->_log('[_updateDictionary] Adding new dictionary terms', true);
        foreach ($newDictionaryTerms as $newDictionaryTerm) {
            if (!isset($dictionaryTerms[$newDictionaryTerm])) {
                $reloadRequired = true;
                $this->_log('[_updateDictionary] Adding ' . $managedLanguage . '/' . $newDictionaryTerm, true);
                $this->_curlPost($this->solr, 'schema/analysis/dictionary/' . $managedLanguage,
                    json_encode([$newDictionaryTerm])
                );
            }
        }
        // Reload the solr core to make the changes current
        if ($reloadRequired) {
            $this->_reloadCore('dictionary');
        } else {
            $this->_log('[_updateDictionary] No change in dictionary terms, skipping core reload', true);
        }
        $this->_log('[_updateDictionary] Update dictionary terms done', true);
        $this->_log('======================================', true);
    }

    protected function _updateFacets()
    {
        $this->_log('[_updateFacets] Updating facets.', true);
        $categoryCollection = Mage::getModel('catalog/category')->getCollection();
        $categoryCollection->addAttributeToFilter('children_count', array('gt' => 0))
            ->getSelect()
            ->order('level DESC')
            ->limit(1);
        $maxLevelCategory = array_pop($categoryCollection->load()->exportToArray());
        for ($level = $maxLevelCategory['level']; $level > 0; $level--) {
            $categoryCollection = Mage::getModel('catalog/category')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('children_count', array('gt' => 0))
                ->addAttributeToFilter('level', array('eq' => $level))
                ->load();
            /** @var Schracklive_SchrackCatalog_Model_Category $category */
            foreach ($categoryCollection->getItems() as $category) {
                $categoryFacets = array();
                $childCategories = $category->getChildrenCategories();
                foreach ($childCategories as $childCategory) {
                    // Children don't have all attributes, load completely
                    $childCategory->load($childCategory->getId());
                    $schrackFacetList = $childCategory->getSchrackFacetList();
                    if ($schrackFacetList) {
                        $categoryFacets = array_merge($categoryFacets, explode(',', $schrackFacetList));
                    }
                    unset($schrackFacetList);
                }
                $categoryFacets = array_unique($categoryFacets);
                // Find top 10 facets out of this list
                $query = $this->solr->createSelect();
                $query->createFilterQuery('category')->setQuery('category_ids_intM:' . $category->getId());
                $facetSet = $query->getFacetSet();
                $facetSet->setMinCount(1);
                $facetSet->setLimit(100);
                foreach ($categoryFacets as $categoryFacet) {
                    $facetSet->createFacetField($categoryFacet . '_facet')->setField($categoryFacet . '_facet');
                }
                $topFacets = array();
                $resultset = $this->solr->select($query);
                if ($resultset->getNumFound() > 0) {
                    foreach ($categoryFacets as $categoryFacet) {
                        $facet = $resultset->getFacetSet()->getFacet($categoryFacet . '_facet');
                        $facetCount = 0;
                        foreach ($facet as $value => $count) {
                            $facetCount += $count;
                        }
                        if ($facetCount > 0) {
                            $topFacets[$categoryFacet] = $facetCount;
                        }
                    }
                    arsort($topFacets);
                }
                $category->setSchrackFacetList(implode(',', array_slice(array_keys($topFacets), 0, 20)));
                $category->save();
            }
        }
        $this->_log('[_updateFacets] Updating facets done', true);
        $this->_log('======================================', true);
    }

    protected function _postToSolr()
    {
        $this->_log('[_postToSolr] Posting data to solr', true);
        $line = 0;
        $rawPost = '';
        while ($solrDocument = $this->getSolrDocumentFromGzFile($this->solrDocsFile)) {
            $rawPost .= $solrDocument;
            if ($line > 0 && $line % 1000 === 0) {
                $this->_curlPost($this->solr, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
                $this->_log('[_postToSolr] ' . $line . ' products posted', true);
                $rawPost = '';
            }
            $line++;
        }
        $this->_curlPost($this->solr, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
        $postEnd = time();
        $elapsedMinutes = ceil(($postEnd - $this->timeStart) / 60) + 5;
        $update = $this->solr->createUpdate();
        $update->addDeleteQuery('appKey:mage AND indexed:[* TO NOW-' . $elapsedMinutes . 'MINUTE]');
        $update->addCommit();
        $update->addOptimize();
        $this->solr->update($update);
        $this->_log('[_postToSolr] Post to solr done', true);
        $this->_log('======================================', true);
    }

    protected function _postToSolrCommon()
    {
        $this->_log('[_postToSolrCommon] Posting data to common solr', true);
        $line = 0;
        $rawPost = '';
        while ($solrDocument = $this->getSolrDocumentFromGzFile($this->solrDocsCommonFile)) {
            $rawPost .= $solrDocument;
            if ($line > 0 && $line % 1000 === 0) {
                $this->_curlPost($this->solrCommon, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
                $rawPost = '';
            }
            $line++;
        }
        $this->_curlPost($this->solrCommon, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
        $postEnd = time();
        $elapsedMinutes = ceil(($postEnd - $this->timeStart) / 60) + 5;
        $update = $this->solrCommon->createUpdate();
        $update->addDeleteQuery('country_stringS:' . $this->country . ' AND indexed:[* TO NOW-' . $elapsedMinutes . 'MINUTE]');
        $update->addCommit();
        $update->addOptimize();
        $this->solrCommon->update($update);
        $this->_log('[_postToSolrCommon] Post to solr common done', true);
        $this->_log('======================================', true);
    }

    protected function _getOptionLabel($attributeCode, $attributeOptionId)
    {
        $optionValue = '';
        if (isset($this->eavOptionValues[$attributeCode])) {
            if (isset($this->eavOptionValues[$attributeCode][$attributeOptionId])) {
                return $this->eavOptionValues[$attributeCode][$attributeOptionId];
            } else {
                return $optionValue;
            }
        }
        $eavQuery = 'SELECT eav_attribute_option_value.option_id, value, store_id FROM eav_attribute
			JOIN eav_attribute_option ON (eav_attribute.attribute_id = eav_attribute_option.attribute_id)
			JOIN eav_attribute_option_value ON(eav_attribute_option.option_id = eav_attribute_option_value.option_id)
			WHERE eav_attribute.entity_type_id=4 AND eav_attribute.attribute_code = "' . $attributeCode . '"
			AND (eav_attribute_option_value.store_id = ' . $this->store . ' OR eav_attribute_option_value.store_id = 0)
			ORDER BY eav_attribute_option_value.store_id DESC';
        $attributeValues = $this->dbReadAdapter->fetchAll($eavQuery);
        if (!isset($this->eavOptionValues[$attributeCode])) {
            $this->eavOptionValues[$attributeCode] = array();
        }
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['store_id'] == $this->store || !isset($this->eavOptionValues[$attributeCode][$attributeValue['option_id']])) {
                $this->eavOptionValues[$attributeCode][$attributeValue['option_id']] = trim($attributeValue['value']);
                if ($attributeValue['option_id'] == $attributeOptionId) {
                    $optionValue = $this->eavOptionValues[$attributeCode][$attributeValue['option_id']];
                }
            }
        }
        return $optionValue;
    }

    /**
     * @param string $type
     *
     * @return array|null
     */
    protected function _getSchemaData(string $type)
    {
        if ($type === 'dictionary') {
            $solrFactory = 'solr.ManagedDictionaryFilterFactory';
        } elseif ($type === 'synonyms') {
            $solrFactory = 'solr.ManagedSynonymGraphFilterFactory';
        } else {
            $this->_log('[_getSchemaData] Called with invalid type ' . $type, true);
            return null;
        }

        // Read solr schema
        $this->_log('[_getSchemaData] Fetching solr schema', true);
        $schemaResponse = $this->_curlGet($this->solr, 'schema/fieldtypes/text');
        if ((int)$schemaResponse->getStatusCode() !== 200) {
            $this->_log('[_getSchemaData] Can\'t get schema, skipping', true);
            return null;
        }
        $schema = json_decode($schemaResponse->getBody());
        foreach ([$schema->fieldType->indexAnalyzer->filters, $schema->fieldType->queryAnalyzer->filters] as $analyzerFilters) {
            foreach ($analyzerFilters as $filter) {
                if ($filter->class === $solrFactory) {
                    $managedLanguage = $filter->managed;
                    break;
                }
            }
        }
        if (!isset($managedLanguage)) {
            $this->_log('[_getSchemaData] Can\'t find managed language for ' . $solrFactory . ', skipping', true);
            return null;
        }
        // Fetch current synonyms
        $this->_log('[_getSchemaData] Fetching currently active terms for ' . $solrFactory, true);
        $synonymsResponse = $this->_curlGet($this->solr, 'schema/analysis/' . $type . '/' . $managedLanguage);
        if ((int)$synonymsResponse->getStatusCode() !== 200) {
            $this->_log('[_getSchemaData] Can\'t get currently active terms for ' . $solrFactory . ', skipping', true);
            return null;
        }
        return [
            $managedLanguage,
            json_decode($synonymsResponse->getBody(), true)
        ];
    }

    protected function _reloadCore($mode)
    {
        $corePath = explode('/', trim($this->solr->getEndpoint()->getPath(), '/'));
        $url = $this->solr->getEndpoint()->getScheme() . '://' . $this->solr->getEndpoint()->getHost() . ':' . $this->solr->getEndpoint()->getPort() . '/solr/admin/cores?wt=json&action=RELOAD&core=' . array_pop($corePath);
        $this->_log('[_reloadCore] (' . $mode . ') Reloading solr core ' . $url, true);
        try {
            $curlAdapter = new Solarium\Core\Client\Adapter\Curl();
            $curlHandle = $curlAdapter->createHandle(new Solarium\Core\Client\Request(), $this->solr->getEndpoint());
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
            $response = $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
        } catch (\Solarium\Exception\HttpException $e) {
            $this->_fail('[_reloadCore] (' . $mode . ') HTTP Exception: ' . $e->getMessage(), true);
        }
        if ($response) {
            if ((int)$response->getStatusCode() === 200) {
                $this->_log('[_reloadCore] (' . $mode . ') Core reloaded', true);
            } else {
                $this->_fail('[_reloadCore] (' . $mode . ') Core reload failed, status ' . $response->getStatusCode(), true);
            }
        } else {
            $this->_log('[_reloadCore] (' . $mode . ') Core reloaded failed, because there is no response from SOLR Server [ERROR]', true);

            // Send E-Mail to Developer and inform about bad SOLR state:
            $mail = new Zend_Mail('utf-8');
            $mail->setFrom('shop_' . Mage::getStoreConfig('general/locale/code'), 'SOLR Problem')
                ->setSubject('ATTENTION: ')
                ->setBodyHtml(strtoupper(Mage::getStoreConfig('schrack/general/country')) . ' : please check SOLR ' . $mode . ': SOLR Server did not response to shop request');
            $mail->addTo('webshop.helpdesk@schrack.com');
            $mail->send();
        }
    }

    /**
     * @param string $key Config key of the magento setting (relative to schrack/solr)
     * @return bool|Client
     */
    protected function _getSolrClient($key = 'solrserver')
    {
        if (!Mage::getStoreConfig('schrack/solr/' . $key, $this->store)) {
            return false;
        }
        // @todo check for broken URL
        if ($key == 'solrserver') {
            $this->solrUrl = Mage::getStoreConfig('schrack/solr/' . $key, $this->store);
            if ($this->solrUrl[strlen($this->solrUrl) - 1] != '/') {
                $this->solrUrl .= '/';
            }
        }
        $solrServerUrl = parse_url(Mage::getStoreConfig('schrack/solr/' . $key, $this->store));
        // Return false if path is incomplete
        if (!isset($solrServerUrl['host']) || !isset($solrServerUrl['port']) || !isset($solrServerUrl['path'])) {
            return false;
        }
        $solrServerUrl['timeout'] = 0;
        $solrServerUrl['username'] = 'write';
        $solrServerUrl['password'] = Mage::getStoreConfig('schrack/solr/write_password');
        $config = array(
            'endpoint' => array(
                'localhost' => $solrServerUrl,
            ),
        );
        $client = new Client($config);
        $client->getPlugin('postbigrequest');
        return $client;
    }

    protected function _getLabel($attribute)
    {
        //$attribute = substr($attribute, 0, strrpos($attribute, '_'));
        $attributeModel = Mage::getModel('catalog/entity_attribute');
        $entityType = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

        return $attributeModel->loadByCode($entityType, $attribute)->getFrontendLabel();
    }

    /**
     * Replace control (non-printable) characters from string that are invalid to Solr's XML parser with a space.
     *
     * @param string $string
     * @return string
     */
    protected function _stripCtrlChars($string)
    {
        // See:  http://w3.org/International/questions/qa-forms-utf-8.html
        // Printable utf-8 does not include any of these chars below x7F
        return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $string);
    }

    protected function resolveAttributeValue($attributeId, $attributeValue)
    {
        if (!is_object($attributeValue) && !is_array($attributeValue)) {
            $attributeValue = trim($attributeValue);
        }
        if (!is_object($attributeValue) && !in_array($attributeValue, $this->skipFields)
            && ((is_string($attributeValue) && $attributeValue !== '')
                || (is_array($attributeValue) && count($attributeValue) > 0)
            )
        ) {
            // Try to resolve Attribute Option to Label
            if (strpos($this->attributes[$attributeId]['attribute_code'], 'schrack_') === 0
                && (in_array($this->attributes[$attributeId]['frontend_input'], array('select', 'multiselect')))
            ) {
                $valExploded = explode(',', $attributeValue);
                if (is_numeric($attributeValue)) {
                    $options = $this->_getOptionLabel($this->attributes[$attributeId]['attribute_code'], $attributeValue);
                } elseif (is_array($valExploded)) {
                    $options = array();
                    foreach ($valExploded as $valOption) {
                        if (is_numeric($valOption)) {
                            $options[] = $this->_getOptionLabel($this->attributes[$attributeId]['attribute_code'], $valOption);
                        }
                    }
                }
                if (isset($options)
                    && ((is_string($options) && $options !== '')
                        || (is_array($options) && count($options) > 0)
                    )
                ) {
                    if (is_array($options)) {
                        $attributeValue = implode(chr(31), $options);
                    } else {
                        $attributeValue = $options;
                    }
                    unset($options);
                }
            }
            if (is_string($attributeValue) && $attributeValue !== '') {
                return $attributeValue;
            }
        }
        return null;
    }

    /**
     * Create an XML fragment from an array appropriate for use inside a Solr add call
     *
     * @param array $data
     * @return string
     */
    protected function _arrayToXmlFragment(array $data)
    {
        $xml = '<doc>';
        foreach ($data as $key => $value) {
            $key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            if (is_array($value)) {
                foreach ($value as $multivalue) {
                    $xml .= '<field name="' . $key . '"';
                    $multivalue = htmlspecialchars($multivalue, ENT_NOQUOTES, 'UTF-8');
                    $xml .= '>' . $multivalue . '</field>';
                }
            } else {
                $xml .= '<field name="' . $key . '"';
                $value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
                $xml .= '>' . $value . '</field>';
            }
        }
        $xml .= '</doc>';

        // replace any control characters to avoid Solr XML parser exception
        return $this->_stripCtrlChars($xml);
    }

    /**
     * @param Client $solr
     * @param string $path
     * @return \Solarium\Core\Client\Response
     */
    protected function _curlDelete($solr, $path)
    {
        $postRequest = new Solarium\Core\Client\Request();
        $postRequest->setMethod(Solarium\Core\Client\Request::METHOD_POST);
        $curlAdapter = new Solarium\Core\Client\Adapter\Curl();
        $curlHandle = $curlAdapter->createHandle($postRequest, $solr->getEndpoint());
        curl_setopt($curlHandle, CURLOPT_URL, $solr->getEndpoint()->getBaseUri() . $path);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        // Set a 10 second timeout; If we get nothing within that timeframe, it's safe to assume the connection is dead
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        try {
            $response = $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
        } catch (\Solarium\Exception\HttpException $e) {
            $this->_fail('[_curlDelete] HTTP Exception: ' . $e->getMessage(), true);
        }

        return $response;
    }

    /**
     * @param Client $solr
     * @param string $path
     * @param string $data
     * @param string $contentType
     * @return \Solarium\Core\Client\Response|null
     */
    protected function _curlPost($solr, $path, $data, $contentType = 'application/json')
    {
        $response = null;
        $postRequest = new Solarium\Core\Client\Request();
        $postRequest->setMethod(Solarium\Core\Client\Request::METHOD_POST);
        $postRequest->addHeader('Content-Type: ' . $contentType);
        $postRequest->setRawData($data);
        $curlAdapter = new Solarium\Core\Client\Adapter\Curl();
        $curlHandle = $curlAdapter->createHandle($postRequest, $solr->getEndpoint());
        curl_setopt($curlHandle, CURLOPT_URL, $solr->getEndpoint()->getBaseUri() . $path);
        // Set a 60 second timeout; If we get nothing within that timeframe, it's safe to assume the connection is dead
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 120);
        try {
            $response = $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
        } catch (\Solarium\Exception\HttpException $e) {
            $this->_fail('[_curlPost] HTTP Exception: ' . $e->getMessage(), true);
        }

        return $response;
    }

    /**
     * @param Client $solr
     * @param string $path
     * @return \Solarium\Core\Client\Response
     */
    protected function _curlGet($solr, $path)
    {
        $curlAdapter = new Solarium\Core\Client\Adapter\Curl();
        $curlHandle = $curlAdapter->createHandle(new Solarium\Core\Client\Request(), $solr->getEndpoint());
        curl_setopt($curlHandle, CURLOPT_URL, $solr->getEndpoint()->getBaseUri() . $path);
        // Set a 30 second timeout; If we get nothing within that timeframe, it's safe to assume the connection is dead
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        try {
            $response = $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
        } catch (\Solarium\Exception\HttpException $e) {
            $this->_fail('[_curlGet] HTTP Exception: ' . $e->getMessage(), true);
        }

        return $response;
    }
}

<?php

use Solarium\Client;

class Schracklive_Search_Helper_ExportSales extends Mage_Core_Helper_Abstract
{
    /** @var Mage_Core_Model_Resource */
    var $dbResource;
    /** @var Varien_Db_Adapter_Pdo_Mysql */
    var $dbReadAdapter;
    /** @var int Starting timestamp */
    var $timeStart;
    /** @var int Store ID */
    var $store = 1;
    /** @var string Mode */
    var $mode = 'delta';
    /** @var string */
    var $solrUrl = '';
    /** @var bool|Client Shop-specific solr connector */
    var $solr;
    /** @var string Shop TLD */
    var $country;
    /** @var string Shop locale */
    var $locale;
    /** @var string Shop URL */
    var $baseUrl;
    /** @var string Filename of serialized full product info solr documents */
    var $solrDocsFile = "solrSalesDocs_%u.gz";
    var $magentoOptions;
    var $solrDocuments = array();

    public function __construct()
    {
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
        $this->timeStart = time();
        if (!$skipAvailabilityCheck) {
            $this->solr = $this->_getSolrClient();
            if (!$this->solr) {
                $this->_fail('Country specific solr URL not found or invalid in Magento Backend', true);
            }
        }
        $this->setStore($this->store);
        if (!$skipAvailabilityCheck) {
            if (!$this->solr) {
                $this->_fail('solr config incorrect, cannot continue, please check backend settings', true);
            }
            if (!$this->solr->ping($this->solr->createPing())) {
                $this->_fail('ping to solr path ' . $this->solr->getEndpoint()->getPath() . ' failed, please check solr server availability',
                    true);
            }
        }
        $this->dbResource = Mage::getSingleton('core/resource');
        $this->dbReadAdapter = $this->dbResource->getConnection('core_read');
    }

    public function setStore($store)
    {
        $this->store = $store;
        $this->country = Mage::getStoreConfig('schrack/general/country', $this->store);
        $this->locale = str_replace('_', '-', Mage::getStoreConfig('general/locale/code', $this->store));
        $this->baseUrl = Mage::getStoreConfig('web/unsecure/base_url', $this->store);
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
        if ($this->mode != 'delta' && $this->mode != 'full') {
            $this->_log('Invalid mode! Exiting', true);
            exit;
        }
    }

    public function runAll()
    {
        $this->_init();
        $this->_buildSolrData();
        $this->_postToSolr();
        $this->_logRuntime();
    }

    public function runPost()
    {
        $this->mode = 'full';
        $this->_init();
        $this->_postToSolr();
        $this->_logRuntime();
    }

    public function runBuild()
    {
        $this->_init(true);
        $this->_buildSolrData();
        $this->_logRuntime();
    }

    private function _buildSolrData($debug = false)
    {
        // Figure out time of last index
        if ($this->mode == 'delta') {
            $indexedFile = filemtime($this->magentoOptions['tmp_dir'] . DS . sprintf($this->solrDocsFile, $this->store));
            if ($indexedFile) {
                // Fetch newest product entry from solr
                $newestSolrDoc = json_decode(file_get_contents($this->solrUrl . 'select?q=type:sales&fl=indexed&wt=json&sort=indexed%20desc&rows=1'));
                if ($newestSolrDoc
                    && property_exists($newestSolrDoc, 'response')
                    && property_exists($newestSolrDoc->response, 'docs')
                    && isset($newestSolrDoc->response->docs[0])
                    && property_exists($newestSolrDoc->response->docs[0], 'indexed')
                ) {
                    // The oldest date wins
                    $indexedSolr = strtotime($newestSolrDoc->response->docs[0]->indexed);
                    if ($indexedFile < $indexedSolr) {
                        $indexed = date('Y-m-d', $indexedFile);
                    } else {
                        $indexed = date('Y-m-d', $indexedSolr);
                    }
                    unset($indexedSolr);
                } else {
                    $this->_log('No existing solr index found, falling back to full mode', true);
                    $this->mode = 'full';
                }
                unset($newestSolrDoc);
                unset($indexedFile);
            } else {
                $this->_log('No existing solr index file found, falling back to full mode', true);
                $this->mode = 'full';
            }
        }
        $columns = array('entity_id', 'state', 'status', 'shipping_description', 'store_id', 'customer_id', 'base_grand_total', 'base_subtotal', 'base_tax_amount', 'grand_total', 'subtotal', 'tax_amount', 'total_qty_ordered', 'billing_address_id', 'customer_group_id', 'quote_id', 'shipping_address_id', 'base_subtotal_incl_tax', 'base_total_due', 'subtotal_incl_tax', 'total_due', 'increment_id', 'base_currency_code', 'customer_email', 'customer_firstname', 'customer_lastname', 'customer_prefix', 'shipping_method', 'created_at', 'updated_at', 'total_item_count', 'customer_gender', 'schrack_wws_order_number', 'schrack_wws_creation_date', 'schrack_wws_status', 'schrack_is_complete', 'schrack_is_orderable', 'schrack_wws_offer_number', 'schrack_wws_offer_date', 'schrack_wws_offer_valid_thru', 'schrack_wws_offer_flag_valid', 'schrack_wws_web_send_no', 'schrack_wws_reference', 'schrack_tax_total', 'schrack_wws_customer_id', 'schrack_payment_terms', 'schrack_shipment_mode', 'schrack_wws_place_memo', 'schrack_wws_ship_memo', 'schrack_is_current_downloaded', 'schrack_wws_operator_mail', 'schrack_customer_project_info', 'schrack_customer_delivery_info', 'schrack_sp_reference_1', 'schrack_sp_reference_2', 'schrack_sp_reference_3', 'schrack_sp_reference_4', 'schrack_sp_reference_5');
        $pageNumbers = 1;
        for ($i = 1; $i <= $pageNumbers; $i++) {
            /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setPageSize(10)
                ->setCurPage($i);
            if ($this->mode == 'delta' && isset($indexed)) {
                $collection->addAttributeToFilter('updated_at', array('gteq' => $indexed));
            }
            $collection->load();
            $pageNumbers = $collection->getLastPageNumber();
            // Export collection as array and clear all unneeded data
            /** @var Schracklive_SchrackSales_Model_Order[] $salesDataRows */
            $salesDataRows = $collection->getItems();
            foreach ($salesDataRows as $salesDataRow) {
                $solrData = array(
                    'id' => 'mage_order_' . $this->country . '_' . $this->store . '_0_' . $salesDataRow->entity_id,
                    'country' => $this->country,
                    'appKey' => 'mage',
                    'type' => 'order',
                    'items' => array()
                );
                foreach ($columns as $column) {
                    $data = $salesDataRow->$column;
                    if ($data) {
                        $solrData[$column] = $data;
                    }
                }
                /** @var Mage_Sales_Model_Order_Item[] $items */
                $items = $salesDataRow->getAllItems();
                foreach ($items as $item) {
                    $solrData['items'][] = $item->getProductId() . '|' . $item->getSku() . '|' . $item->getName() . '|' . $item->getQtyOrdered() . '|' . $item->getPrice();
                }
                $this->solrDocuments[] = $this->_arrayToXmlFragment($solrData);
            }
            break;
        }

        // Delta has to merge new data with existing, replacing all entries for changed SKUs
        if ($this->mode == 'delta') {
            // Merge with existing documents in case of delta
            $oldDocs = $this->_loadSolrDocumentsFromGzFile($this->solrDocsFile);
            $this->_log("Merging " . count($this->solrDocuments) . " new docs with " . count($oldDocs) . " old docs for storage", true);
            $docs = array_merge($this->solrDocuments, $oldDocs);
            unset($oldDocs);
            if (!$this->_writeSolrDocs($this->solrDocsFile, $docs, $debug)) {
                $this->_log("Couldn't create solr data, not submitting new data to solr", true);
                exit;
            }
            unset($docs);
        } elseif ($this->mode == 'full') {
            if (!$this->_writeSolrDocs($this->solrDocsFile, $this->solrDocuments, $debug)) {
                $this->_log("Couldn't create solr data, not submitting new data to solr", true);
                exit;
            }
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
        if (!isset($solrServerUrl['host']) || !isset($solrServerUrl['port'])) {
            return false;
        }
        $solrServerUrl['timeout'] = 0;
        $solrServerUrl['path'] = '/solr/orderData/';
        $config = array(
            'endpoint' => array(
                'localhost' => $solrServerUrl,
            ),
        );
        $client = new Client($config);
        $client->getPlugin('postbigrequest');
        return $client;
    }

    protected function _postToSolr()
    {
        $this->_log('Posting data to solr', true);
        $this->solrDocuments = $this->_loadSolrDocumentsFromGzFile($this->solrDocsFile);
        if ($this->solrDocuments) {
            $rawPost = '';
            foreach ($this->solrDocuments as $line => $doc) {
                $rawPost .= $doc;
                if ($line > 0 && $line % 1000 === 0) {
                    $this->_curlPost($this->solr, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
                    $rawPost = '';
                }
            }
            $this->_curlPost($this->solr, 'update', '<add>' . $rawPost . '</add>', 'text/xml');
            $postEnd = time();
            $elapsedMinutes = ceil(($postEnd - $this->timeStart) / 60) + 5;
            $update = $this->solr->createUpdate();
            if ($this->mode == 'delta' || $this->mode == 'full') {
                $update->addDeleteQuery('appKey:mage AND indexed:[* TO NOW-' . $elapsedMinutes . 'MINUTE]');
            }
            $update->addCommit();
            $update->addOptimize();
            $this->solr->update($update);
        }
    }

    protected function _logRuntime()
    {
        $timeEnd = time();
        $passedMinutes = ($timeEnd - $this->timeStart) / 60;
        $this->_log('Runtime: ' . $passedMinutes . ' minutes', true);
        $this->_log('Max RAM used: ' . memory_get_peak_usage(), true);
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
        throw new Exception($data);
    }

    protected function _writeSolrDocs($docFile, array $docs, $debug = false)
    {
        if (!$debug) {
            $file = gzopen($this->magentoOptions['tmp_dir'] . DS . sprintf($docFile, $this->store), 'w5');
            if (!$file) {
                $this->_log("Can't open " . sprintf($docFile, $this->store), true);
                return false;
            }
            foreach ($docs as $doc) {
                if (!gzwrite($file, str_replace("\n", "###newline###", $doc) . "\n")) {
                    $this->_log("Can't write " . sprintf($docFile, $this->store), true);
                    gzclose($file);
                    return false;
                }
            }
            $this->_log(sprintf($docFile, $this->store) . " write successful", true);
            gzclose($file);
        } else {
            foreach ($docs as $doc) {
                echo $doc;
            }
        }

        return true;
    }

    protected function _loadSolrDocumentsFromGzFile($fileBase)
    {
        $solrDocuments = array();
        $file = gzopen($this->magentoOptions['tmp_dir'] . DS . sprintf($fileBase, $this->store), 'r');
        if ($file) {
            while (!gzeof($file)) {
                $solrDocuments[] = trim(str_replace('###newline###', "\n", gzgets($file)));
            }
            gzclose($file);
        }
        return $solrDocuments;
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

    protected function _curlDelete($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);
        return $result;
    }

    /**
     * @param Client $solr
     * @param string $path
     * @param string $data
     * @param string $contentType
     * @return \Solarium\Core\Client\Response
     */
    protected function _curlPost($solr, $path, $data, $contentType = 'application/json')
    {
        $postRequest = new Solarium\Core\Client\Request();
        $postRequest->setMethod(Solarium\Core\Client\Request::METHOD_POST);
        $postRequest->addHeader('Content-Type: ' . $contentType);
        $postRequest->setRawData($data);
        $curlAdapter = new Solarium\Core\Client\Adapter\Curl();
        $curlHandle = $curlAdapter->createHandle($postRequest, $solr->getEndpoint());
        curl_setopt($curlHandle, CURLOPT_URL, $solr->getEndpoint()->getBaseUri() . $path);
        return $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
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
        return $curlAdapter->getResponse($curlHandle, curl_exec($curlHandle));
    }
}

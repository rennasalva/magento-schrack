<?php

use com\schrack\queue\protobuf\Message;

class Schracklive_SchrackCatalog_Model_Protoimport extends Schracklive_SchrackCatalog_Model_Protoimport_Base {
    
    /* $var _importMsg com\schrack\queue\protobuf\Message */
	var $_importMsg;
    var $_groupsIdMap;
    var $_oldCategoryMap;
    var $_articleIdMap;
    var $_semaphoreFileName = '/tmp/protoimporter_semaphore';
    
    var $_doGroups                      = true;
    var $_doArticles                    = true;
    var $_doRelations                   = true;
    var $_doReindexing                  = true;
    var $_doSolrExport                  = true;
    var $_doMegaMenuUpdate              = true;
    var $_doTranslations                = true;
    var $_doSynonyms                    = true;
    var $_doDictionary                  = true;
    var $_doRewrites                    = true;
    var $_doRemoveEmptyCats             = true;
    var $_doWarmupCache                 = true;

    var $_finalize              = false;

    var $_ignoreLastPackages    = false;
    var $_lastDumpFilePath      = false;
    var $_lastRetrievedDumpMsg  = null;
    
    function __construct() {
        parent::__construct();
        ini_set('memory_limit', '2048M');
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $maxSolr = Mage::getStoreConfig('schrack/product_import/running_solr_exports');
        if ( ! $maxSolr ) {
            $maxSolr = 2;
        }
        $postfix = '_' . rand(1,$maxSolr);
        $this->_semaphoreFileName .= $postfix;
        echo $this->_semaphoreFileName . "\n";
    }
    
    public function dump ( &$binData ) {
        $dumper = new Schracklive_SchrackCatalog_Model_Protoimport_Dumper();
        $dumper->dump($binData);
    }

    public function dumpStructure ( &$binData ) {
        $this->_importMsg = new Message($binData);
        $binData = null;
        $dumper = new Schracklive_SchrackCatalog_Model_Protoimport_Dumper();
        $dumper->dumpStructure($this->_importMsg);
    }

    public function serializeData ( &$binData ) {
		$time_start = microtime(true);
        self::log('Protoimport: serializing data...');
        self::beginTrace('parsing_data');
        $msg = new Message($binData);
        self::endTrace('parsing_data');
        $binData = null;
        self::beginTrace('serializing_data');
        $res = serialize($msg);
        self::endTrace('serializing_data');
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		self::log("Runtime: ".$time." seconds");
		self::log("Max RAM used #1: ".memory_get_peak_usage());
        return $res;
    }
    
    public function unserializeAndRun ( &$serialized, $overridePackageType = null ) {
		$time_start = microtime(true);
        try {
            self::log('');
            self::log('');
            self::logDebug('');
            self::logDebug('');
            self::log('running Protoimport...');
            self::beginTrace('unserializing_data');
            $this->_importMsg = unserialize($serialized);
            self::endTrace('unserializing_data');
            $serialized = null;
            $this->doRun($overridePackageType);
        }
        catch ( Exception $ex ) {
            Mage::logException($ex);
            self::log('!!! Protoimport interrupted with exception: "'.$ex->getMessage().'"');
            throw $ex;
        }
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		self::log("Runtime: ".$time." seconds");
		self::log("Max RAM used #2: ".memory_get_peak_usage());
    }
    
    public function run ( &$binData, $overridePackageType = null, $originTimestamp = null ) {
        $this->_originTimestamp = $originTimestamp;
		$time_start = microtime(true);
        try {
            self::log('running Protoimport...');
            if ( $this->needInputData() ) {
                self::beginTrace('parsing_data');
                $this->_importMsg = new Message($binData);
                self::endTrace('parsing_data');
            }
            $binData = null;
            gc_collect_cycles();
            $this->doRun($overridePackageType);
        }
        catch ( Exception $ex ) {
            Mage::logException($ex);
            self::log('!!! Protoimport interrupted with exception: "'.$ex->getMessage().'"');
            throw $ex;
        }
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		self::log("Runtime: ".$time." seconds");
		self::log("Max RAM used #3: ".memory_get_peak_usage());
        self::logDebug('# finished #');
    }

    public function disableGroupProcessing ( $disable = true ) {
        $this->_doGroups = ! $disable;
    }
    public function disableArticleProcessing ( $disable = true ) {
        $this->_doArticles = ! $disable;
    }
    public function disableRelationProcessing ( $disable = true ) {
        $this->_doRelations = ! $disable;
    }
    public function disableReindexing ( $disable = true ) {
        $this->_doReindexing = ! $disable;
    }
    public function disableSolrExport ( $disable = true ) {
        $this->_doSolrExport = ! $disable;
    }

    public function disableMegaMenuUpdate ( $disable = true ) {
        $this->_doMegaMenuUpdate = ! $disable;
    }

    public function disableTranslations ( $disable = true ) {
        $this->_doTranslations = ! $disable;
    }
    public function disableSynonyms ( $disable = true ) {
        $this->_doSynonyms = ! $disable;
    }
    public function disableDictionary ( $disable = true ) {
        $this->_doDictionary = ! $disable;
    }
    public function disableRewrites ( $disable = true ) {
        $this->_doRewrites = ! $disable;
    }
    public function disableRemoveEmptyCats ( $disable = true ) {
        $this->_doRemoveEmptyCats = ! $disable;
    }
    public function disableWarmupCache ( $disable = true ) {
        $this->_doWarmupCache = ! $disable;
    }
    public function enableFinalize () {
        $this->disableAll();
        $this->_doReindexing = $this->_doSolrExport = $this->_doMegaMenuUpdate = $this->_doWarmupCache
                             = $this->_doReindexing = $this->_doRemoveEmptyCats
                             = $this->_doRewrites = true;
        $this->_finalize = true;
    }
    public function disableAll ( $disable = true ) {
        $this->_doGroups = $this->_doArticles = $this->_doRelations = $this->_doReindexing 
                         = $this->_doSolrExport = $this->_doMegaMenuUpdate = $this->_doRemoveEmptyCats
                         = $this->_doTranslations = $this->_doSynonyms = $this->_doDictionary = $this->_doRewrites
                         = $this->_doWarmupCache = ! $disable;
    }
    public function needInputData () {
        return $this->_doArticles || $this->_doTranslations || $this->_doSynonyms || $this->_doDictionary;
    }
    public function setIgnoreLastPackages ( $ignore = true ) {
        $this->_ignoreLastPackages = $ignore;
    }

    public function doRunCreateGroupUrlTmpFiles () {
        $rewriter = new Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler();
        $rewriter->saveOldStuff(); // TODO: remove that old behaviour after 3 months or something of running the new stuff
    }

    public function doRunCreateRewritesFromTmpFiles () {
        $rewriter = new Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler();
        $rewriter->addOldPathsAsPermanentRedirects();
    }

    private function doRun ( $overridePackageType ) {
        if ( $overridePackageType && $this->checkPackageType($overridePackageType,false) ) {
            $this->_importMsg->setPackagetype($overridePackageType);
        }

        if ( $this->needInputData() ) {
            if ($this->_importMsg) {
                $this->checkShop($this->_importMsg->getShop());
            }
            $this->checkPackageType($this->_importMsg->getPackagetype());
            
            $this->handleGroups();
            gc_collect_cycles();
            $this->handleArticles();
            gc_collect_cycles();
            $this->handleRelations();
            gc_collect_cycles();
            $this->handleTranslations();
            gc_collect_cycles();
            $this->handleSynonyms();
            gc_collect_cycles();
            $this->handleDictionary();
            gc_collect_cycles();
        }

        if ( ! $this->needInputData() || $this->_importMsg->getPackagetype() !== Schracklive_SchrackCatalog_Model_Protoimport_Base::PACKAGE_TYPE_PART ) {
            if (    $this->_ignoreLastPackages
                 && $this->_importMsg != null
                 && $this->_importMsg->getPackagetype() == Schracklive_SchrackCatalog_Model_Protoimport_Base::PACKAGE_TYPE_LAST ) {
                self::log('ignoring commit part for last package');
            } else {
                $this->commit();
            }
        }
        self::log('Protoimport done.');
    }
    
    private function checkPackageType ( $packageType, $throwOnError = true ) {
        if (    $packageType !== Schracklive_SchrackCatalog_Model_Protoimport_Base::PACKAGE_TYPE_FULL
             && $packageType !== Schracklive_SchrackCatalog_Model_Protoimport_Base::PACKAGE_TYPE_LAST
             && $packageType !== Schracklive_SchrackCatalog_Model_Protoimport_Base::PACKAGE_TYPE_PART ) {
            if ( $throwOnError ) {
                throw new Exception("Invalid package type $packageType got!");
            }
            return false;
        }
        return true;
    }

    private function checkShop ( $shopCountry, $throwOnError = true ) {
        $shopCountryCode = self::getCountryCode();
        if ( strncmp(strtoupper($shopCountry),strtoupper($shopCountryCode),2) !== 0 ) {
            if ( $throwOnError ) {
                throw new Exception("Invalid shop country $shopCountry for shop $shopCountryCode got!");
            }
            return false;
        }
        return true;
    }

    private function handleGroups ( $finalize = false ) {
        if ( ! $finalize ) {
            if ( ! $this->_doGroups || $this->_DO_ONLY_THAT_SKU  || count($this->_importMsg->getGroupsList()) < 1 ) {
                return;
            }
            $this->storeLastDumpRef('groups');
            return;
        }
        $groupsMsg = &$this->retrieveDumpByRef('groups');
        $handler = new Schracklive_SchrackCatalog_Model_Protoimport_GroupsHandler($this->_originTimestamp);
        if ( $groupsMsg ) {
            self::log('handling now categories:');
            $handler->handle($groupsMsg);
            $this->removeDumpRef('groups');
            self::log('categories done.');
        }
        $this->_groupsIdMap = $handler->getSchrack2MagentoIdMap();
        $this->_oldCategoryMap = $handler->getLoadedOldCategoryMap();
    }

    private function handleArticles () {
        $handler = new Schracklive_SchrackCatalog_Model_Protoimport_ArticlesHandler($this->_originTimestamp);
        if ( $this->_doArticles ) {
            self::log('handling now products:');
            if ( $this->_DO_ONLY_THAT_SKU ) {
                $handler->setDoOnlyThatSku($this->_DO_ONLY_THAT_SKU);
            }
            $handler->handle($this->_importMsg);
            self::log('products done.');
        }
        $this->_articleIdMap = $handler->getSchrack2MagentoIdMap();
    }
    
    private function handleRelations ( $finalize = false ) {
        if ( ! $finalize ) {
            if ( ! $this->_doRelations || count($this->_importMsg->getArticlegrouprefsList()) < 100 ) {
                // we are just interested in the package containing all references (what is much more than 100)...
                return;
            }
            $this->storeLastDumpRef('relations');
            return;
        }
        $relationsMsg = &$this->retrieveDumpByRef('relations');
        if ( $relationsMsg ) {
            self::log('handling now relations:');
            $handler = new Schracklive_SchrackCatalog_Model_Protoimport_RelationsHandler($this->_groupsIdMap, $this->_articleIdMap, $this->_originTimestamp);
            if ( $this->_DO_ONLY_THAT_SKU ) {
                $handler->setDoOnlyThatSku($this->_DO_ONLY_THAT_SKU);
            }
            $handler->handle($relationsMsg);
            $this->removeDumpRef('relations');
            self::log('relations done.');
        }
    }

    private function handleTranslations () {
        if ( ! $this->_doTranslations ) {
            return;
        }
        self::log('handling now translations:');
        $handler = new Schracklive_SchrackCatalog_Model_Protoimport_TranslationsHandler($this->_originTimestamp);
        $handler->handle($this->_importMsg);
        self::log('translations done.');
    }
    
    private function handleSynonyms () {
        if ( ! $this->_doSynonyms ) {
            return;
        }
        self::log('handling now synonyms:');
        $handler = new Schracklive_SchrackCatalog_Model_Protoimport_SynonymsHandler($this->_originTimestamp);
        $handler->handle($this->_importMsg);
        self::log('synonyms done.');
    }

    private function handleDictionary () {
        if ( ! $this->_doDictionary ) {
            return;
        }
        self::log('handling now dictionary:');
        $handler = new Schracklive_SchrackCatalog_Model_Protoimport_DictionaryHandler($this->_originTimestamp);
        $handler->handle($this->_importMsg);
        self::log('dictionary done.');
    }

    private function commit () {
        $this->checkDumpFilePath('groups');
        $this->checkDumpFilePath('relations');
        if ( $this->_DO_ONLY_THAT_SKU ) {
            return;
        }
        $rewriter = null;
        $rewriter = new Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler();
        $rewriter->saveOldStuff(); // TODO: remove that old behaviour after 3 months or something of running the new stuff
        $this->handleGroups($this->_finalize);
        $this->handleRelations($this->_finalize);
        $this->_lastRetrievedDumpMsg = null;
        $rewriter->setLoadedOldCategoryMap($this->_oldCategoryMap);
        $rewriter->createRewriteMetadata();
        gc_collect_cycles();
        $this->handleReindexing();
        if ( $this->_doRewrites ) {
            $rewriter->addOldPathsAsPermanentRedirects();
        }
        gc_collect_cycles();
        $this->removeEmptyCategories();
        $this->updateMegaMenu();

        // Sending Finished SOLR Mail:
        Mage::helper('schrack/email')->sendDeveloperMail('Starting SOLR-Export for','Starting SOLR Export');

        $this->handleSolrExport();
        gc_collect_cycles();

        // Sending Finished SOLR Mail:
        Mage::helper('schrack/email')->sendDeveloperMail('Finished SOLR-Export for','SOLR Export FINISHED');

        self::log('cleaning local cache...');
		Mage::app()->getCacheInstance()->flush();
		Mage::app()->cleanCache();

        self::log('cleaning remote cache...');
        $url = Mage::helper('schrack/backend')->getFrontendUrl('sd/Cache/flush');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        curl_close($ch);
        if ( ! $response ) {
            self::log("!!! ERROR: remote flush failed !!!");
        } else {
            self::log("success.");
        }

        $this->handleCacheWarmup();

        self::log('done');
    }
    
    private function handleReindexing () {
        if ( ! $this->_doReindexing ) {
            return;
        }
        self::log('handling now reindexing:');
        $codes = array(
            'catalog_product_attribute',
//            'catalog_product_price',
            'catalog_url',
            'catalog_category_flat',
//            'catalog_category_product',
//            'catalogsearch_fulltext',
            'cataloginventory_stock'
         );

        /*
        $code = 'catalogsearch_fulltext';
        self::log('    index: ' . $code);
        $this->disableDead();
        try {
            $process = Mage::getSingleton('index/indexer')->getProcessByCode(trim($code));
            $process->reindexEverything();
        } catch ( Exception $ex ) {
            $this->enableDead();
            throw $ex;
        }
        $this->enableDead();
        */

        foreach ($codes as $code) {
            self::log('    index: ' . $code);
            try {
                if ( $code == 'catalog_url' ) {
                    $this->_writeConnection->query("TRUNCATE core_url_rewrite");
                }
                $process = Mage::getSingleton('index/indexer')->getProcessByCode(trim($code));
                $process->reindexEverything();

            } catch ( Exception $ex ) {
                Mage::logException($ex);
                self::log("    ERROR: index $code failed with exception: " . $ex->getMessage());
            }
        }

        // alternative re-creation of catalog_category_product_index:
        self::log('    index: alternate catalog_category_product');
        try {
            $sql = "DELETE FROM catalog_category_product_index;";
            $this->_writeConnection->query($sql);
            $sql = " INSERT INTO catalog_category_product_index (category_id, product_id, position, is_parent, store_id, visibility)"
                 . " SELECT category_id, product_id, position, 1, ?, 4 FROM catalog_category_product;";
            $this->_writeConnection->query($sql,array($this->_storeId));
            $sql = " SELECT ccp.category_id, ccp.product_id, cat.path FROM catalog_category_product ccp"
                 . " JOIN catalog_category_entity cat ON cat.entity_id = ccp.category_id;";
            $res = $this->_readConnection->fetchAll($sql);
            foreach ( $res as $row ) {
                $categoryID = $row['category_id'];
                $productID = $row['product_id'];
                $parentCatIDs = substr($row['path'],2);
                $parentCatIDs = substr($parentCatIDs,0,strlen($parentCatIDs) - (strlen($categoryID) + 1));
                $parentCatIDs = str_replace('/',',',$parentCatIDs);
                $position = $productID;
                $sql = " INSERT INTO catalog_category_product_index (category_id, product_id, position, is_parent, store_id, visibility)"
                     . " SELECT entity_id, ?, ?, 0, ?, 4 FROM catalog_category_entity WHERE entity_id in ($parentCatIDs) AND entity_id NOT IN (SELECT category_id FROM catalog_category_product_index WHERE category_id = entity_id AND product_id = ?);";
                $this->_writeConnection->query($sql,array($productID,$position,$this->_storeId,$productID ));
            }
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            self::log("    ERROR: alternate catalog_category_product failed with exception: " . $ex->getMessage());
        }

        self::log('reindexing done.');
    }

    private function disableDead () {
        self::log('disable dead and strategic_no');
        $sql = "UPDATE catalog_product_entity_int attr JOIN catalog_product_entity AS prod ON prod.entity_id = attr.entity_id"
             . " SET value = 2 WHERE prod.schrack_sts_statuslocal IN ('tot','strategic_no','unsaleable');";
        $this->_writeConnection->query($sql);
    }

    private function enableDead () {
        self::log('ensable dead and strategic_no');
        $sql = "UPDATE catalog_product_entity_int attr JOIN catalog_product_entity AS prod ON prod.entity_id = attr.entity_id"
            . " SET value = 1 WHERE prod.schrack_sts_statuslocal IN ('tot','strategic_no','unsaleable');";
        $this->_writeConnection->query($sql);
    }

    private function removeEmptyCategories () {
        if ( ! $this->_doRemoveEmptyCats ) {
            return;
        }
        self::log('removing empty categories');
        $sql = " DELETE cat FROM catalog_category_entity cat"
             . " LEFT JOIN catalog_category_product_index ccpi ON cat.entity_id = ccpi.category_id"
             . " LEFT JOIN catalog_category_entity_varchar attrID ON ("
             . "   cat.entity_id = attrID.entity_id AND attrID.attribute_id IN ("
             . "     SELECT attribute_id FROM eav_attribute WHERE entity_type_id = ("
             . "       SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_category'"
             . "     ) AND attribute_code = 'schrack_group_id'"
             . "   )"
             . " )"
             . " WHERE ccpi.product_id IS NULL"
             . " AND cat.entity_id > 2"
             . " AND attrID.value <> '_PROMOS_'";
        $this->_writeConnection->query($sql);
    }

    private function handleSolrExport () {
        if ( ! $this->_doSolrExport ) {
            return;
        }
        self::log('handling now solr export:');
        $baseDir = Mage::getBaseDir('base');
        if ( $this->isWindowsOS() ) {
            $workDir = $baseDir . '\shell\local';
            $cmd = 'php "' .$workDir . '\exportProducts.php" all --mode full';
        } else {
            $workDir = $baseDir . '/shell/local';
            $cmd = 'php ' . $workDir . '/exportProducts.php all --mode full';
            
            // using semaphore:
            $fn = $this->getSemaphoreFileName();
            $fp = fopen($fn,'a');
            fclose($fp);
            $no = ftok($fn,'I');
            $sem = sem_get($no);
            self::log('acquire semaphore...');
            sem_acquire($sem);
        }
        chdir($workDir);
        self::log('launching now external process: ' . $cmd);
        $output = array();
        $result = -1;
        exec($cmd,$output,$result);
        if ( ! $this->isWindowsOS() ) {
            self::log('release semaphore...');
            sem_release($sem);
        }
        foreach ( $output as $line ) {
            self::log('> ' . $line);
        }
        self::log('result was: ' . $result);
        self::log('solr export done.');
    }

    private function handleCacheWarmup () {
        $x = Mage::getStoreConfig('schrack/product_cache_warmup/enable_warmup');
        if ( ! intval($x) || ! $this->_doWarmupCache ) {
            return;
        }
        self::log('starting product cache warmup...');
        $script = Mage::getBaseDir('base') . DS . 'shell' . DS . 'local' . DS . 'WarmupProductCache.php';
        $country = strtolower(Schracklive_SchrackCatalog_Model_Protoimport::getCountryCode());
        $cmdLine = sprintf("php %s < /dev/null > /tmp/product_cache_warmup_%s.out 2>&1 &",$script,$country);
        exec($cmdLine);
        self::log('...started in background.');
    }
  
    public function getSemaphoreFileName () {
        return $this->_semaphoreFileName;
    }
    
    public function setSemaphoreFileName ( $fn ) {
        $this->_semaphoreFileName = $fn;
    }
    
    private function isWindowsOS () {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
    
    public static function getCountryCode () {
        $countryCode = Mage::getStoreConfig('general/country/default');
        if ( $countryCode === 'DK' ) {
            $countryCode = 'CO'; // UGLY!!!! Should be removed ASAP!!!!!
        }
        return $countryCode;
    }

    /*
     * Updates the mega menu in browser cache (localstorage) :
     */
    private function updateMegaMenu() {
        if ( ! $this->_doMegaMenuUpdate ) {
            return;
        }

        $this->createMegaMenuContent();

        // Just write now-timestamp to core_config_data:
        $latestRefreshDatetime = date('Y-m-d H:i:s');

        $queryString  = "UPDATE core_config_data SET value = '" . $latestRefreshDatetime . "'";
        $queryString .= " WHERE path LIKE 'schrack/performance/mega_menu_latest_refresh_datetime'";

        $this->_writeConnection->query($queryString);
        self::log("Megamenu timestamp updated to $latestRefreshDatetime");

        // tell typo3 the new timestamp:
        $url = Mage::getStoreConfig('schrack/typo3/typo3url') . Mage::getStoreConfig('schrack/typo3/clearmegamenuurl');
        $url .= '&menuTs=';
        $url .= strtotime($latestRefreshDatetime);

        self::log("updating mega menu ts in typo: $url");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        curl_close($ch);
        if ( ! $response ) {
            self::log("!!! ERROR: typo call failed !!!");
        } else {
            self::log("success.");
        }
    }

    private function createMegaMenuContent() {
        $webserviceUrlMenuApiPath = Mage::getStoreConfig('schrack/general/generatemenuserviceurl');
        $secret = Mage::getStoreConfig('schrack/api/session_prefix');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $webserviceUrlMenuApiPath,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS =>"{\"user\":\"shop\",\"secret\":\"$secret\",\"function\":\"fetchCompleteMenuHTML\"}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        if( curl_errno($curl) ) {
            $curlErrorMsg = curl_error($curl);
            curl_close($curl);
            Mage::log('ERROR: ' . $curlErrorMsg, null, 'menubuilder.generate.log');
        } else {
            curl_close($curl);
            Mage::log($response, null, 'menubuilder.generate.log');
        }
    }

    private function storeLastDumpRef ( $name ) {
        $filePath = $this->getRefPath($name);
        self::log("storing reference to $name into file '$filePath'");
        file_put_contents($filePath,$this->_lastDumpFilePath);
    }

    private function &retrieveDumpByRef ( $name ) {
        $refFilePath = $this->getRefPath($name);
        $dumpFilePath = trim(file_get_contents($refFilePath));
        if ( $dumpFilePath == $this->_lastDumpFilePath && $this->_lastRetrievedDumpMsg != null ) {
            // parsed message already there
            self::log("reusing already parsed msg from '$dumpFilePath'");
            return $this->_lastRetrievedDumpMsg;
        }
        // read from FS:
        if ( file_exists($dumpFilePath) ) {
            $this->_lastDumpFilePath = $dumpFilePath;
            $binData = file_get_contents($dumpFilePath);
            if ( ! $binData ) {
                throw new Exception("Could not read file '$dumpFilePath'");
            }
            self::log("prasing now $name data from file '$dumpFilePath'");
            $this->_lastRetrievedDumpMsg = new Message($binData);
        } else {
            $this->_lastRetrievedDumpMsg = null;
        }
        return $this->_lastRetrievedDumpMsg;
    }

    private function removeDumpRef ( $name ) {
        // currently no remove to ease a re-finalize...
        /*
        $filePath = $this->getRefPath($name);
        unlink($filePath);
        */
    }

}

?>

<?php

require_once 'shell.php';

class Schracklive_Shell_WarmupProductCache extends Schracklive_Shell {
    var $_readConnection = null;

    function __construct() {
        parent::__construct();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

	public function run () {
        $backendHelper = Mage::helper('schrack/backend');

        $deadLineInSeconds = $this->getTimeInSecondsHMS(5,0,0);
        $x = $this->getConfigTimeInSeconds('schrack/product_cache_warmup/finish_warmup_time');
        if (  $x !== false ) {
            $deadLineInSeconds = $x;
        }
        $pauseSecBetweenProducts = 1;
        $x = Mage::getStoreConfig('schrack/product_cache_warmup/request_delay');
        if ( $x ) {
            $pauseSecBetweenProducts = intval($x);
        }

        self::custom_print("querying product vievs...");
        $sql = " SELECT COUNT(event_id) AS views, object_id AS entity_id FROM report_event"
             . " WHERE event_type_id = 1"
             . " GROUP BY entity_id"
             . " HAVING COUNT(event_id) > 2"
             . " ORDER BY views DESC";
        $results = $this->_readConnection->fetchAll($sql);
        $cnt = count($results);
        $i = 1;
        self::custom_print('...done.');
        $ch = curl_init();
        foreach ( $results as $row ) {
            $entityID = $row['entity_id'];
            $views = $row['views'];
            self::custom_print("Warmig up now $i/$cnt: $entityID with $views views:");
            $sql = "SELECT request_path FROM core_url_rewrite WHERE product_id = ?";
            $urls = $this->_readConnection->fetchRow($sql,$entityID);
            foreach ( $urls as $url ) {
                $fullUrl = $backendHelper->getFrontendUrl($url);
                self::custom_print("    " . $fullUrl . " ... ",true,false);
                //Set the URL that you want to GET by using the CURLOPT_URL option.
                curl_setopt($ch, CURLOPT_URL, $fullUrl);
                //Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                //Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                // set user agent
                curl_setopt($ch, CURLOPT_USERAGENT, "Product Detail Cache Warmup");
                //Execute the request.
                $data = curl_exec($ch);
                if ( curl_errno($ch) ) {
                    self::custom_print("FAILED with error " . curl_errno($ch) . ": " . curl_error($ch), false, true);
                } else {
                    self::custom_print('ok, ' . strlen($data) . ' chars got.',false,true);
                }
            }
            ++$i;
            sleep($pauseSecBetweenProducts);
            if ( $this->getCurrentTimeInSeconds() >= $deadLineInSeconds ) {
                break;
            }
        }
        //Close the cURL handle.
        curl_close($ch);
	    self::custom_print("done.");
    }
}

$instance = new Schracklive_Shell_WarmupProductCache();
$instance->run();

<?php
require_once 'shell.php';

/*
 * loginState: 1 = Eingeloggt und Ausgeloggt
 * loginState: 2 = Eingeloggt
 * loginState: 3 = Ausgeloggt
 *
 * accountType: 1 = Alle Kundentypen
 * accountType: 2 = Vollkunde
 * accountType: 3 = Interessent
 *
 * type: 1 = Text
 * type: 2 = Aktion
 * type: 3 = Angebot
 */

class Schracklive_Shell_FetchTypoMessageBars extends Schracklive_Shell {
    private $_typoserviceurl;
    private $_error;

    // Default settings : should be all "false" by default:
    private $_testing;
    private $_logcurl ;
    private $_activateFetch;
    private $_localTestingExampleUrl;


    public function __construct () {
        parent::__construct();
        $this->_error                  = 0;
        $this->_logcurl                = intval(Mage::getStoreConfig('schrack/typo3/message_bars_logging'));
        $this->_activateFetch          = intval(Mage::getStoreConfig('schrack/typo3/message_bars_fetch_active'));
        $this->_testing                = intval(Mage::getStoreConfig('schrack/typo3/message_bars_testing'));
        $this->_localTestingExampleUrl = 'https://test-ba.schrack.com/?id=1130&m=getMessageBarMessages';
        $typo_url                      = Mage::getStoreConfig('schrack/typo3/typo3url');
        $typo_page_id_suffix           = Mage::getStoreConfig('schrack/typo3/message_bars_service_url');

        if ($this->_testing == 1) {
            $this->_typoserviceurl = $this->_localTestingExampleUrl;
            Mage::log('typoserviceurl = ' . $this->_typoserviceurl, null, "message_bars.test.log", false, false);
        } else {
            $this->_typoserviceurl = $typo_url . $typo_page_id_suffix;
        }
    }


    public function run() {
        if ($this->_typoserviceurl && $this->_activateFetch == 1) {
            // Try to use cURL to fetch the content:
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->_typoserviceurl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            curl_close($ch);

            if ( ! $response ) {
                $this->_error = 2;
                Mage::log('Curl Error #1', null, "message_bars_cron.err.log");
            } else {
                // Init DB-Access:
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                // Truncate table with old data:
                $tableCleanupQuery = "TRUNCATE schrack_message_bars";
                $writeConnection->query($tableCleanupQuery);

                $responseAsArray = json_decode($response, true);
                if ($responseAsArray && is_array($responseAsArray)) {
                    if ($this->_logcurl == true) {
                        Mage::log($responseAsArray, null, "message_bars_cron_response.log", false, false);
                    }
                    foreach($responseAsArray as $index => $messageBarData) {
                        // Insert or Update all messageBar related data into database:
                        $pid = $messageBarData['pid'];
                        $accountType = $messageBarData['accountType'];
                        $body = base64_encode($messageBarData['body']);
                        $branchId = $messageBarData['branchId'];
                        if (!$branchId) {
                            $branchId = 'null';
                        }
                        $campaignName = $messageBarData['campaignName'];
                        $loginState   = $messageBarData['loginState'];
                        $type         = $messageBarData['type'];
                        $link         = $messageBarData['link'];
                        $linkText     = $messageBarData['linkText'];
                        $active       = 1; // TODO : this could be a condition of different rules!!
                        $uid          = $messageBarData['uid'];


                        $query  = "INSERT INTO schrack_message_bars SET";
                        $query .= " uid = " . $uid . ",";
                        $query .= " pid = " . $pid . ",";
                        $query .= " accountType = " . $accountType . ",";
                        $query .= " body = '" . $body . "',";
                        $query .= " branchId = " . $branchId . ",";
                        $query .= " campaignName = '" . $campaignName . "',";
                        $query .= " loginState = " . $loginState . ",";
                        $query .= " type = " . $type . ",";
                        $query .= " link = '" . $link . "',";
                        $query .= " linkText = '" . $linkText . "',";
                        $query .= " active = " . $active . ",";
                        $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";
                        $query .= " ON DUPLICATE KEY UPDATE";
                        $query .= " pid = " . $pid . ",";
                        $query .= " accountType = " . $accountType . ",";
                        $query .= " body = '" . $body . "',";
                        $query .= " branchId = " . $branchId . ",";
                        $query .= " campaignName = '" . $campaignName . "',";
                        $query .= " loginState = " . $loginState . ",";
                        $query .= " type = " . $type . ",";
                        $query .= " link = '" . $link . "',";
                        $query .= " linkText = '" . $linkText . "',";
                        $query .= " active = " . $active . ",";
                        $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";

                        if ($this->_logcurl == true) {
                            Mage::log($query, null, "message_bars_cron_response.log", false, false);
                        }

                        $writeConnection->query($query);
                    }
                }
            }
        }
    }
}

$shell = new Schracklive_Shell_FetchTypoMessageBars();
$shell->run();

<?php
require_once 'shell.php';

class Schracklive_Shell_FetchSchrackTermsOfUse extends Schracklive_Shell {
    private $_typoserviceurl;
    private $_error;

    // Default settings : should be all "false" by default:
    private $_localtesting           = false;
    private $_forceCurl              = true; // infrastructure related setting -> only curl does the trick (file_get_contents() failed)!!
    private $_logcurl                = false;
    private $_localTestingExampleUrl = 'https://test-de.schrack.com/?id=169564';

    public function __construct () {
        parent::__construct();
        $this->_error = 0;
        $typo_url = Mage::getStoreConfig('schrack/typo3/typo3url');
        $typo_page_id_suffix = Mage::getStoreConfig('schrack/typo3/terms_of_use_service_url');
        if ($this->_localtesting == true) {
            $this->_typoserviceurl = $this->_localTestingExampleUrl;
            Mage::log('typoserviceurl = ' . $this->_typoserviceurl, null, "terms-of-use-test.log", false, false);
            require_once '../../lib/HtmlFetch/simple_html_dom.php';
        } else {
            $this->_typoserviceurl = $typo_url . $typo_page_id_suffix;
            $country = substr(strtolower(Mage::getStoreConfig('schrack/general/country')), 0, 2);
            require_once '/var/www/html/'. $country .'/htdocs/lib/HtmlFetch/simple_html_dom.php';
        }
    }

    public function run() {
        $currentTermsOfUseHtml    = "";
        $oldTermsOfUseHTML        = '';
        $oldTermsOfUseHTMLBase64  = '';
        $schrackTermsOfUsePage    = '';

        if ($this->_typoserviceurl && intval(Mage::getStoreConfig('schrack/dsgvo/activateUserTermsFeature')) == 1) {
            if ($this->_forceCurl == false) {
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );

                $schrackTermsOfUsePage = file_get_html($this->_typoserviceurl, false, stream_context_create($arrContextOptions));

                // Find all div tags with class=mainContRightSec (there is only one on every typo page)
                if ($schrackTermsOfUsePage) {
                    foreach($schrackTermsOfUsePage->find('div.mainContRightSec') as $e) {
                        $currentTermsOfUseHtml = $e->outertext;
                    }
                } else {
                    Mage::log('Something went wrong: fetching service of terms of use', null, "terms_of_use_cron.err.log");
                    Mage::log('typoserviceurl = ' . $this->_typoserviceurl, null, "terms_of_use_cron.err.log", false, false);
                    Mage::log('$schrackTermsOfUsePage is empty', null, "terms_of_use_cron.err.log");
                    Mage::log('==========================================================', null, "terms_of_use_cron.err.log");
                    $this->_error = 1;
                }

                if ($this->_error == 1) {
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
                        Mage::log('Curl Error #1', null, "terms_of_use_cron.err.log");
                        Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.err.log");
                    } else {
                        if ($this->_logcurl == true) {
                            Mage::log($response, null, "terms_of_use_cron_response_by_curl.log", false, false);
                            Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron_response_by_curl.log", false, false);
                        }
                        $schrackTermsOfUsePage = get_html_input($response);
                        if ($schrackTermsOfUsePage) {
                            foreach($schrackTermsOfUsePage->find('div.mainContRightSec') as $e) {
                                $currentTermsOfUseHtml = $e->outertext;
                                if ($this->_logcurl == true) {
                                    Mage::log($currentTermsOfUseHtml, null, "terms_of_use_cron_response_by_curl_content_only.log", false, false);
                                }
                            }
                        }
                    }
                }
            } else {
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
                    Mage::log('Curl Error #2', null, "terms_of_use_cron.err.log");
                    Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.err.log");
                } else {
                    if ($this->_logcurl == true) {
                        Mage::log($response, null, "terms_of_use_cron_response_by_curl.log", false, false);
                        Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron_response_by_curl.log", false, false);
                    }
                    $schrackTermsOfUsePage = get_html_input($response);
                    if ($schrackTermsOfUsePage) {
                        foreach($schrackTermsOfUsePage->find('div.mainContRightSec') as $e) {
                            $currentTermsOfUseHtml = $e->outertext;
                            if ($this->_logcurl == true) {
                                Mage::log($currentTermsOfUseHtml, null, "terms_of_use_cron_response_by_curl_content_only.log", false, false);
                                Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron_response_by_curl_content_only.log", false, false);
                            }
                            $currentTermsOfUseWithoutTags = strip_tags($e->outertext);
                        }
                    }
                }
            }

            if ($this->_error == 0) {
                if ($currentTermsOfUseHtml == "") {
                    // Something went wrong with fetching HTML!
                    Mage::log('No HTML Found in fetching service of terms of use', null, "terms_of_use_cron.err.log");
                    Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.err.log");
                    Mage::log('==========================================================', null, "terms_of_use_cron.err.log");
                } else {
                    Mage::log('User terms successfully fetched', null, "terms_of_use_cron.log");
                    Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.log");

                    $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                    // Get raw content string and hash of HTML from database from the latest version of the terms of use
                    $query = "SELECT * FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
                    $result = $readConnection->fetchAll($query);
                    foreach ($result as $recordset) {
                        $oldTermsOfUseWitoutTagsBase64 = $recordset['content_without_html_tags'];
                        $oldTermsOfUseHTMLContentHash  = $recordset['content_hash'];
                    }

                    if ($oldTermsOfUseHTMLContentHash) {
                        // Compare to current version string of the terms of use:
                        $oldTermsOfUseWithoutTags = base64_decode($oldTermsOfUseWitoutTagsBase64);
                        if (strcmp($oldTermsOfUseWithoutTags, $currentTermsOfUseWithoutTags) !== 0) {
                            $rawContentChanged = 1;
                            //$rawContentChanged = 0; // --> Only needed for initial load of new comparison arithmetic!

                            if ($rawContentChanged == 1) {
                                // Current Terms has been changed in the meantime - so let's insert a new version and do an all-customers-reset:
                                $query  = "INSERT INTO schrack_terms_of_use";
                                $query .= " SET content = '" . base64_encode($currentTermsOfUseHtml) . "',";
                                $query .= " content_without_html_tags = '" . base64_encode($currentTermsOfUseWithoutTags) . "',";
                                $query .= " content_hash = '" . hash ( 'sha256', $currentTermsOfUseHtml) . "',";
                                $query .= " content_hash_without_html_tags = '" . hash ( 'sha256', $currentTermsOfUseWithoutTags) . "',";
                                $query .= " raw_content_changed = " . $rawContentChanged . ",";
                                $query .= " created_at = '" . date('Y-m-d H:i:s') . "'";
                            } else {
                                // Simply updates some database columns (manual use):
                                $query  = "UPDATE schrack_terms_of_use";
                                $query .= " SET content_without_html_tags = '" . base64_encode($currentTermsOfUseWithoutTags) . "',";
                                $query .= " content_hash_without_html_tags = '" . hash ( 'sha256', $currentTermsOfUseWithoutTags) . "',";
                                $query .= " raw_content_changed = " . $rawContentChanged;
                                $query .= " WHERE content_hash LIKE '" . $oldTermsOfUseHTMLContentHash . "'";
                            }

                            //Mage::log($query, null, 'query.log');
                            $writeConnection->query($query);

                            if ($rawContentChanged == 1) {
                                // Set all users to "non-confirmed" (invalidation of all users):
                                $query  = "UPDATE customer_entity SET schrack_last_terms_confirmed = 0";
                                $writeConnection->query($query);

                                // Informing the S4S mobile-app, that a new (updated) version of terms of use is there:
                                Mage::helper('s4s')->newTermsOfUseProvided();
                                Mage::log('User terms successfully updated', null, "terms_of_use_cron.log");
                                Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.log");
                            }
                        } else {
                            Mage::log('no change of user terms detected', null, "terms_of_use_cron.log");
                            Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.log");
                        }
                    } else {
                        // Initial load (only needed for the first entry of user terms):
                        $rawContentChanged = 1;

                        // Insert first entry into database:
                        $query  = "INSERT INTO schrack_terms_of_use";
                        $query .= " SET content = '" . base64_encode($currentTermsOfUseHtml) . "',";
                        $query .= " content_without_html_tags = '" . base64_encode($currentTermsOfUseWithoutTags) . "',";
                        $query .= " content_hash = '" . hash ( 'sha256', $currentTermsOfUseHtml) . "',";
                        $query .= " content_hash_without_html_tags = '" . hash ( 'sha256', $currentTermsOfUseWithoutTags) . "',";
                        $query .= " raw_content_changed = " . $rawContentChanged . ",";
                        $query .= " created_at = '" . date('Y-m-d H:i:s') . "'";
                        $writeConnection->query($query);

                        Mage::log('Initial user terms successfully inserted', null, "terms_of_use_cron.log");
                        Mage::log('TYPO-Service-URL : ' . $this->_typoserviceurl, null, "terms_of_use_cron.log");
                    }
                }
            }
        }
    }
}

$shell = new Schracklive_Shell_FetchSchrackTermsOfUse();
$shell->run();

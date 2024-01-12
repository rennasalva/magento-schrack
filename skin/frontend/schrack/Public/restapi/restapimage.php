<?php

// This script must be called as webservice:
// e.g.:
// curl https://test-si.schrack.com/trgovina/skin/frontend/schrack/Public/restapi/restapi.php

require_once '../../../../../shell/local/shell.php';
require_once 'menu/menubuilder.php';

class Schracklive_Shell_Restapimage extends Schracklive_Shell {

    private $_versionTimestamp;

    public function __construct () {
        parent::__construct();
        // TODO
    }

    public function run () {
        $this->_versionTimestamp = date('Y-m-d_H:i:s');
        $response = $this->errorResponse('something undefined went wrong');
        $apiAutoLoginSessionPrefix = Mage::getStoreConfig('schrack/api/session_prefix');
        $inputJSONStringBase64Encoded = $this->getArg('params');
        $inputJSONString = base64_decode($inputJSONStringBase64Encoded);

        $requestBodyAsArray = json_decode($inputJSONString, true);
        if (is_array($requestBodyAsArray) && !empty($requestBodyAsArray)) {
            if (isset($requestBodyAsArray['user'])) {
                if ($requestBodyAsArray['user'] != 'shop') {
                    Mage::log('Version = ' . $this->_versionTimestamp . ' --> wrong user', null, 'restapi.failed.log');
                    return json_encode($this->errorResponse('wrong user'));
                }
            } else {
                Mage::log('Version = ' . $this->_versionTimestamp . ' --> missing user parameter', null, 'restapi.failed.log');
                return json_encode($this->errorResponse('missing user parameter'));
            }

            if (isset($requestBodyAsArray['secret'])) {
                if ($requestBodyAsArray['secret'] != $apiAutoLoginSessionPrefix) {
                    Mage::log('Version = ' . $this->_versionTimestamp . ' --> wrong secret', null, 'restapi.failed.log');
                    return json_encode($this->errorResponse('wrong secret'));
                }
            } else {
                Mage::log('Version = ' . $this->_versionTimestamp . ' --> missing secret parameter', null, 'restapi.failed.log');
                return json_encode($this->errorResponse('missing secret parameter'));
            }

            if (isset($requestBodyAsArray['function'])) {
                if ($requestBodyAsArray['function'] == 'fetchCompleteMenuHTML') {
                    $menubuilder = new Menubuilder(); // Init menubuilder

                    // Fetch typo content via webservice:
                    $typoResponseAsJSON = null; // Init variable
                    try {
                        // TODO: Hardcoded URL from WebService, in case of: have only TEST-BA configured for webservice:
                        //Example: $servicePath = 'https://test-ba.schrack.com/contentEID=export_pages&type=all';
                        $servicePath = Mage::getStoreConfig('schrack/typo3/typo3_fetch_menu_partial_url');
                        $typoResponseAsJSON = file_get_contents($servicePath);
                    } catch (Mage_Core_Exception $err) {
                        Mage::log($err->getMessage(), null, 'restapi.failed.log');
                    }

                    if ($typoResponseAsJSON == null) {
                        // TYPO have no response:
                        Mage::log('Response from TYPO is null', null, 'restapi.failed.log');
                        // Try to get an older menu of typo, if typo failed to deliver successful response:
                        $typoResponseAsArray = $menubuilder->loadPreviousTypoMenu();
                    } else {
                        $menubuilder->persistJsonFromTypo($typoResponseAsJSON, $this->_versionTimestamp);
                        $typoResponseAsArray = json_decode($typoResponseAsJSON, true);
                    }

                    $menubuilder->setVersion($this->_versionTimestamp);

                    if (is_array($typoResponseAsArray) && empty($typoResponseAsArray)) {
                        $msgTypoBuild = 'typo build failed';
                    } else {
                        $msgTypoBuild = 'typo build successful';
                    }

                    // Do building all HTML and save to database as base64:
                    $result = $menubuilder->persistMenuStructure($typoResponseAsArray, $this->_versionTimestamp);
                    if ($result == true) {
                        $msgShopBuild = 'shop build successful';
                    } else {
                        $msgShopBuild = 'shop build failed';
                    }
                    $response = $this->successResponse('Shop = ' . $msgShopBuild . '  TYPO = ' . $msgTypoBuild);

                } else {
                    Mage::log('Version = ' . $this->_versionTimestamp . ' --> function is invalid', null, 'restapi.failed.log');
                    return json_encode($this->errorResponse('missing function'));
                }
            } else {
                Mage::log('Version = ' . $this->_versionTimestamp . ' --> missing function parameter', null, 'restapi.failed.log');
                return json_encode($this->errorResponse('missing function parameter'));
            }
        } else {
            Mage::log('Version = ' . $this->_versionTimestamp . ' --> cannot create array from JSON', null, 'restapi.failed.log');
            return json_encode($this->errorResponse('cannot create array from JSON'));
        }

        Mage::log('Success', null, 'restapi.success.log');
        return json_encode($response);
    }


    private function errorResponse ($message) {
        return array(
            'result' => 'error',
            'message' => $message,
            'current_build' => $this->_versionTimestamp,
            'menu_html' => ''
        );
    }


    private function successResponse ($message) {
        return array(
            'result' => 'success',
            'message' => $message,
            'current_build' => $this->_versionTimestamp
        );
    }
}

$shell = new Schracklive_Shell_Restapimage();
$menu_as_json = $shell->run();
echo $menu_as_json;
die();
<?php

require_once "CommonToolsController.php";

class Schracklive_Tools_EasylanConfiguratorController extends Schracklive_Tools_CommonToolsController {

    const LOGFILE = 'easylan_integration.log';
    const INTERNAL_DEFAULT_EMAIL = 'f.bogacz@schrack.com';

    static $locale_to_iso_639_2_map = array(
        'ar_SA' => 'eng',
        'en_GB' => 'eng',
        'en_US' => 'eng',
        'de_AT' => 'deu',
        'de_DE' => 'deu',
        'bg_BG' => 'bul',
        'bs_BA' => 'bos',
        'cs_CZ' => 'cze',
        'hr_HR' => 'hrv',
        'hu_HU' => 'hun',
        'nl_NL' => 'dut',
        'pl_PL' => 'pol',
        'ro_RO' => 'ron',
        'ru_RU' => 'rus',
        'sk_SK' => 'slk',
        'sl_SI' => 'slv',
        'sr_RS' => 'srp'
    );

    public function indexAction () {
        if ( Mage::getStoreConfig('schrack/customertools/enable_easylan_configurator') != '1' ) {
            self::log("indexAction: easylan disabled");
            $this->norouteAction();
            return;
        }
        if ( $this->ensureLogin() ) {
            self::log("indexAction: requesting login");
            return;
        }
        $deuUrlUrl = Mage::getStoreConfig('schrack/customertools/easylan_configurator_get_iframe_url');
        $secretKey = Mage::getStoreConfig('schrack/customertools/easylan_configurator_secret_key');
        $locale = Mage::app()->getLocale()->getLocaleCode();
        if ( $deuUrlUrl && $deuUrlUrl > '' ) {
            self::log("START GetIntegrationIframe call");
            self::log("using URL: $deuUrlUrl");
            try {
                $params = [];
                $params['secretKey']        = $secretKey;
                $params['sessionId']        = $this->getAndRememberSessionId();
                $params['configuratorId']   = 10;
                $params['includeInIframe']  = true;
                $params['setLanguageId']    = self::$locale_to_iso_639_2_map[$locale];
                $json = json_encode($params);
                self::logJson("post body data is",$json);

                $headers = [
                    "Content-type: text/json",
                    "Content-length: " . strlen($json),
                    "Connection: close"
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $deuUrlUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if ( curl_errno($ch) ) {
                    throw new Exception(curl_error($ch));
                } else {
                    curl_close($ch);
                }
                self::logJson("got result is",$result);
                $resultPhp = json_decode($result);
                $status = isset($resultPhp) && isset($resultPhp->status) ? $resultPhp->status : 0;
                if ( $status != 200 ) {
                    $ms = isset($resultPhp) && isset($resultPhp->statusMessage)
                        ? $resultPhp->statusMessage
                        : 'Unknown error, no error message got';
                    throw new Exception($ms);
                }
                $iframeUrl = isset($resultPhp) && isset($resultPhp->iframeUrl) ? $resultPhp->iframeUrl : '';
                if ( $uID = $this->getRequest()->getParam('UniqueID') ) {
                    if ( strpos($iframeUrl,'?') !== false ) {
                        $iframeUrl .= '&';
                    } else {
                        $iframeUrl .= '?';
                    }
                    $iframeUrl .= ('UniqueID=' . $uID);
                }
                Mage::register('easylan_iframe_url', $iframeUrl);
            } catch ( Exception $ex ) {
                self::log("Error while retrieving IFrame url (see 'exception.log' as well): " . $ex->getMessage());
                Mage::logException($ex);
                $this->_redirect('/');
            }
            self::log("END GetIntegrationIframe call");
        } else {
            self::log("no URL defined");
            $this->norouteAction();
            return;
        }

        self::log("indexAction: rendering now page...");
        $this->loadLayout();
        $this->constructBreadCrumbs('Easylan Configurator');
        $this->renderLayout();
    }

    public function pullCustomerDataAction () {
        self::log("START pullCustomerDataAction()");
        if ( ! $this->getRequest()->isPost() ) {
            self::log("END pullCustomerDataAction() => not a post request");
            $this->_redirect('/');
            return;
        }
        $jsonRequest = $this->getRequest()->getRawBody();
        self::logJson('REQUEST pullCustomerData',$jsonRequest);
        $requestData = $this->parseRequestData($jsonRequest);
        if ( ! isset($requestData->sessionId) || $requestData->sessionId <= '' ){
            $this->returnError("Missing mandatory field 'sessionId'!");
        }
        $customer = $this->getCustomerForSessionId($requestData->sessionId);
        if ( ! $customer ) {
            $this->returnError("Invalid or outdated sessionId!");
        }
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $country = substr($locale,3);

        $res = array(
            'uidhash'           => $requestData->sessionId,
            'country'           => $country,
            'customerNumber'    => '15554',
            'customerNumberExt' => $requestData->sessionId
        );
        $this->returnSuccess($res);
    }

    public function pushOrderDataAction () {
        self::log("START pushOrderDataAction()");
        if ( ! $this->getRequest()->isPost() ) {
            self::log("END pushOrderDataAction() => not a post request");
            $this->_redirect('/');
            return;
        }
        $jsonRequest = $this->getRequest()->getRawBody();
        self::logJson('REQUEST pushOrderData',$jsonRequest);
        $requestData = $this->parseRequestData($jsonRequest);
        if ( ! isset($requestData->CustomerData->CustomerNumberExt) || $requestData->CustomerData->CustomerNumberExt <= '' ){
            $msg = "Missing mandatory field 'CustomerNumberExt'!";
            self::log("ERROR: $msg");
            $this->returnError($msg);
        }
        try {
            $customer = $this->getCustomerForSessionId($requestData->CustomerData->CustomerNumberExt);
            self::log("handling now customer email...");
            $this->sendCustomerMail($customer, $requestData);
            self::log("handling now internal email...");
            $this->sendInternalMail($customer, $requestData, $jsonRequest);
            self::log("...emails done");
            $customerEndMessages = $this->__('You will then receive an order confirmation via e-mail, or a representative will personally contact you.');
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            self::log("ERROR occured: " . $ex->getMessage());
            $customerEndMessages = $this->__('There was an error processing your order. Please contact us.');
        }
        $this->returnSuccess(array('confirmationHTML' => $customerEndMessages));
    }

    private function sendInternalMail ( $customer, $requestData, $jsonRequest ) {
        $toAddress = Mage::getStoreConfig('schrack/customertools/easylan_configurator_schrack_employee_email_to');
        if ( ! $toAddress || ($toAddress = trim($toAddress))  == '' ) {
            $msg = "Missing TO address in backend configuration!";
            self::log("ERROR: $msg");
            throw new Exception($msg);
        }
        $ccAddress = Mage::getStoreConfig('schrack/customertools/easylan_configurator_schrack_employee_email_cc');
        /** @var Schracklive_SchrackCustomer_Helper_Data $customerHelper */
        $customerHelper = Mage::helper('customer');
        $toArray = $ccArray = null;
        try {
            $toArray = $customerHelper->replaceEmailAddressPlaceholdersAndExpand($toAddress,$customer,null,self::LOGFILE);
            $ccArray = $customerHelper->replaceEmailAddressPlaceholdersAndExpand($ccAddress,$customer,null,self::LOGFILE);
        } catch ( Exception $ex ) {
            self::log("Error resolving email addresses: " . $ex->getMessage());
            if ( ! is_array($toArray) || count($toArray) == 0 ) {
                self::log("Using default '" . self::INTERNAL_DEFAULT_EMAIL . "' for TO");
                $toArray = [ self::INTERNAL_DEFAULT_EMAIL ];
            } else if ( ! is_array($ccArray) || count($ccArray) == 0 ) {
                self::log("Using default '" . self::INTERNAL_DEFAULT_EMAIL . "' for CC");
                $ccArray = [ self::INTERNAL_DEFAULT_EMAIL ];
            }
        }
        $orderTableHtml = $this->createOrderTableHtml($requestData,true);
        $templateVars = array(
            'customer' => $customer,
            'account' => $customer->getAccount(),
            'oderdata' => $requestData,
            'ordertable' => $orderTableHtml
        );
        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $configPath = 'schrack/customertools/easylan_configurator_internal_email_template_number';
        $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($configPath);
        $singleMailApi->setMagentoTransactionalTemplateVariables($templateVars);
        $singleMailApi->setToEmailAddresses($toArray);
        if ( $ccArray ) {
            $singleMailApi->setCcEmailAddresses($ccArray);
        }
        $singleMailApi->setFromEmail('general');
        // $singleMailApi->addAttachement('easylan_order.json',$jsonRequest);
        $singleMailApi->createAndSendMail();
    }

    private function sendCustomerMail ( $customer, $requestData ) {
        $orderTableHtml = $this->createOrderTableHtml($requestData,false);
        $configPath = 'schrack/customertools/easylan_configurator_customer_email_template_number';
        $templateVars = array(
            'customer' => $customer,
            'oderdata' => $requestData,
            'ordertable' => $orderTableHtml
        );
        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($configPath);
        $singleMailApi->setMagentoTransactionalTemplateVariables($templateVars);
        $singleMailApi->addToEmailAddress($customer->getEmail());
        $singleMailApi->setFromEmail('general');
        $singleMailApi->createAndSendMail();
    }

    private function createOrderTableHtml ( $requestData, $isSchrackInternal ) {
        if ( $isSchrackInternal ) {
            $fields = array(
                // "OrderPositionID",
                // "OrderID",
                "Amount",
                "Title",
                // "UniqueID",
                // "ConfiguratorID",
                "UnitPrice",
                "UnitPriceReseller",
                "TotalPrice",
                "TotalPriceReseller",
                "Currency",
                "ExchangeRate",
                "UnitPriceEuro",
                "TotalPriceEuro"
            );
        } else {
            $fields = array(
                "Amount",
                "Title",
                "UnitPrice",
                "TotalPrice"
            );
        }
        $configurationFileds = array(
            // "FeatureID",
            "PlugA",
            "PlugB",
            "FanOut",
            "Head",
            "FiberCount",
            // "auto",
            "CableLength",
            // "Length",
            "Overhead",
            "CableLengthWithoutOverhead",
            "FiberType",
            "CableType",
            // "LengthCategory",
            "LamEtikettL",
            "LamEtikettR"
        );
        $html = <<<TABSTART
<style>
.ordertable {
  border-collapse: collapse;
}
.ordertable td {
  border: 1px solid black;
}
</style>
<table class="ordertable">
TABSTART;
        $totalPriceSum = 0.0;
        $totalPriceResellerSum = 0.0;
        $labels = array_merge($fields,$configurationFileds);
        $posCount = 0;
        $posTxt = $this->__('Pos.');
        foreach ( $requestData->OrderPositions as $orderPosition ) {
            ++$posCount;
            $html .= "<tr style=\"background: lightgray\"><td>$posTxt $posCount</td><td></td><td></td></tr>\n";
            foreach ( $labels as $label ) {
                $isConfig = ! isset($orderPosition->$label);
                $val = ! $isConfig ? $orderPosition->$label : $orderPosition->Configuration->$label;
                if ( is_object($val) ) {
                    if ( implode('', (array) $val) == '' ) {
                        $val = '';
                    } else {
                        $val = implode('<br/>', (array)$val);
                    }
                } else {
                    if ( $label == 'Currency' || $label == 'ExchangeRate' || $label == 'UnitPriceEuro' || $label == 'TotalPriceEuro' ) {
                        continue; // if no value, skip field
                    }
                }
                if ( $label == 'TotalPrice' ) {
                    $totalPriceSum += floatval($val);
                } else if ( $label == 'TotalPriceReseller' ) {
                    $totalPriceResellerSum += floatval($val);
                }

                $labelTranslated = $this->__($label);
                $tdVal = $this->buildTdVal($label,$val);
                if ( $isConfig ) {
                    $html .= "<tr><td></td><td>$labelTranslated:</td>$tdVal";
                } else {
                    $html .= "<tr><td></td><td>$labelTranslated:</td>$tdVal";
                }
                $html .= "</tr>\n";
            }
        }
        $totalPriceSum = Mage::app()->getStore()->formatPrice($totalPriceSum);
        $label = $this->__('Overall Sum') . ':';
        $html .= "<tr style=\"background: lightgray; font-weight: bold\"><td>$label</td><td></td><td style=\"text-align: right\">$totalPriceSum</td></tr>\n";
        if ( $isSchrackInternal ) {
            $label = $this->__('Overall Reseller Sum') . ':';
            $totalPriceResellerSum = Mage::app()->getStore()->formatPrice($totalPriceResellerSum);
            $html .= "<tr style=\"background: lightgray; font-weight: bold\"><td>$label</td><td></td><td style=\"text-align: right\">$totalPriceResellerSum</td></tr>\n";
        }
        $html .= "</table>";
        return $html;
    }

    const ALIGN_ALL_VALUES_RIGHT = true;

    private static $RIGHT_ALIGN_LABELS = [
        'Amount'                        => true,
        'UnitPrice'                     => true,
        'UnitPriceReseller'             => true,
        'TotalPrice'                    => true,
        'TotalPriceReseller'            => true,
        'FiberCount'                    => true,
        'CableLength'                   => true,
        'Length'                        => true,
        'Overhead'                      => true,
        'CableLengthWithoutOverhead'    => true
    ];

    private static $MONEY_LABELS = [
        'UnitPrice'                     => true,
        'UnitPriceReseller'             => true,
        'TotalPrice'                    => true,
        'TotalPriceReseller'            => true
    ];


    private function buildTdVal ( $label, $val ) {
        if ( isset(self::$MONEY_LABELS[$label]) ) {
            $val = Mage::app()->getStore()->formatPrice($val);
        }
        if ( self::ALIGN_ALL_VALUES_RIGHT || isset(self::$RIGHT_ALIGN_LABELS[$label]) ) {
            return '<td style="text-align: right; width: 150px"> ' . $val . ' </td>';
        }
        return "<td>$val</td>";
    }

    private function parseRequestData ( $jsonRequest ) {
        $requestData = json_decode($jsonRequest);
        if ( ! $requestData ) {
            $msg = "Error parsing Json request: " . json_last_error_msg();
            self::log($msg);
            throw new Exception($msg);
        }
        return $requestData;
    }

    private function getCustomerForSessionId ( $sessionId ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT customer_id FROM schrack_easylan_session WHERE session_id =?";
        $customerId = $readConnection->fetchOne($sql,$sessionId);
        if ( ! $customerId ) {
            self::log("No customer stored for session $sessionId.");
            return false;
        }
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if ( ! $customer->getEmail() ) {
            self::log("Customer with ID $customerId can not be loaded!");
            return false;
        }
        self::log("Customer for session $sessionId is {$customer->getEmail()}");
        return $customer;
    }

    private function getAndRememberSessionId () {
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        // delete all records older than 1 day:
        $oneDayAgoTimestamp = date('Y-m-d H:i:s',time() - (24 * 60 * 60));
        $sql = "DELETE FROM schrack_easylan_session WHERE last_started_at < ?";
        $writeConnection->query($sql,$oneDayAgoTimestamp);
        // insert or update record
        $session = Mage::getSingleton('core/session');
        $SID = $session->getEncryptedSessionId(); //current session id
        $currentTimestamp = date('Y-m-d H:i:s');
        $customerId = $this->getCustomer()->getId();
        $sql = "REPLACE INTO schrack_easylan_session (customer_id,session_id,last_started_at) VALUES(:cust,:sess,:ts)";
        $writeConnection->query($sql,array('cust' => $customerId, 'sess' => $SID, 'ts' =>  $currentTimestamp));
        return $SID;
    }

    private function returnError ( $msg ) {
        $res = array('status' => 400, 'statusMessage' => $msg);
        $this->returnJson($res);
    }

    private function returnSuccess ( array $data ) {
        $data['status'] = 200;
        $data['statusMessage'] = "OK";
        $this->returnJson($data);
    }

    private function returnJson ( array $data ) {
        header('Content-Type: application/json',true);
        header('Accept: application/json',true);
        header('Allow: POST',true);
        $json = json_encode($data);
        $this->writeResponse($json);
        self::logJson('RESPONSE',$json);
        die();
    }

    private function writeResponse ( $jsonResponse ) {
        echo $jsonResponse;
    }

    private static function log ( $msg ) {
        // TODO: make controllable in backend...
        Mage::log($msg,null,self::LOGFILE);
    }

    private static function logJson ( $headLIne, $jsonString ) {
        // TODO: make controllable in backend...
        $jsonString = json_encode(json_decode($jsonString), JSON_PRETTY_PRINT);
        self::log("$headLIne:\n$jsonString\n");
    }


    public function testTableAction () {
        $json = <<<JSON
{
    "OrderID": "595fd948-7f11-4aeb-a921-d70389058d14",
    "UniqueID": "O-T806750",
    "CreateDate": "2020-02-28T14:32:48.74",
    "Remark": "",
    "CustomerData": {
        "UserIDhash": "jl90hvav5oeaooff8pe58jrci4",
        "CreateDate": "2020-02-28T14:32:48.74",
        "Firstname": "Schrack Technik GmbH",
        "Lastname": "Schrack Technik GmbH",
        "Company": "Schrack Technik GmbH",
        "Street": "Seybelgasse",
        "StreetNo": "13",
        "ZIP": "A-1230",
        "City": "Wien",
        "Country": "\u00d6sterreich",
        "CountryID": 14,
        "CountryISO3166Alpha2": "AT",
        "CountryISO3166Alpha3": "AUT",
        "Phone": "+43 1 866 85 5900",
        "EMail": "info@schrack.at",
        "CustomerNumber": "15554",
        "CustomerNumberExt": "jl90hvav5oeaooff8pe58jrci4"
    },
    "OrderPositions": [
        {
            "OrderPositionID": "771138b0-b0ce-43ec-b6a3-c6e0ead1c0ae",
            "OrderID": "595fd948-7f11-4aeb-a921-d70389058d14",
            "Amount": 1,
            "Title": "I412031",
            "UniqueID": "I412031",
            "ConfiguratorID": 10,
            "UnitPrice": 683.1429,
            "UnitPriceReseller": 341.5775,
            "TotalPrice": 683.1429,
            "TotalPriceReseller": 341.5715,
            "Configuration": {
                "ConfiguratorID": 10,
                "FeatureID": "9dd02902-306f-45da-b5a2-55fd5b033213",
                "PlugA": "E2000\/APC",
                "PlugB": "E2000\/APC",
                "FanOut": "0.9",
                "Head": "FODH1",
                "FiberCount": 8,
                "auto": 1,
                "CableLength": 388.5,
                "Length": "388,5 m",
                "Overhead": 2.5,
                "CableLengthWithoutOverhead": 386,
                "FiberType": "OS2",
                "CableType": "Universalkabel",
                "LengthCategory": ">=100",
                "LamEtikettL": {
                    "Zeile1": "",
                    "Zeile2": "",
                    "Zeile3": ""
                },
                "LamEtikettR": {
                    "Zeile1": "",
                    "Zeile2": "",
                    "Zeile3": ""
                }
            }
        },
        {
            "OrderPositionID": "93020a3b-441b-4151-8410-1c29160e8a58",
            "OrderID": "595fd948-7f11-4aeb-a921-d70389058d14",
            "Amount": 1,
            "Title": "M567231",
            "UniqueID": "M567231",
            "ConfiguratorID": 10,
            "UnitPrice": 439.2728,
            "UnitPriceReseller": 219.6364,
            "TotalPrice": 439.2728,
            "TotalPriceReseller": 219.6364,
            "Configuration": {
                "ConfiguratorID": 10,
                "FeatureID": "f314293e-3a87-467d-9a1c-58de55331420",
                "PlugA": "E2000\/APC",
                "PlugB": "E2000\/APC",
                "FanOut": "0.9",
                "Head": "FODH1",
                "FiberCount": 4,
                "auto": 1,
                "CableLength": 261.25,
                "Length": "261,25 m",
                "Overhead": 2.25,
                "CableLengthWithoutOverhead": 259,
                "FiberType": "OS2",
                "CableType": "Universalkabel",
                "LengthCategory": ">=100",
                "LamEtikettL": {
                    "Zeile1": "oans",
                    "Zeile2": "zwoa",
                    "Zeile3": "drei"
                },
                "LamEtikettR": {
                    "Zeile1": "",
                    "Zeile2": "",
                    "Zeile3": ""
                }
            }
        }
    ]
}
JSON;
        $requestData = json_decode($json);
        $orderTableHtml = $this->createOrderTableHtml($requestData,false);
        $orderTableResellerHtml = $this->createOrderTableHtml($requestData,true);
        die("<body>\n<div>START:</div>\n$orderTableHtml\n<div>RESELLER:</div>\n$orderTableResellerHtml\n<div>:END</div>\n</body>\n");
    }
}


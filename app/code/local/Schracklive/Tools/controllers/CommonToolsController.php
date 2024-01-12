<?php

class Schracklive_Tools_CommonToolsController extends Mage_Core_Controller_Front_Action {

    public function getAdditionalArticleDataAction () {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $requestData = $this->getRequest()->getPost();
            if ( ! is_array($requestData) || count($requestData) == 0 || ! isset($requestData["skus"]) ) {
                $skus = array();
            } else {
                $skus = $requestData["skus"];
            }
            $this->getArticleDataImpl($skus);
        }
    }

    public function getAccessoriesForSkuAction () {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $requestData = $this->getRequest()->getPost();
            $accessorySKUs = array();
            if ( is_array($requestData) && count($requestData) > 0 && isset($requestData["sku"]) ) {
                $sku = $requestData['sku'];
                $product = Mage::getModel('catalog/product')->loadBySku($sku);
                if ( $product->getId() ) {
                    $accessorySKUs = $product->getAccessorySKUs();
                }
            }
            $this->getArticleDataImpl($accessorySKUs);
        }
    }

    public function renderBlockAction () {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $requestData = $this->getRequest()->getPost();
            if ( is_array($requestData) && count($requestData) > 0 && isset($requestData["template_name"]) ) {
                $templatePath = 'tools/' . $requestData['template_name'] . '.phtml';
                $html = $this->renderHtml('tools/html', $requestData['template_name'], $templatePath);
                echo $html;
            }
        }
    }

    public function createPdfAction () {
        if ( $this->getRequest()->isPost() ) {
            $data = $this->getRequest()->getPost();
            try {
                $xml = $this->buildXml($data);
                $pdf = $this->getPdf($xml,$data['stylesheet']);
                if ( strtoupper(substr($pdf,0,4)) != '%PDF' ) {
                    Mage::log($pdf,null,'pdferror.log');
                    throw new Exception('Not a PDF file got!');
                }
                $link = $this->savePdf($pdf);
                die($link);
            } catch ( Exception $ex ) {
                Mage::logException($ex);
                die('ERROR: ' . $ex->getMessage());
            }
        }
    }

    public function deletePdfAction () {
        if ( $this->getRequest()->isPost() ) {
            $data = $this->getRequest()->getPost();
            $link = $data['link'];
            $fileName = substr($link,strrpos($link,'/') + 1);
            $baseDir = Mage::getBaseDir('base');
            $downloadsDir = $baseDir . DS . 'downloads';
            $filePath = $downloadsDir . DS . $fileName;
            unlink($filePath);
        }
    }

    private function getPdf ( $xml, $stylesheet ) {
        $url = Mage::getStoreConfig('schrack/customertools/pdf_generation_url');
        $headers = array(
            "Content-type: text/xml",
            "Content-length: " . strlen($xml),
            "Connection: close",
            "xslt: $stylesheet"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($ch);
        if( curl_errno($ch) ) {
            throw new Exception(curl_error($ch));
        } else {
            curl_close($ch);
            return $data;
        }
    }

    private function savePdf ( $pdf ) {
        $baseDir = Mage::getBaseDir('base');
        $downloadsDir = $baseDir . DS . 'downloads';
        if ( ! is_dir($downloadsDir) ) {
            mkdir($downloadsDir);
        }
        $fileName = 'tools_project_documentation_' . md5($this->getCustomer()->getEmail()) . '_' . time() . '.pdf';
        $filePath = $downloadsDir . DS . $fileName;
        file_put_contents($filePath,$pdf);
        $baseUrl = Mage::getBaseUrl();
        $url = $baseUrl . 'downloads/' . $fileName;
        return $url;
    }

    private function buildXml ( $data ) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        self::addChild($xml,'webshop', Mage::getBaseUrl());
        if ( $this->getSession()->isLoggedIn() ) {
            $customer = $this->getCustomer();
            $account = $customer->getAccount();
            self::addChild($xml,'customerName', $account ? $account->getName(true) : '');
            self::addChild($xml,'customerStreet', $account ? $account->getStreet1() : '');
            self::addChild($xml,'customerZip', $account ? $account->getPostcode() : '');
            self::addChild($xml,'customerCity', $account ? $account->getPostcode() : '');
            self::addChild($xml,'contactFirstName', $customer->getFirstname());
            self::addChild($xml,'contactLastName', $customer->getLastname());
            if ( ($phone = $customer->getSchrackTelephone()) && $phone > '' ) {
                self::addChild($xml,'contactPhone', $phone);
            } else {
                if ( ($phone = $customer->getSchrackMobilePhone()) && $phone > '' ) {
                    self::addChild($xml,'contactPhone', $phone);
                } else {
                    self::addChild($xml,'contactPhone', '');
                }
            }
            self::addChild($xml,'contactEmail', $customer->getEmail());
        } else {
            self::addChild($xml,'customerName','');
            self::addChild($xml,'customerStreet', '');
            self::addChild($xml,'customerZip', '');
            self::addChild($xml,'customerCity', '');
            self::addChild($xml,'contactFirstName', '');
            self::addChild($xml,'contactLastName', '');
            self::addChild($xml,'contactPhone', '');
            self::addChild($xml,'contactEmail', '');
        }

        $xml = self::arrayToXml($xml,$data);

        $articles = self::addChild($xml,'articles');
        foreach ( $data['articles'] as $v ) {
            $article = $articles->addChild('article');
            self::arrayToXml($article,$v);
        }
        $hints = self::addChild($xml,'hints');
        $groupFlags = array();
        foreach ( $data['hints'] as $hintKey => $hint ) {
            if ( ! is_array($hint) ) {
                continue;
            }
            $groupName = $hint['group'];
            if ( ! isset($groupFlags[$groupName]) ) {
                $groupFlags[$groupName] = true;
                $group = $hints->addChild('group');
                $group->addAttribute('name',$groupName);
            }
            $paragraph = $group->addChild('paragraph');
            $paragraph->addChild('name',$hint['name']);
            $paragraph->addChild('note',$hint['note']);
        }

        $translations = self::addChild($xml,'translations');
        self::addTranslation($translations,'Projektadresse',$this->__('Project Address'));
        self::addTranslation($translations,'Notiz',$this->__('Note'));
        self::addTranslation($translations,'Beschreibung',$this->__('Description'));
        self::addTranslation($translations,'Hinweise:',$this->__('Hints') . ':');
        self::addTranslation($translations,'Stückliste:',$this->__('Parts List') . ':');
        self::addTranslation($translations,'Artikelnummer',$this->__('SKU'));
        self::addTranslation($translations,'Artikelbezeichnung',$this->__('Product Text'));
        self::addTranslation($translations,'Menge',$this->__('Quantity'));
        self::addTranslation($translations,'Online erstellt am',$this->__('Created online on'));
        self::addTranslation($translations,'Änderungen und Irrtümer vorbehalten',$this->__('Changes and errors excepted'));
        self::addTranslation($translations,'Seite',$this->__('Page'));
        self::addTranslation($translations,'von',$this->__('from'));

        self::addTranslation($translations,'Rechtliche Hinweise',$this->__('Legal Notice'));
        $prefix = isset($data['legalNotesKeyPrefix']) ? $data['legalNotesKeyPrefix'] : '';
        self::addTranslation($translations,'rh_paragraph_1',$this->__($prefix . 'ln_paragraph_1'));
        self::addTranslation($translations,'rh_paragraph_2',$this->__($prefix . 'ln_paragraph_2'));
        self::addTranslation($translations,'rh_paragraph_3',$this->__($prefix . 'ln_paragraph_3'));
        self::addTranslation($translations,'rh_paragraph_4',$this->__($prefix . 'ln_paragraph_4'));
        self::addTranslation($translations,'rh_paragraph_5',$this->__($prefix . 'ln_paragraph_5'));

        $res = $xml->asXML();
        if ( Mage::getStoreConfig('schrackdev/tools_documentation/log') == '1' ) {
            $user = $this->getSession()->isLoggedIn() ? $this->getCustomer()->getEmail() : '(anonymous)';
            $log = @fopen(Mage::getBaseDir('var').DS.'log'.DS.'schracklive_http_client_tools_download_request.log','a');
            @fwrite($log,chr(10) . date('Y-m-d H:i:s UTC', time()));
            @fwrite($log,chr(10) . 'User: ' . $user . chr(10));
            @fwrite($log, chr(10) . $res);
			@fclose($log);
        }
        $res = str_replace('&ltltlt;','<',$res);
        $res = str_replace('&gtgtgt;','>',$res);
        $res = str_replace('<br>','<br/>',$res);
        $res = str_replace('</br>','',$res);
        return $res;
    }

    private static function addTranslation ( $xml, $key, $value ) {
        $elem = self::addChild($xml,'text',$value);
        $elem->addAttribute('key',self::escapeSpecialChars($key));
    }

    private static function addChild ( $xml, $key, $value ) {
        $escape = self::escapeSpecialChars($value);
        if ( $key == 'text' ) {
            // renaming those escapes to replace then back to '<' and '>' for translations only
            // to keep html tags like <a href="...">yyy</a>
            $escape = str_replace('&lt;','&ltltlt;',$escape);
            $escape = str_replace('&gt;','&gtgtgt;',$escape);
        }
        return $xml->addChild($key,$escape);
    }

    private static function escapeSpecialChars ( $text ) {
        $res = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, "utf-8");
        return $res;
    }

    private static function arrayToXml ( $xml, $array ) {
        foreach ( $array as $k => $v ) {
            if ( ! is_array($v) ) {
                self::addChild($xml,$k, $v);
            }
        }
        return $xml;
    }

    protected function constructBreadCrumbs ( $untranslatedLastBreadcrumbLabel ) {
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home"),
            "title" => $this->__("Home"),
            "link"  => Mage::getBaseUrl()
       ));
        $breadcrumbs->addCrumb("onlinetools", array(
            "label" => $this->__("Online Tools"),
            "title" => $this->__("Online Tools"),
            "link"  => Mage::getBaseUrl() . 'onlinetools'
       ));
        $breadcrumbs->addCrumb("currenttool", array(
            "label" => $this->__($untranslatedLastBreadcrumbLabel),
            "title" => $this->__($untranslatedLastBreadcrumbLabel)
       ));
    }

    protected function getSession () {
        return Mage::getSingleton('customer/session');
    }

    protected function getCustomer () {
        return $this->getSession()->getCustomer();
    }

    private function getArticleDataImpl ( array $skus ) {
        if ( count($skus) == 0 ) {
            echo '{}';
        } else {
            $helper = Mage::helper('tools/articles');
            $res = $helper->getAdditionalArticleData($skus);
            echo json_encode($res);
        }
        die();
    }

    private function renderHtml ( $blockType, $blockName = '', $template = null, $args = array() ) {
        $block = $this->getLayout()->createBlock($blockType,$blockName);
        if ( $template ) {
            $block->setTemplate($template);
        }
        $block->addData($args);
        $html = $block->toHtml();
        return $html;
    }

    public function getTranslationsAction() {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $requestData = $this->getRequest()->getPost();

            if (is_array($requestData) && !empty($requestData)) {
                $resultJSON = array();
                foreach($requestData as $index => $translationKey) {
                    $resultJSON[$translationKey] = $this->__($translationKey);
                }
            } else {
                $resultJSON = array('error' => 'no translations found or wrong format or empty request');
            }

            echo json_encode($resultJSON);
            die();
        }
    }

    protected function ensureLogin () {
        if ( ! $this->getSession()->isLoggedIn() ) {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $referer = Mage::helper('core')->urlEncode($currentUrl);
            $loginUrl = Mage::getUrl('customer/account/login', array('referer' => $referer));
            $this->_redirectUrl($loginUrl);
            return true;
        }
        return false;
    }

    public function getProductPricesAction() {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $data = $this->getRequest()->getPost();
            $skuData = $data['skuList'];

            if (!is_array($skuData)) {
                Mage::log('SKU-List is not an array : "' . $skuData . '"', null, 'get_product_prices_skus_error.log');
                die('no sku given');
            } else {
                foreach($skuData as $key) {
                    $skus[] = $key;
                }
            }

            $productHelper = Mage::helper('schrackcatalog/product');
            $resultJSON = $productHelper->getPriceProductInfo($skus);

            echo json_encode($resultJSON);
            die();
        }
    }


    public function addressValidationAction() {
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $data = $this->getRequest()->getPost();

            // Mage::log($data, null,'addressData.log');
            $status = 'INVALID';
            if (Mage::getStoreConfig('schrack/general/addressvalidation_active')."" === "1") {
                $params['APIKey']        = Mage::getStoreConfig('schrack/general/addressvalidation_password')."";
                $params['StreetAddress'] = htmlspecialchars($data['adressdata']['street'],ENT_HTML5);
                $params['City']          = urlencode($data['adressdata']['city']);
                $params['PostalCode']    = urlencode($data['adressdata']['postcode']);
                $params['CountryCode']   = urlencode($data['adressdata']['country']);

                //  PHP_QUERY_RFC3986 makes space -> %20 otherwise default value is +
                $getParams = http_build_query($params, '', '&',PHP_QUERY_RFC3986);
                $url = Mage::getStoreConfig('schrack/general/addressvalidation_url') . $getParams;
                Mage::log($url, null, "address_service_request.log", false, false);
                Mage::log($params, null, "address_service_request.log", false, false);
                Mage::log($data, null, "address_service_request.log", false, false);

                $countryCreditsDach = array('AT', 'DE', 'CH'); // 2.5
                if (in_array($data['adressdata']['country'], $countryCreditsDach)) {
                    $credit = 2.5;
                } else {
                    $credit = 5.0;
                }

                $monthByNumber = date('m') . '.' . date('Y');
                if ($data['adressdata']['street'] && $data['adressdata']['postcode'] && $data['adressdata']['city']) {
                    $address = $data['adressdata']['street'] . "/" . $data['adressdata']['postcode'] . "/" . $data['adressdata']['city'];
                } else {
                    $address = '';
                }

                if ($address != '') {
                    // Check, if address already exists:
                    $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $select = "SELECT `result` FROM schrack_adddress_validation WHERE address LIKE ?";
                    $queryResult = $readConnection->fetchOne($select, $address);
                    if ($queryResult) {
                        if ($queryResult == 'SUCCESS') {
                            $status = 'SUCCESS';
                        }
                    } else {
                        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $proxyUrlForCurl = "http://172.22.4.250:8080";

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        curl_setopt($ch, CURLOPT_PROXY, $proxyUrlForCurl);

                        $response = curl_exec($ch);
                        if( curl_errno($ch) ) {
                            Mage::log(date('Y-m-d H:i:s') . ' [ERROR] CURL FAILED with error ' . curl_errno($ch) . ': ' . curl_error($ch), null, '/address_service_error.log');
                        } else {
                            $parsedResponse = json_decode($response, true);
                            Mage::log($parsedResponse, null, "address_service_response.log", false, false);
                            if (is_array($parsedResponse) && isset($parsedResponse['status'])) {
                                //  If status 'VALID' or 'SUSPECT' -> status 'VALID'
                                if ($parsedResponse['status'] == 'VALID' || $parsedResponse['status'] == 'SUSPECT') {
                                    $status = 'SUCCESS';
                                }
                            }
                            $query  = "INSERT INTO schrack_adddress_validation SET";
                            $query .= " created_at = '" . date("Y-m-d H:i:s") . "',";
                            $query .= " address = :addressConcat, country = :countryCode, credit = :requestCost,";
                            $query .= "  current_month = :monthNumber, result = :status";
                            $binds = array(
                                'countryCode'   => $data['adressdata']['country'],
                                'requestCost'   => $credit,
                                'monthNumber'   => $monthByNumber,
                                'addressConcat' => $address,
                                'status'        => $status
                            );

                            $result = $writeConnection->query($query, $binds);
                        }
                        curl_close($ch);
                    }
                }
            } else {
                // Always return address validation success, if module is deactivated:
                $status = 'SUCCESS';
            }

            echo json_encode(array('result' => $status));
            die();
        }
    }

}

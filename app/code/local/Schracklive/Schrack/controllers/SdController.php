<?php

// require_once 'Mage/Customer/controllers/AccountController.php';
class Schracklive_Schrack_SdController extends Mage_Core_Controller_Front_Action {

    private $_logAll = false;

    public function indexAction () {
        try {
            $request = $this->getRequest();
            if ( $this->tryArticles($request) ) {
                return;
            }
            if ( $this->tryCategories($request) ) {
                return;
            }
            $this->tryOrders($request);
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $this->_redirect("/");
        }
    }

    private function tryArticles ($request) {
        // HINT: Rule for all types: If you are logged in, you will see the article without any message
        // Type 'a' means: show article in default manner --> no login needed!
        $type = 'a';
        $params = $request->getParams();
        if (is_array($params) && !empty($params)) {
            foreach($params as $key => $value) {
                if ($key == 'ah') {
                    $type = 'ah';
                }
                if ($key == 'al') {
                    $type = 'al';
                }
            }
        }
        $sku = $params[$type];
        if ($this->_logAll == true) {
            Mage::log($params, null, 'short_urls.log');
            Mage::log($sku, null, 'short_urls.log');
        }

        if (!isset($sku)) {
            // Type 'al' means: show article in default manner with heading information banner (lower prices) --> login available here (link to login popup)!
            $type = 'ah';
        }
        if (isset($sku)) {
            // Hack for compensating mistake in PM:
            if ( $params['focusDownloads'] == 1 ) {
                switch ( $sku ) {
                    case 'BZT18D011A' : $sku = 'BZT27A011-'; break;
                    case 'BZT18D011W' : $sku = 'BZT18D011A'; break;
                    case 'BZT18D012W' : $sku = 'BZT18D011W'; break;
                    case 'BZT27A011-' : $sku = 'BZT18D012W'; break;
                }
            }
            // end of hack
            $session = Mage::getSingleton('customer/session');
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku, array());
            if ( !$product ) {
                Mage::log("Product not found = " . $sku, null, 'short_urls.log');
                return false;
            }
            switch ( $type ) {
                case 'ah' :
                    if ( !$session->isLoggedIn() ) {
                        Mage::getSingleton("core/session", array("name"=>"frontend"))->setData('login_for_custom_prices_hint','1');
                    }
                    Mage::log("SKU = " . $sku, null, 'short_urls.log');
                    //$this->_redirectUrl( $this->addUrlParamsExcept($product->getProductUrlWithChapterIfAvail(), $params, $type) );
                    //break;
                case 'a' :
                    if ($this->_logAll == true) {
                        Mage::log("Case = a -> SKU = " . $sku, null, 'short_urls.log');
                        Mage::log("Get SKU by Product -> " . $product->getSku() . $sku, null, 'short_urls.log');
                        Mage::log("URL = " . $this->addUrlParamsExcept($product->getProductUrlWithChapterIfAvail(), $params, $type) . $product->getSku() . $sku, null, 'short_urls.log');
                    }
                    $this->_redirectUrl( $this->addUrlParamsExcept($product->getProductUrlWithChapterIfAvail(), $params, $type) );
                    break;
                case 'al' :
                    if ( $session->isLoggedIn() ) {
                        $this->_redirectUrl($product->getProductUrlWithChapterIfAvail());
                    } else {
                        $referer = Mage::helper('core')->urlEncode($this->addUrlParamsExcept($product->getProductUrlWithChapterIfAvail(),$params,$type));
                        $this->_redirect('customer/account/login',array('referer' => $referer));
                    }
            }
            return true;
        } else {
            return false;
        }
    }

    private function addUrlParamsExcept ($url, $params, $except = null) {
        $first = true;
        foreach ( $params as $key => $val ) {
            if ( $key != $except ) {
                if ( $first ) {
                    $url .= "?" . $key . "=" . $val;
                    $first = false;
                } else {
                    $url .= "&" . $key . "=" . $val;
                }
            }
        }
        return $url;
    }

    private function tryCategories ($request) {
        $no = $request->getParam('k');
        if ( isset($no) ) {
            $sql = " SELECT request_path FROM core_url_rewrite rw"
                 . " JOIN catalog_category_entity_varchar id ON rw.category_id = id.entity_id"
                 . " WHERE rw.product_id IS NULL"
                 . " AND id.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
                 . " AND (id.value = '$no' OR id.value LIKE '%#$no' OR id.value LIKE '%/$no')"
                 . " ORDER BY LENGTH(request_path) ASC;";
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $url = $readConnection->fetchOne($sql);
            if ( $url ) {
                $url = Mage::getBaseUrl() . $url;
                $this->_redirectUrl($url);
                return true;
            }
        }
        return false;
    }

    private function tryOrders ( $request ) {
        $type = 'o';
        $no = $request->getParam($type);
        $outType = 'offer';
        if ( ! isset($no) ) {
            $type = 's';
            $no = $request->getParam($type);
            $outType = 'shipment';
        }
        if ( ! isset($no) ) {
            $type = 'i';
            $no = $request->getParam($type);
            $outType = 'invoice';
        }
        if ( ! isset($no) ) {
            $type = 'c';
            $no = $request->getParam($type);
            $outType = 'creditmemo';
        }
        if ( ! isset($no) ) {
            return false;
        }
        $no = intval($no);
        if ( ! is_int($no) || $no < 1000 ) {
            throw new Exception("Invalid doctype or docnum got.");
        }
        $docId = $no;
        $helper = Mage::helper('schracksales/order');
        $orderId = $helper->getOrderNumberForDocNumber($docId,$outType);

        $redirectUrl = Mage::getUrl("customer/account/documentsDetailView",array('id' => $orderId, 'type' => $outType, 'documentId' => $docId));

        // adding url params for tracking
        $params = Mage::app()->getRequest()->getParams();
        if ( $params && count($params) > 0 ) {
            $first = true;
            foreach ( $params as $k => $v ) {
                if ( $first ) {
                    $redirectUrl .= '?';
                    $first = false;
                } else {
                    $redirectUrl .= '&';
                }
                $redirectUrl .= ($k . '=' . $v);
            }
        }

        Mage::app()->getFrontController()->getResponse()->setRedirect($redirectUrl);
        return true;
    }

}


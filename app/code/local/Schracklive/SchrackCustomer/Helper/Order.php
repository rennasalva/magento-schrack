<?php

class Schracklive_SchrackCustomer_Helper_Order extends Mage_Core_Helper_Abstract {

    private $_collection;
    private $_urls;
    

    public function __construct() {
        $this->_collection = null;
        $this->_urls = array();
    }

    public function searchOrders(Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParameters, $pager) {
        if ($this->_collection === null) {
            /**
             * @var Schracklive_SchrackSales_Helper_Order
             */
            $helper = Mage::helper('schracksales/order');
            $this->_collection = $helper->searchSalesOrders($searchParameters, null, -1, 'SchrackCustomer-Helper-Order.php');
            $pager->setCollection($this->_collection);
        }
        return $this->_collection;
    }

    public function getCollection() {
        return $this->_collection;
    }
    
    public function itemCanBeAddedToLists($item) {
        return ($this->getProductUrlFromItem($item) !== null);
    }
    
    public function productCanBeAddedToLists($product) {
        return ($this->getProductUrlFromProduct($product) !== null);
    }
    
    public function getProductUrlFromItem($item) {
        $sku = $item->getSku();
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku,'');
        if ( ! $product ) {
            return null;
        }
        return $this->getProductUrlFromProduct($product);
    }
    
    public function getProductUrlFromProduct($product) {
        $sku = $product->getSku();
        $status = $product->getSchrackStsStatuslocal();

        if (Mage::helper('sapoci')->isSapociCheckout()) {
            if ($status == 'strategic_no') {
                $status = 'std';
            }
        }

        if ( ! isset($this->_urls[$sku]) ) {
            if (    in_array($sku,array('TRANSPORT-','MANIPULAT-','VERPACKUNG'))
                 || in_array($status,array('tot','strategic_no','gesperrt','unsaleable')) ) {
                $this->_urls[$sku] = null;
            } else {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku,'');
                $this->_urls[$sku] = $product->getProductUrl(false);
            }
        }
        return $this->_urls[$sku];
    }

     // bloody dirty bitch:
    public function documentsDownloadAction() {
        try {
            $orderId = $this->getRequest()->getParam('id');
            $documentId = $this->getRequest()->getParam('documentId');
            $type = $this->getRequest()->getParam('type');
            $base64 = $this->getRequest()->getParam('rq');
            if ( (isset($orderId) && isset($documentId) && isset($type)) || isset($base64) ) {
                if ( isset($base64) ) {
                    $vals = explode("|",base64_decode($base64));
                    $orderId    = $vals[1];
                    $type       = $vals[2];
                    $documentId = $vals[3];
                    $document = Mage::helper('schracksales/order')->getDocument($orderId,$documentId,intval($type));
                } else {
                    $document = Mage::helper('schracksales/order')->getDocumentByIds($orderId, $documentId, $this->_getDocumentTypeFromTypeName($type));
                }
                if (!is_object($document) && is_array($document) && isset($document['error']) && !empty($document['error'])) {
                    // Temporary logging to evaluate error:
                    if ($type && $type == 'order') {
                        $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
                        $sql = "SELECT * FROM captcha_log WHERE type LIKE 'Missing AB on MDOC-Server' AND value LIKE '" . $orderId . "'";
                        $result = $readConnection->fetchAll($sql);
                        if (is_array($result) && empty($result)) {
                            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                            $query  = "INSERT INTO captcha_log SET ";
                            $query .= " type = 'Missing AB on MDOC-Server',";
                            $query .= " value = '" . $orderId . "',";
                            $query .= " count = 1,";
                            $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
                            $writeConnection->query($query);
                        }
                    }
                    $this->getResponse()->setHttpResponseCode(404);
                    $this->getResponse()->setHeader('Content-type', 'text/plain');
                    $this->getResponse()->setBody($document['error']);
                    $this->getResponse()->sendResponse();
                } else {
                    if ($document) {
                        // so to check whether this order belongs to this customer:
                        $order = Mage::helper('schracksales/order')->getFullOrder($orderId);
                        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
                        if ( intval($customerId) !== intval($order->getSchrackWwsCustomerId()) && ! Mage::getStoreConfig('schrack/solr4orders/override_customer_id') ) {
                            throw new Exception('invalid id');
                        }
                        $fileName = $document->FileName;
                        $data = $document->Data;
                        $this->getResponse()->setHeader('Content-type', 'application/pdf');
                        $this->getResponse()->setHeader('Content-Disposition', "attachment; filename=\"$fileName\"");
                        $this->getResponse()->setBody($data);
                        $this->getResponse()->sendResponse();
                    } else {
                        $this->getResponse()->setHttpResponseCode(404);
                        $this->getResponse()->setHeader('Content-type', 'text/plain');
                        $this->getResponse()->setBody($this->__('Can not retrieve document.'));
                        // Temporary logging to evaluate error:
                        if ($type && $type == 'order') {
                            $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
                            $sql = "SELECT * FROM captcha_log WHERE type LIKE 'Missing AB on MDOC-Server' AND value LIKE '" . $orderId . "'";
                            $result = $readConnection->fetchAll($sql);
                            if (is_array($result) && empty($result)) {
                                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                                $query  = "INSERT INTO captcha_log SET ";
                                $query .= " type = 'Missing AB on MDOC-Server',";
                                $query .= " value = '" . $orderId . "',";
                                $query .= " count = 1,";
                                $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
                                $writeConnection->query($query);
                            }
                        }
                        $this->getResponse()->sendResponse();
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }
    
    private function getResponse () {
        $res = Mage::app()->getResponse();
        return $res;
    }

    private function getRequest () {
        $res = Mage::app()->getRequest();
        return $res;
    }
    
    private function _getDocumentTypeFromTypeName($name) {
        switch ($name) {
            case 'order':
                return Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER;
            case 'offer':
                return Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER;
            case 'shipment':
                return Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT;
            case 'invoice':
                return Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE;
            case 'creditmemo':
                return Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO;
            default:
                throw new Exception('no such document type');
        }
    }
    
}
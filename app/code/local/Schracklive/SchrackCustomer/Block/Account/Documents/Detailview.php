<?php

class IncompleteDocumentException extends Exception {}
      
class Schracklive_SchrackCustomer_Block_Account_Documents_Detailview extends Schracklive_SchrackCustomer_Block_Account_Documents {
    private $_urls = array();
    private $_evenOdd;
    private $_document; // cache
    
    public function __construct() {
        parent::__construct();
        $this->_evenOdd = 0;
        $this->_document = null;
    }

    protected function getCartOfferNum () {
        $customerID = Mage::getSingleton('customer/session')->getCustomer()->getId();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = " SELECT DISTINCT schrack_offer_number FROM sales_flat_quote_item WHERE quote_id ="
             . " (SELECT entity_id FROM sales_flat_quote WHERE customer_id = ? AND is_active = 1 ORDER BY entity_id DESC LIMIT 1)"
             . " LIMIT 1";
        $res = $connection->fetchCol($sql,$customerID);
        if ( count($res) < 1 ) {
            return '';
        }
        return $res[0];
    }

    protected function getDocument() {
        $type = $this->getRequest()->getParam('type');
        $orderNumber = $this->getRequest()->getParam('id');
        $documentNumber = $this->getRequest()->getParam('documentId');
        $helper = Mage::helper('schracksales/order');
        $document = null;
        switch ($type) {
            case 'offer':
                $document = $helper->getFullDocument($orderNumber ? $orderNumber : $documentNumber,"Offer");
                break;
            case 'order':
                $document = $helper->getFullDocument($orderNumber ? $orderNumber : $documentNumber,"Order");
                break;
            case 'shipment':
                $document = $helper->getFullDocument($documentNumber,"Shipment");
                break;
            case 'invoice':
                $document = $helper->getFullDocument($documentNumber,"Invoice",$orderNumber);
                break;
            case 'creditmemo':
                $document = $helper->getFullDocument($documentNumber,"Creditmemo",$orderNumber);
                break;
            default:
                throw new Exception("No such type as '".$type."'");
        }

        // !!!! remove ASAP !!!!
        if ( $overrideCustomerId = Mage::getStoreConfig('schrack/solr4orders/override_customer_id') ) {
            return $document;
        }
        //----------------------------------------------------------------------
        $account = Mage::getModel('account/account')->loadByWwsCustomerId($this->_getCustomerSession()->getCustomer()->getSchrackWwsCustomerId());
        $DocViewGrantedForActCustomer = ( intval($document->getSchrackWwsCustomerId()) === intval($this->_getCustomerSession()->getCustomer()->getSchrackWwsCustomerId()) ) ? "Y" : "N";
        $DocViewGrantedForHistoryCustomer = ($account->getWwsCustomerIdHistory().'.' != '.' && intval($document->getSchrackWwsCustomerId()) === intval($account->getWwsCustomerIdHistory())) ? "Y" : "N";
        //----------------------------------------------------------------------
        if ($DocViewGrantedForActCustomer == "N" && $DocViewGrantedForHistoryCustomer == "N" ) {
            Mage::log('customer id mismatch: ' . $document->getSchrackWwsCustomerId() . ' <> ' .  $this->_getCustomerSession()->getCustomer()->getSchrackWwsCustomerId());
            throw new Exception($this->__('wrong id') . ' (error: #34516)');
        }

        return $document;        
    }
    
    protected function getOrder() {
        $order = Mage::helper('schracksales/order')->getFullOrder($this->getRequest()->getParam('id'));
        // !!!! remove ASAP !!!!
        $overrideCustomerId = Mage::getStoreConfig('schrack/solr4orders/override_customer_id');
        //----------------------------------------------------------------------
        $account = Mage::getModel('account/account')->loadByWwsCustomerId($this->_getCustomerSession()->getCustomer()->getSchrackWwsCustomerId());
        $DocViewGrantedForActCustomer = ( intval(Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId()) === intval($order->getSchrackWwsCustomerId()) ) ? "Y" : "N";
        $DocViewGrantedForHistoryCustomer = ($account->getWwsCustomerIdHistory().'.' != '.' && intval($order->getSchrackWwsCustomerId()) === intval($account->getWwsCustomerIdHistory())) ? "Y" : "N";
        //----------------------------------------------------------------------
        if ( !$overrideCustomerId && $DocViewGrantedForActCustomer == "N" && $DocViewGrantedForHistoryCustomer == "N" )
             throw new Exception($this->__('invalid id') . ' (error: #34517)');
        return $order;
    }
    
    protected function getBackQty($item) {
        $type = $this->getRequest()->getParam('type');
        switch($type) {
            case 'offer':
                return $this->_formatQty($item->getQtyOrdered());
            case 'order':
                return $this->_formatQty($item->getQtyOrdered());
            case 'invoice':
                return $this->_formatQty($item->getQtyOrdered());
            case 'shipment':
                return $this->_formatQty($item->getQtyOrdered());
            case 'creditmemo':
                return $this->_formatQty($item->getQtyOrdered());
            default:
                throw new Exception($this->__('cannot determine qty') . ' (error: #34518)');
        }
    }
    
    protected function getQty($item, $format = true) {
        $type = $this->getRequest()->getParam('type');
        switch($type) {
            case 'order':
            case 'offer':
                $qty = $item->getQtyOrdered();
                break;
            default:
                $qty = $item->getQty();
        }
        
        if ($format)
            return $this->_formatQty($qty);
        else
            return $qty;
    }
    
    protected function getQtyBackordered($item) {
        return $this->_formatQty($item->getSchrackBackorderQty());
    }
    
    protected function getPrice($item) {
        return Mage::helper('checkout')->formatPrice($item->getPrice());
    }

    protected function getAmount($item) {
        return Mage::helper('checkout')->formatPrice($item->getPrice());
    }

     private function _formatQty ( $qty ) {
        if ( is_numeric($qty) ) {
            $qty = Mage::helper('schrackcore/string')->numberFormat($qty);
        }
        return $qty;
    }
    
    protected function getFormattedDate($timestamp = null) {
        if ( ! $timestamp ) {
            return '';
        }
        $date = new Zend_Date($timestamp); 
        return $date->get(Zend_Date::DATE_MEDIUM);
    }
    
    protected function getSqlDate($timestamp = null) {
        return date('Y-m-d', $timestamp);  
    }
    
    protected function getDocumentCustomerNumber($document) {
        if (strlen($document->getSchrackWwsReference()))
            return $document->getSchrackWwsReference();
        else
            return $this->getDocumentNumber($document);
    }

    protected function getDocumentCustomerNumberLabel($document) {
        $type = $this->getRequest()->getParam('type');
        if ( strlen($document->getSchrackWwsReference()) )
            return $this->__('Meine Bestellangabe');
        elseif ( isset($type) && strlen($type) ) {
            return $this->__(ucfirst($type));
        } else {
            return $this->__('Meine Bestellangabe');
        }
    }
   
    protected function getDocumentNumber($document) {
        $type = $this->getRequest()->getParam('type');
        switch ($type) {
            case 'offer':
                if ( $document->hasSchrackWwsOfferNumber() ) {
                    return $document->getSchrackWwsOfferNumber();
                } else {
                    return '';
                }
            case 'order':
                return $document->getSchrackWwsOrderNumber();
            case 'invoice':
                return $document->getSchrackWwsInvoiceNumber();
            case 'shipment':
                return $document->getSchrackWwsShipmentNumber();
            case 'creditmemo':
                return $document->getSchrackWwsCreditmemoNumber();
            default:
                throw new Exception('cannot determine document id due to unknown type');
        }        
    }    
    
    protected function isCurrentDocument($document, $type, $id) {
        $requestType = $this->getRequest()->getParam('type');
        if ( $requestType === 'offer' ) {
            $docId = $document->getSchrackWwsOfferNumber();
        } else {
            $docId = $document->getDocumentNumber();
        }
        return ($docId === $id) && ($requestType === $type);
    }
    
    protected function getDocumentLinkUrl($document, $type) {
        $docId = $document->getDocumentNumber();
        $url = $this->getUrl('*/*/*', array('id' => $this->getRequest()->getParam('id'), 'type' => $type, 'documentId' => $docId));
        return $url;
    }      
    
    protected function getItems($document) {
        $items = $document->getAllItems();
        $text = $this->getRequest()->getParam('text');

        if ( $text ) {
            $filterItems = array();
            foreach ( $items as $item ) {
                if ( stripos($item->getSku(),$text) !== false || stripos($item->getDescription(),$text) !== false ) {
                    $filterItems[] = $item;
                }
            }
            $items = $filterItems;
        }
        // schrack_position, name, qty, qty_backordered
        $this->sortOrder = $this->getRequest()->getParam('sort_order', 'schrack_position');
        if ( $this->sortOrder && $this->sortOrder > '' ) {
            $this->desc = $this->getRequest()->getParam('direction') === 'desc';
            $this->sortInt = $this->sortOrder != 'name';

            usort($items, function ( $l, $r ) {
                $l = $l->getData($this->sortOrder);
                $r = $r->getData($this->sortOrder);
                if ( $this->desc ) {
                    if ( $this->sortInt )
                        return $r - $l;
                    else
                        return strcmp($r, $l);
                } else {
                    if ( $this->sortInt )
                        return $l - $r;
                    else
                        return strcmp($l, $r);
                }
            });
        }

        $this->getLayout()->getBlock('html_pager')->setAvailableLimit(array(1000=>1000));
        $this->getLayout()->getBlock('html_pager2')->setAvailableLimit(array(1000=>1000));

        if ($this->getRequest()->getParam('type') == 'offer') {
            $this->getLayout()->getBlock('html_pager')->setAvailableLimit(array(1000=>1000));
            $this->getLayout()->getBlock('html_pager2')->setAvailableLimit(array(1000=>1000));
        }

        return $items;
    }
    
     protected function _getSortOrder() {
         $param = $this->getRequest()->getParam('sort_order', 'schrack_position');
         if ($param === 'qty')
             return $this->_getQtyField();
         else
            return $param;
    }
    
    protected function _getQtyField() {
        $type = $this->getRequest()->getParam('type');
        switch($type) {
            case 'offer':
            case 'order':
                return 'qty_ordered';
            case 'invoice':
            case 'shipment':
            case 'creditmemo':
                return 'qty';
            default:
                throw new Exception('cannot determine qty field');
        }
    }
    
    protected function _getDirection() {
        return $this->getRequest()->getParam('direction', 'asc');   
    }
    
    protected function getDownloadUrl() {
        return $this->getUrl('*/*/documentsDownload', array('id' => $this->getRequest()->getParam('id'), 'type' => $this->getRequest()->getParam('type'), 'documentId' => $this->getRequest()->getParam('documentId')));
    }

    protected function canBeAddedToLists($item) {
        return ($this->getProductUrlFromItem($item) !== null && ! $item->getIsSubPosition());
    }

    protected function getProductUrlFromItem($item) {
        return Mage::helper('schrackcustomer/order')->getProductUrlFromItem($item);
    }
    
    protected function getContactCustomerName($order, $document) {
       $contactNo = $order->getData('OrdererContactNumber');
       $customer  = $order->getData('CustomerNumber');
       if ( ! $contactNo || ! $customer ) {
           return null;
       }
       $c = Mage::getModel('customer/customer')->loadByWwsContactNumber($customer,$contactNo);
       if ( $c ) {
           return $c->getName();
       } else {
           return null;
       }
    }

    protected function getIsEven() {
        return ($this->_evenOdd % 2 === 0);
    }

    protected function getEvenOddClass() {
        return ((++$this->_evenOdd) % 2 === 0) ? 'even' : 'odd';
    }
    protected function resetEvenOddClass() {
        $this->_evenOdd = 0;        
    }
    protected function getLineCounter() {
        return $this->_evenOdd;
    }
    
    private $_columns = array(
        'checkbox' => 1,
        'schrack_position' => 2,
        // 'sku' => 5,
        'name' => 7,
        'qty' => 2,
        'qty_backordered' => 3,
        'price' => 3,
        'surcharge' => 3,
        'row_total' => 3
    );
    
    
     protected function getColumnNumber($name) {
         
        $this->_document = $this->_document ? $this->_document : $this->getDocument();
        $docTypeShortName = $this->getDocTypeShortName($this->_document);
        
        $columns = $this->_columns;
        if ($docTypeShortName === 'offer') { // qty_backordered is hidden
            $columns['name'] += 3;
            // $columns['sku'] += 1;
        }
        
        return $columns[$name];
    }


    public function getCorrectQuantity($sku, $qty) {
        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        if (is_object($product)) {
            $stockItem = $product->getStockItem();
            $matchingQuantity = $stockItem->suggestQty($qty);
        } else {
            $matchingQuantity = 1;
        }
        return $matchingQuantity;
    }

    private function joinOrderItemData ( $collection ) {
        $collection->join(
            array('orderitem' => 'sales/order_item'),    // alias => table
            'order_item_id = item_id',                          // on
            array('schrack_surcharge' => 'schrack_surcharge',   // fields
                  'qty_backordered'   => 'qty_backordered'   )
        );
        return $collection;
    }
}

<?php

class Schracklive_SchrackCustomer_Block_Account_Documents extends Schracklive_SchrackCustomer_Block_Account_Dashboard_Hello {
    private $_columns = array(
        'checkbox' => 1,
        'date' => 3,
        'document_number' => 4,
        'order_number' => 4,
        'reference' => 5,
        'document_type' => 2,
        'status' => 5
    );

    protected $_sessionParams;
    protected $_arrayHelper;
    protected $_haveTnt;

    public function __construct() {
        parent::__construct();
        $this->_sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
        $this->_arrayHelper = Mage::helper('schrackcore/array');
        $this->_haveTnt = false;
    }

    protected function getOrderButtonText ( $document ) {
        switch ( strtolower($document->getSchrackWwsStatus()) ) {
            case 'la1online' :
            case 'la1' :
                return $this->__('Order Now!');
            default :
                return $this->__('Order Again!');
        }
    }

    protected function canBeOrdered ( $document  ) {
        if ( $document instanceof Schracklive_SchrackSales_Model_Order ) {
            return $document->isOfferAndCanBeOrdered();
        }
        return true;
    }

    protected function getColumnNumber($name) {
        $columns = $this->_columns;
        if ($this->_haveTnt) {
            $columns['tnt'] = 0; // will be calc'ed below
        }
        if ($this->isDetailsearchRequest()) {
            if (!$this->showOrderNumber()) {
                $columns['reference'] += 2;
                $columns['document_type'] += 2;
            }
        } else {
            if ($this->_haveTnt) {
                $columns['tnt'] += 2;
            } else {
                $columns['date'] += 2;
            }
            if (!$this->showOrderNumber()) {            
                $columns['document_number'] += 2;
                $columns['reference'] += 2;                
            }
        }
        
        if ($this->_haveTnt) {
            $columns['tnt'] += 2;
            $columns['status'] -= 2;
        }
        
        return $columns[$name];
    }
    
    /**
     * find out whether we were called for all kinds of documents
     * (as opposed to: one specific document type)
     * @return boolean
     */
    protected function isDetailsearchRequest() {
        $action = $this->getRequest()->getActionName();
        return ($action === 'documentsDetailsearch');
    }        
    
    protected function isOverviewRequest() {
        return ($this->getRequest()->getActionName() === 'index');
    }
        
    protected function getFormattedDate($timestamp = null) {
        if ( ! $timestamp ) {
            return '';
        }
        $date = new Zend_Date($timestamp); 
        return $date->get(Zend_Date::DATE_MEDIUM);
    }
    
    protected function getSqlDate($timestamp = null) {
        if (intval($timestamp) > 0)
            return date('Y-m-d', $timestamp);  
        else
            return null;
    }
    
    protected function getDocTypeShortName($document) {
        $helper = Mage::helper('schracksales/order');
        $docType = $helper->getDocType($document);
        switch ($docType) {
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER:
                return 'order';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT:
                return 'shipment';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE:
                return 'invoice';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO:
                return 'creditmemo';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER:
                return 'offer';
            default:
                throw new Exception('unknown document type '.$docType);
        }
    }
    
    protected function getDocTypeName($document) {
        $helper = Mage::helper('schracksales/order');
        $docType = $helper->getDocType($document);
        switch ($docType) {
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER:
                return 'order';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT:
                return 'shipment';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE:
                return 'invoice';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO:
                return 'credit memo';
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER:
                return 'offer';
            default:
                throw new Exception('unknown document type '.$docType);
        }
    }
    
    protected function getDocumentId($document) {
        $helper = Mage::helper('schracksales/order');
        $docType = $helper->getDocType($document);
        switch ($docType) {
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER:
                return $document->getOrderId();
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT:
                return $document->getShipmentId();
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE:
                return $document->getInvoiceId();
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO:
                return $document->getCreditMemoId();
            case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER:
                return $document->getSchrackWwsOfferNumber();
            default:
                throw new Exception('unknown document type '.$docType);
        }
    }
    
    protected function getSortOrder() {
        return $this->_getSortOrder();
    }
    
    protected function getDirection() {
        return $this->_getDirection();
    }
       
    public function getDirectionImageSkinUrl($order) {
        if ($this->_getSortOrder() === $order) {
            if ($this->_getDirection() === 'asc')
                return $this->getSkinUrl('images/sort-up.png');
            else
                return $this->getSkinUrl('images/sort-down.png');
        } else
            return $this->getSkinUrl('images/sort-inactive.png');
    }
    
    /**
     * Retreive customer session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Retrieve customer login status
     *
     * @return bool
     */
    protected function _isCustomerLogIn()
    {
        return $this->_getCustomerSession()->isLoggedIn();
    }

    /**
     * Retrieve logged in customer
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function getCurrentCustomer()
    {
        return $this->_getCustomerSession()->getCustomer();
    }

    /**
     * 
     * @return array
     */
    protected function _getSearchRequest($presetKey = null) {        
        setlocale(LC_ALL, Mage::app()->getLocale()->getLocale());
        $sessionData = Mage::getSingleton('customer/session')->getData('documentParams');
        $searchRequest = array( 
            'type_offers' => ( $this->_arrayHelper->arrayDefault($sessionData, 'offers') === '1' ),
            'type_orders' => ( $this->_arrayHelper->arrayDefault($sessionData, 'orders') === '1' ),
            'type_deliveries' => ( $this->_arrayHelper->arrayDefault($sessionData, 'deliveries') === '1' ),
            'type_invoices' => ( $this->_arrayHelper->arrayDefault($sessionData, 'invoices') === '1' ),
            'type_creditmemos' => ( $this->_arrayHelper->arrayDefault($sessionData, 'creditmemos') === '1' ),
            'status_open' => ( $this->_arrayHelper->arrayDefault($sessionData, 'open') === '1' ),
            'status_ordered' => ( $this->_arrayHelper->arrayDefault($sessionData, 'open') === '1' ),
            'status_commissioned' => ( $this->_arrayHelper->arrayDefault($sessionData, 'commissioned') === '1' ),
            'status_delivered' => ( $this->_arrayHelper->arrayDefault($sessionData, 'delivered') === '1' ),
            'status_invoiced' => ( $this->_arrayHelper->arrayDefault($sessionData, 'invoiced') === '1' ),
            'status_credited' => ( $this->_arrayHelper->arrayDefault($sessionData, 'credited') === '1' ),
            'date_range' => $this->_arrayHelper->arrayDefault($sessionData, 'date_range'),
            'date_from' => $this->getSqlDate(strtotime($this->_arrayHelper->arrayDefault($sessionData, 'date_from'))),
            'date_to' => $this->getSqlDate(strtotime($this->_arrayHelper->arrayDefault($sessionData, 'date_to'))),
            'text' => $this->_arrayHelper->arrayDefault($sessionData, 'text'),
            'sort_order'  => $this->_arrayHelper->arrayDefault($sessionData, 'sort_order', 'document_date_time'),
            'direction'  => $this->_arrayHelper->arrayDefault($sessionData, 'direction', 'des'),
        );
        if ($presetKey !== null)
            $searchRequest[$presetKey] = true;
        
        /**
         * if no status is set, we want documents for all statuses; the same goes for type
         */
        $this->setAllKeysIfNoneIsSet($searchRequest, 'type_');
        $this->setAllKeysIfNoneIsSet($searchRequest, 'status_');
        $searchRequest['text'] = $this->_arrayHelper->arrayDefault($sessionData, 'text');
        
        switch ($searchRequest['date_range']) {
            case 'this_year':
                $searchRequest['date_from'] = date('Y') . '-01-01';
                break;
            case 'this_month':
                $x = strtotime('first day of this month');
                $searchRequest['date_from'] = $this->getSqlDate(strtotime('first day of this month'));
                break;
            case 'last_3_months':
                $searchRequest['date_from'] = $this->getSqlDate(strtotime('-3 month'));
                break;
        }
        
        return $searchRequest;
    }
    
    private function setAllKeysIfNoneIsSet(&$array, $prefix) {
        $isOneSet = false;
        foreach ($array as $key => $value) {
            if (preg_match('/^' . $prefix . '/', $key) && $array[$key]) {
                $isOneSet = true;
                break;
            }
        }
        if (!$isOneSet) {
            foreach ($array as $key => $value) {
                if (preg_match('/^' . $prefix . '/', $key)) {
                    $array[$key] = true;
                }
            }
        }
    }

    protected function getTrackandtraceUrl($document) {
        return Mage::helper('schracksales/order')->getTrackandtraceUrl($document);
    }
    
}

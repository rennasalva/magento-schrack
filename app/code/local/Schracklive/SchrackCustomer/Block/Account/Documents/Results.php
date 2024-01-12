<?php

class Schracklive_SchrackCustomer_Block_Account_Documents_Results extends Schracklive_SchrackCustomer_Block_Account_Documents {

    protected $_orderCollection = null;
    protected $_documentCollection = null;
    protected $_sessionParams;
    
    public function __construct() {
        parent::__construct();
        $this->_sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
    }
    
    public function getFinalDocuments() {
        $collection = $this->getDocuments();
        return $collection;
    }

    /**
     * @Override
     * @return Mage_Sales_Model_Mysql4_???_Collection
     */
    public function getDocuments() {
        $searchRequest = $this->_getSearchRequest();

        /**
         * @var Schracklive_SchrackSales_Helper_Order_SearchParameters
         */
        $searchParameters = new Schracklive_SchrackSales_Helper_Order_SearchParameters(
            $searchRequest['status_open'], true, $searchRequest['status_commissioned'], $searchRequest['status_delivered'],
            $searchRequest['status_invoiced'], $searchRequest['status_credited'], 
            $searchRequest['date_from'], $searchRequest['date_to'], 
            $searchRequest['text'], 
            $searchRequest['sort_order'], $searchRequest['direction'] === 'asc'
        );
        
        $collection = $this->_getDocuments($searchParameters);                

        return $collection;
    }

    public function isOfferList () {
        return false;
    }
    
    /**
     * @param Schracklive_SchrackSales_Helper_Order_SearchParameters $searchRequest
     * 
     * @return Mage_Sales_Model_Mysql4_*_Collection
     */
    protected function _getDocuments(Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParameters) {        
        if ($this->_documentCollection === null) {
            /**
             * @var Schracklive_SchrackSales_Helper_Order
             */
            $helper = Mage::helper('schracksales/order');
            $this->_documentCollection = $helper->searchSalesOrders($searchParameters, null, -1, 'SchrackCustomer-Block-Account-Documents-Results.php');
        }
            
        return $this->_documentCollection;
    }
    
    protected function getStatusImageSkinUrl($status) {
        $statuses = array( 'offered' => 'status-open.png',
            'offered online' => 'status-open.png',
            'ordered' => 'status-open.png',
            'commissioned' => 'status-in-delivery.png',
            'partly shipped' => 'status-delivered.png',
            'delivered' => 'status-delivered.png',
            'partly invoiced' => 'status-delivered.png',
            'invoiced' => 'status-delivered.png',
            'credited' => 'status-wip.png',
            'unknown' => 'status-wip.png',
        );
        if (isset($statuses[$status]))
            return $this->getSkinUrl('images/' . $statuses[$status]);
        else
            return $this->getSkinUrl('images/status-open.png');
    }
    
    protected function getDocumentNumber($document) {
        return $document->getWwsDocumentNumber();
    }
    
    protected function getDocumentCustomerNumber($document, $forWhat) {
        if ( intval(Mage::getStoreConfigFlag('schrack/shop/enable_custom_project_info_in_checkout')) != 1 ) {
            return $document->getSchrackWwsReference();
        } else {
            return ($document->getSchrackWwsReference() > '' ? $document->getSchrackWwsReference() : '-') . '/' . ( $document->getSchrackCustomerProjectInfo() ? $document->getSchrackCustomerProjectInfo() : '-');
        }
    }
    
    protected function _searchOrders(Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParameters, $pager) {
        /**
         * @var Schracklive_SchrackCustomer_Helper_Order
         */
        $helper = Mage::helper('schrackcustomer/order');
        return $helper->searchOrders($searchParameters, $pager);
    }

    // protected function
    
    protected function getDocumentStatus($document) {
        switch (strtolower($document->getSchrackWwsStatus())) {
            case 'la1':
                return $this->__('offered');
            case 'la0':
            case 'la1online':
                return $this->__('offered online');
            case 'la2':
                return $this->__('ordered');
            case 'la3':
                return $this->__('commissioned');
            case 'la4':
                if ( $document->getData('schrack_sum_backorder_qty') > 0 ) {
                    return $this->__('partly shipped');
                } else {
                    return $this->__('delivered');
                }
            case 'la5':
                if ( $document->getData('schrack_sum_backorder_qty') > 0 ) {
                    return $this->__('partly invoiced');
                } else {
                    return $this->__('invoiced');
                }
            case 'la8':
                return $this->__('credited');
            default:
                return $this->__('unknown');
        }
    }

    protected function getUnfakedDocumentStatus($document) {
        switch (strtolower($document->getSchrackWwsStatus())) {
            case 'la0':
            case 'la1':
            case 'la1online':
                return 'offered';
            case 'la2':
                return 'ordered';
            case 'la3':
                return 'commissioned';
            case 'la4':
                return 'delivered';
            case 'la5':
                return 'invoiced';
            case 'la8':
                return 'credited';
            default:
                return 'unknown';
        }
    }

    protected function _getSortOrder() {
        return $this->_arrayHelper->arrayDefault($this->_sessionParams, 'sort_order', 'document_date_time');
    }
    
    protected function _getDirection() {
        return $this->_arrayHelper->arrayDefault($this->_sessionParams, 'direction', 'desc');        
    }
    
    protected function getColspanForListActionTd() { // TODO
        if ($this->showOrderNumber()) {
            if ($this->getRequest()->getActionName() === 'documentsDetailsearch')
                return 6;
            else
                return 5;
        } else
            return 4;        
    }

    /**
     * for the THs in the result lists, we need the number field name according to document type
     */
    protected function getDocumentNumberFieldDescription() {
        return $this->__('Number');
    }
    protected function getDownloadUrl() {
        return $this->getUrl('*/*/documentsDownload');
    }
    
    
    protected function showOrderNumber() {
        $action = $this->getRequest()->getActionName();
        return (in_array($action, array('invoices', 'creditmemos', 'documentsDetailsearch')));
    }   
    
    protected function haveSortableResultTable() {
        return true;
    }
    
    protected function getClassId() {
        return md5(get_class($this));
    }
}

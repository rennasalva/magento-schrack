<?php

class Schracklive_SchrackCustomer_Block_Account_Documents_Results_Invoices extends Schracklive_SchrackCustomer_Block_Account_Documents_Results {

    /**
     * @Override
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    public function getDocuments() {
        $searchRequest = $this->_getSearchRequest('type_invoices');              
                
        /**
         * @var Schracklive_SchrackSales_Helper_Order_SearchParameters
         */
        $searchParameters = new Schracklive_SchrackSales_Helper_Order_SearchParameters();
        $searchParameters->isOffered = $searchRequest['status_open'];
        $searchParameters->isOrdered = $searchRequest['status_ordered'];
        $searchParameters->isCommissioned = $searchRequest['status_commissioned'];
        $searchParameters->isDelivered = $searchRequest['status_delivered'];
        $searchParameters->isInvoiced = $searchRequest['status_invoiced'];
        $searchParameters->isCredited = $searchRequest['status_credited'];
        $searchParameters->fromDate = $searchRequest['date_from'];
        $searchParameters->toDate = $searchRequest['date_to'];
        $searchParameters->text = $searchRequest['text'];
        $searchParameters->getInvoiceDocs = true;
        $searchParameters->sortColumnName = $searchRequest['sort_order'];
        $searchParameters->isSortAsc = ($searchRequest['direction'] === 'asc');

        $this->getLayout()->getBlock('html_pager')->setAvailableLimit(array(1000=>1000));
        $this->getLayout()->getBlock('html_pager2')->setAvailableLimit(array(1000=>1000));
        
        $collection = $this->_searchOrders($searchParameters, $this->getLayout()->getBlock('html_pager'));                
        $this->getLayout()->getBlock('html_pager2')->setCollection($collection);
        
        return $collection; 
    }
    /**
     * for the THs in the result lists, we need the number field name according to document type
     */
    protected function getDocumentNumberFieldDescription() {
        return $this->__('Invoice Number');
    }

}

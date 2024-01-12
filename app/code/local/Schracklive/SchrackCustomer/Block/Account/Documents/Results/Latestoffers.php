<?php

class Schracklive_SchrackCustomer_Block_Account_Documents_Results_Latestoffers extends Schracklive_SchrackCustomer_Block_Account_Documents_Results {

    /**
     * @Override
     */
    public function isOfferList () {
        return true;
    }

    /**
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    public function getDocuments() {
        if ( $this->_documentCollection == null ) {
            /**
             * @var Schracklive_SchrackSales_Helper_Order
             */
            $helper = Mage::helper('schracksales/order');
            $this->_documentCollection = $helper->getLastOffers();
        }
        return $this->_documentCollection;
    }

     /**
     * for the THs in the result lists, we need the number field name according to document type
     */
    protected function getDocumentNumberFieldDescription() {
        return $this->__('Offer Number');
    }
    
    protected function haveSortableResultTable() {
        return false;
    }
   
}

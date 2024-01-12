<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SearchParameters
 *
 * @author d.laslov
 */
class Schracklive_SchrackSales_Helper_Order_SearchParameters {

    public $isOffered = true, $isOrdered = true, $isCommissioned = true, $isDelivered = true, $isInvoiced = true, $isCredited = true,
           $fromDate = null, $toDate = null, $text = null, 
           $getOfferDocs = false, $getOrderDocs = false, $getDeliveryDocs = false, $getInvoiceDocs = false, $getCreditMemoDocs = false,
           $sortColumnName = null, $isSortAsc = true;
    
    
    public function __construct () {
    }

    public function doSearchOffersWithMultipleStates () {
        return      $this->getOfferDocs
                &&  $this->isOffered
                &&  (       $this->isOrdered
                        ||  $this->isCommissioned
                        ||  $this->isDelivered
                        ||  $this->isInvoiced
                        ||  $this->isCredited);
    }

    public function doSearchNoOffers () {
        return      $this->isOrdered
                ||  $this->isCommissioned
                ||  $this->isDelivered
                ||  $this->isInvoiced
                ||  $this->isCredited;
    }

    public function doSearchDocs () {
        return     $this->getOfferDocs 
                || $this->getOrderDocs 
                || $this->getDeliveryDocs 
                || $this->getInvoiceDocs 
                || $this->getCreditMemoDocs;
    }

    public function setSearchDocs ( $flag = true ) {
        $this->getOfferDocs = $this->getOrderDocs = $this->getDeliveryDocs = $this->getInvoiceDocs = $this->getCreditMemoDocs = $flag;
    }
    
    public function __toString () {
        return   "" . $this->isOffered . '|' . $this->isOrdered . '|' . $this->isCommissioned . '|' . $this->isDelivered . '|' . $this->isInvoiced . '|' . $this->isCredited
               . '|' . $this->fromDate . '|' . $this->toDate . '|' . $this->text . '|' . $this->getOfferDocs . '|' . $this->getOrderDocs . '|' . $this->getDeliveryDocs
               . '|' . $this->getInvoiceDocs . '|' . $this->getCreditMemoDocs . '|' . $this->sortColumnName . '|' . $this->isSortAsc;
    }
}

?>

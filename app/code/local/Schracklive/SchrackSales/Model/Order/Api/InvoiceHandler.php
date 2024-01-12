<?php

class Schracklive_SchrackSales_Model_Order_Api_InvoiceHandler extends Schracklive_SchrackSales_Model_Order_Api_DocumentHandler {

    public function __construct ( $soapOrder, $soapItems, $magentoOrder, $convertor ) {
        parent::__construct($soapOrder,$soapItems,$magentoOrder,$convertor);
    }
    
    public function find () {
        return $this->_findImpl('sales/order_invoice_collection',
                                'schrack_wws_invoice_number',
                                $this->_soapOrder->InvoiceNumber);
    }

    protected function getIndexIdName () {
        return 'invoice_id';
    }

    protected function _findImpl ( $modelName, $docFieldName, $docFieldVal ) {
        $collection = Mage::getResourceModel($modelName);
        $collection->addAttributeToFilter('main_table.order_id',$this->_magentoOrder->getEntityId());
        $collection->addAttributeToFilter($docFieldName,$docFieldVal);
        if ( intval($this->_soapOrder->IsCollectiveInvoice) ) {
            $collection->getSelect()->join(array('index' => 'sales_flat_order_schrack_index'), 'index.' . $this->getIndexIdName() . ' = main_table.entity_id', array('index.wws_followup_order_number'));
        }
        $collection->getSelect();
        foreach ( $collection as $tmpDocument ) {
            if ( intval($this->_soapOrder->IsCollectiveInvoice) && $this->_soapOrder->OrderNumber != $tmpDocument->getWwsFollowupOrderNumber() ) {
                continue;
            }
            if ( $this->checkPositionsAndDeleteIfNecessary($tmpDocument) ) {
                $this->_document = $tmpDocument;
                return $this->_document;
            }
        }
        return false;
    }

    protected function _convertOrderToDocument () {
        return $this->_convertor->toInvoice($this->_magentoOrder);
    }
    
    protected function _convertOrderItemToDocumentItem ( $item ) {
        return $this->_convertor->itemToInvoiceItem($item);
    }
    
    protected function _setInitialSpecificDocumentAttributes () {
        $this->_document->setSchrackWwsInvoiceNumber($this->_soapOrder->InvoiceNumber);
        if ( $this->_soapOrder->InvoiceDate ) {
            $this->_document->setSchrackWwsDocumentDate($this->_soapOrder->InvoiceDate);
        }
        $this->_document->setSchrackIsCollectiveDoc($this->_soapOrder->IsCollectiveInvoice);
        
    }
    protected function _setUpdateSpecificDocumentAttributes () {
    }
    
    protected function _setUpdateSpecificItemAttributes ( $documentItem, $soapItem ) {
        // $documentItem->setBaseDiscountAmount(???);     
        // $documentItem->setBaseHiddenTaxAmount(???);   
        $documentItem->setBasePrice($soapItem->Price);
        // $documentItem->setBasePriceInclTax($soapItem->AmountTot);
        $documentItem->setBaseRowTotal($soapItem->AmountNet);
        $documentItem->setBaseRowTotalInclTax($soapItem->AmountTot);  
        $documentItem->setBaseTaxAmount($soapItem->AmountVat);
        // $documentItem->setBaseWeeeTaxAppliedAmount(???);
        // $documentItem->setBaseWeeeTaxDisposition(???); 
        // $documentItem->setBaseWeeeTaxRowDisposition(???);
        // $documentItem->setDiscountAmount(???);          
        // $documentItem->setPriceInclTax($soapItem->Price);
        // $documentItem->setRowTotal($soapItem->AmountNet); -- already set in base class
        $documentItem->setRowTotalInclTax($soapItem->AmountTot);       
        // $documentItem->setWeeeTaxAppliedAmount(???);
        // $documentItem->setWeeeTaxAppliedRowAmount(???);
        // $documentItem->setWeeeTaxRowDisposition(???);
    }
    
    protected function _errorNoOrderItem ( $sku ) {
        throw new Mage_Api_Exception(213,'Try to invoice unordered item "'
                                         .$sku
                                         .'" for invoiceNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber.'")');
    }
    
    protected function _errorNoDocumentItem ( $sku ) {
        throw new Mage_Api_Exception(214,'Internal error: SKU "'
                                         .$sku.'" for invoiceNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber
                                         .'") not found!');
    }

    protected function _handleOldAndReturnNewItemQuantity ( $magentoOrderItem, $soapItem, $documentItem ) {
        $newQty = abs($soapItem->Qty);
        $res = 0;
        if ( $this->_soapOrder->OrderNumber === $this->_soapOrder->OriginalOrderNumber ) {
            $res = $newQty;
        }
        else {
            $oldVal = $magentoOrderItem->getQtyInvoiced();
            $newVal = $oldVal + $newQty;
            $res = $newVal;
        }
        $magentoOrderItem->setQtyInvoiced(0); // need that to reset for bloody magento to save shipment
        $documentItem->getOrderItem()->setQtyInvoiced(0); // need that to reset for bloody magento to save shipment
        return $res;
    }
    
    protected function _setNewOrderItemQuantity ( $magentoOrderItem, $newVal ) {
        $magentoOrderItem->setQtyInvoiced($newVal);
    }
    
}

?>

<?php

class Schracklive_SchrackSales_Model_Order_Api_CreditMemoHandler extends Schracklive_SchrackSales_Model_Order_Api_InvoiceHandler {

    public function __construct ( $soapOrder, $soapItems, $magentoOrder, $convertor ) {
        parent::__construct($soapOrder,$soapItems,$magentoOrder,$convertor);
    }
    
    public function find () {
        return $this->_findImpl('sales/order_creditmemo_collection',
                                'schrack_wws_creditmemo_number',
                                $this->_soapOrder->InvoiceNumber);
    }
    
    protected function getIndexIdName () {
        return 'credit_memo_id';
    }

    protected function _convertOrderToDocument () {
        return $this->_convertor->toCreditmemo($this->_magentoOrder);
    }
    
    protected function _convertOrderItemToDocumentItem ( $item ) {
        return $this->_convertor->itemToCreditmemoItem($item);
    }
    
    protected function _setInitialSpecificDocumentAttributes () {
        $this->_document->setSchrackWwsCreditmemoNumber($this->_soapOrder->InvoiceNumber);
        if ( $this->_soapOrder->InvoiceDate ) {
            $this->_document->setSchrackWwsDocumentDate($this->_soapOrder->InvoiceDate);
        }
        $this->_document->setSchrackIsCollectiveDoc($this->_soapOrder->IsCollectiveInvoice);
    }
    
    
    protected function _setTotalQty ( $qty ) {
        // no TotalQty in creditmemo...
    }
    
    protected function _errorNoOrderItem ( $sku ) {
        throw new Mage_Api_Exception(215,'Try to credit unordered item "'
                                         .$sku
                                         .'" for invoiceNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber.'")');
    }
    
    protected function _errorNoDocumentItem ( $sku ) {
        throw new Mage_Api_Exception(216,'Internal error: SKU "'
                                         .$sku.'" for invoiceNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber
                                         .'") not found!');
    }

    protected function _handleOldAndReturnNewItemQuantity ( $magentoOrderItem, $soapItem, $documentItem ) {
        $res = 0;
        if ( $this->soapOrder->OrderNumber == $this->soapOrder->OriginalOrderNumber ) {
            $res = abs($soapItem->Qty);
        }
        else {
            $oldVal = $magentoOrderItem->getQtyInvoiced();
            $newVal = $oldVal + abs($soapItem->Qty);
            $res = $newVal;
        }
        $magentoOrderItem->setQtyInvoiced(0); // need that to reset for bloody magento to save shipment
        $documentItem->getOrderItem()->setQtyInvoiced(0); // need that to reset for bloody magento to save shipment
        return $res;
    }
}

?>

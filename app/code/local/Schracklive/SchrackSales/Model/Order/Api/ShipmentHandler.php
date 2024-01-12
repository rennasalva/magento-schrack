<?php

class Schracklive_SchrackSales_Model_Order_Api_ShipmentHandler extends Schracklive_SchrackSales_Model_Order_Api_DocumentHandler {

    public function __construct ( $soapOrder, $soapItems, $magentoOrder, $convertor ) {
        parent::__construct($soapOrder,$soapItems,$magentoOrder,$convertor);
    }
    
    public function find () {
        return $this->_findImpl('sales/order_shipment_collection',
                                'schrack_wws_shipment_number',
                                $this->_soapOrder->ShipmentNumber);
    }
    
    protected function _convertOrderToDocument () {
        return $this->_convertor->toShipment($this->_magentoOrder);
    }
    
    protected function _convertOrderItemToDocumentItem ( $item ) {
        return $this->_convertor->itemToShipmentItem($item);
    }
    
    protected function _setInitialSpecificDocumentAttributes () {
        $this->_document->setSchrackWwsShipmentNumber($this->_soapOrder->ShipmentNumber);
        if ( $this->_soapOrder->ShipmentDate ) {
            $this->_document->setSchrackWwsDocumentDate($this->_soapOrder->ShipmentDate);
        }
    }
    protected function _setUpdateSpecificDocumentAttributes () {
        $this->_document->setTotalWeight($this->_soapOrder->WeightTot);
        $this->_document->setSchrackWwsParcels($this->_soapOrder->Parcels);
    }
    
    protected function _setUpdateSpecificItemAttributes ( $documentItem, $soapItem ) {
        // would be the only option: $documentItem->setWeight(???);
    }
    
    protected function _errorNoOrderItem ( $sku ) {
        throw new Mage_Api_Exception(211,'Try to ship unordered item "'
                                         .$sku
                                         .'" for shipmentNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber.'")');
    }
    
    protected function _errorNoDocumentItem ( $sku ) {
        throw new Mage_Api_Exception(212,'Internal error: SKU "'
                                         .$sku.'" for shipmentNo "'
                                         .$this->_soapOrder->ShipmentNumber
                                         .'" and orderNo "'
                                         .$this->_soapOrder->OrderNumber
                                         .'" (was "'
                                         .$this->_soapOrder->OriginalOrderNumber
                                         .'") not found!');
    }

    protected function _handleOldAndReturnNewItemQuantity ( $magentoOrderItem, $soapItem, $documentItem ) {
        // TODO: remove that stuff if problem does not longer exist
        try {
            if ( ! is_object($magentoOrderItem) ) {
                throw new Exception("magentoOrderItem not set!!!");
            }
             if ( ! is_object($documentItem) ) {
                throw new Exception("documentItem not set!!!");
             }
        } catch ( Exception $ex ) {
            Mage::logException($ex);
        }
        // end TODO
        $res = 0;
        if ( $this->_soapOrder->OrderNumber == $this->_soapOrder->OriginalOrderNumber ) {
            $res = $soapItem->Qty;
        }
        else {
            $oldVal = $magentoOrderItem->getQtyShipped();
            $newVal = $oldVal + $soapItem->Qty;
            $res = $newVal;
        }
        $magentoOrderItem->setQtyShipped(0); // need that to reset for bloody magento to save shipment
        $documentItem->getOrderItem()->setQtyShipped(0); // need that to reset for bloody magento to save shipment
        return $res;
    }
    
    protected function _setNewOrderItemQuantity ( $magentoOrderItem, $newVal ) {
        $magentoOrderItem->setQtyShipped($newVal);
    }
    
}

?>

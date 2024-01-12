<?php

abstract class Schracklive_SchrackSales_Model_Order_Api_DocumentHandler {
    protected $_soapOrder, $_soapItems, $_magentoOrder;
    protected $_document = null;
    protected $_convertor;
    protected $_newQuantities = array();

    private   $_keyToOrderItemMap = null;
    
    abstract public function find ();
    
    abstract protected function _convertOrderToDocument ();
    abstract protected function _convertOrderItemToDocumentItem ( $item );
    abstract protected function _setInitialSpecificDocumentAttributes ();
    abstract protected function _setUpdateSpecificDocumentAttributes ();
    abstract protected function _setUpdateSpecificItemAttributes ( $documentItm, $soapItem );
    abstract protected function _errorNoOrderItem ( $sku );
    abstract protected function _errorNoDocumentItem ( $sku );
    
    static public function mkKeyToItemMap ( $itemCollection ) {
        $res = array();
        foreach ( $itemCollection as $item ) {
            $key = self::mkMagentoItemKey($item);
            $res[$key] = $item;
        }
        return $res;
    }

    static public function mkMagentoItemKey ( $item ) {
        if ( is_numeric($item->getSchrackPosition()) ) {
            return self::mkItemKey($item->getSchrackPosition(), $item->getSku());
        } else {
            return $item->getSku();
        }
    }

    static public function mkSoapItemKey ( $item ) {
        return self::mkItemKey($item->Position,$item->Sku);
    }

    static private function mkItemKey ( $pos, $sku ) {
        $res = sprintf('%04d-%s',$pos,$sku);
        // return $sku; // hotfix
        return $res;
    }
    
    
    protected function _handleOldAndReturnNewItemQuantity ( $magentoOrderItem, $soapItem, $documentItem ) {
        return $soapItem->Qty;
    }
    
    protected function _setNewOrderItemQuantity ( $magentoOrderItem, $newVal ) {
        // do nothing as default...
    }
    
    protected function __construct ( $soapOrder, $soapItems, $magentoOrder, $convertor ) {
        $this->_soapOrder    = $soapOrder;
        $this->_soapItems    = $soapItems;
        $this->_magentoOrder = $magentoOrder;
        $this->_convertor    = $convertor;
    }

    protected function _findImpl ( $modelName, $docFieldName, $docFieldVal ) {
        $collection = Mage::getResourceModel($modelName);
        $collection->addAttributeToFilter('order_id',$this->_magentoOrder->getEntityId());
        $collection->addAttributeToFilter($docFieldName,$docFieldVal);
        $collection->getSelect();
        foreach ( $collection as $tmpDocument ) {
            if ( $this->checkPositionsAndDeleteIfNecessary($tmpDocument) ) {
                $this->_document = $tmpDocument;
                return $this->_document;
            }
        } 
        return false;
    }

    protected function checkPositionsAndDeleteIfNecessary ( $document ) {
        $hasDifferences = false;
        $hasSame = false;
        $magentoPos = array();
        foreach ( $document->getAllItems() as $item ) {
            $magentoPos[] = intval($item->getSchrackPosition());
        }
        $soapPos = array();
        foreach ( $this->_soapItems as $soapItem ) {
            if ( floatval($soapItem->Qty) > 0 ) {
                $soapPos[] = intval($soapItem->Position);
            }
        }
        $l = count($magentoPos);
        if ( $l !== count($soapPos) ) {
            $hasDifferences = true;
        }
        for ( $i = 0; $i < $l; $i++ ) {
            if ( $magentoPos[$i] ===  $soapPos[$i] ) {
                $hasSame = true;
            } else {
                $hasDifferences = true;
            }
        }
        if ( $hasSame && $hasDifferences ) {
            $document->delete();
            return false;
        }
        return true;
    }

    public function create () {
        $newQuantities = array();
        $this->_document = $this->_convertOrderToDocument();
        $this->_document->setSchrackWwsOrderNumber($this->_soapOrder->OriginalOrderNumber);
        $this->_document->setSchrackWwsReference($this->_soapOrder->Reference);
        $now = Schracklive_SchrackSales_Model_Order_Api_V2::now();
        $this->_document->setCreatedAt($now);
        $this->_document->setUpdatedAt($now);

        $this->_setInitialSpecificDocumentAttributes();
                
        $magentoOrderItems = $this->_getKeyToOrderItemMap();
        foreach ( $this->_soapItems as $soapItem ) {
            if ( intval($soapItem->Qty) === 0 ) {
                continue;
            }
            $key = $soapItem->key;
            $magentoOrderItem = $magentoOrderItems[$key];
            if ( ! isset($magentoOrderItem) ) {
                if ( ! $this->_magentoOrder->getSchrackIsComplete() ) {
                    continue;
                }
                $this->_errorNoOrderItem($soapItem->Sku);
            }
            $item = $this->_convertOrderItemToDocumentItem($magentoOrderItem);
            $newQuantities[$key] = $this->_handleOldAndReturnNewItemQuantity($magentoOrderItem,$soapItem,$item);
            $item->setQty(abs($soapItem->Qty));
            $item->setSchrackPosition($soapItem->Position);
            $this->_document->addItem($item);
            $item->isDeleted(0);
        }
        if ( count($this->_document->getItemsCollection()) == 0 ) {
            return null;
        }
        $this->_document->register();
        foreach ( $this->_document->getItemsCollection() as $item ) {
            $item->isDeleted(0);
        }
        $this->_document->save();
        foreach ( $newQuantities as $key => $newVal ) {
            $magentoOrderItem = $magentoOrderItems[$key];
            $this->_setNewOrderItemQuantity($magentoOrderItem,$newVal);
        }
        
        $this->update();
        return $this->_document;
    }

    public function update () {
        $this->_setUpdateSpecificDocumentAttributes();
        $newQuantities = array();
        $totalQty = 0;
        
        $documentItemMap = self::mkKeyToItemMap($this->_document->getItemsCollection());
        $orderItemMap = $this->_getKeyToOrderItemMap();
        foreach ( $this->_soapItems as $soapItem ) {
            if ( intval($soapItem->Qty) === 0 ) {
                continue;
            }
            $key = $soapItem->key;
            $documentItem = $documentItemMap[$key];
            if ( ! isset($documentItem) ) {
                if ( ! $this->_magentoOrder->getSchrackIsComplete() ) {
                    continue;
                }
                $this->_errorNoDocumentItem($soapItem->Sku);
            }
            
            $magentoOrderItem = $orderItemMap[$key];
            if ( ! isset($magentoOrderItem) ) {
                if ( ! $this->_magentoOrder->getSchrackIsComplete() ) {
                    continue;
                }
                $this->_errorNoOrderItem($soapItem->Sku);
            }
            $newQuantities[$key] = $this->_handleOldAndReturnNewItemQuantity($magentoOrderItem,$soapItem,$documentItem);
            $documentItem->setRowTotal($soapItem->AmountNet);
            $documentItem->setPrice($soapItem->Price);
            $documentItem->setWeight($soapItem->ProductWeight);

            $qty = abs($soapItem->Qty);

            if ( $this instanceof Schracklive_SchrackSales_Model_Order_Api_CreditMemoHandler ) {
                $orderItem = $documentItem->getOrderItem();
                if ( $qty > $orderItem->getQtyToRefund() ) {
                    $orderItem->setQtyInvoiced($qty);
                }
            }

            $documentItem->setQty($qty);
            $documentItem->setName($soapItem->Description);
            if ( isset($soapItem->DrumShortDesc) ) {
                $documentItem->setDescription($soapItem->DrumShortDesc);
            }
            $this->_setUpdateSpecificItemAttributes($documentItem,$soapItem);
            $totalQty += $soapItem->Qty;
            $documentItem->isDeleted(0);
        }
        $this->_setTotalQty($totalQty);
        $this->_document->save();
        
        foreach ( $newQuantities as $key => $newVal ) {
            $magentoOrderItem = $orderItemMap[$key];
            $this->_setNewOrderItemQuantity($magentoOrderItem,$newVal);
        }
    }
    
    protected function _setTotalQty ( $qty ) {
        $this->_document->setTotalQty($qty);
    }
    
    private function _getKeyToOrderItemMap () {
        if ( ! isset($this->_keyToOrderItemMap) ) {
            $this->_keyToOrderItemMap = self::mkKeyToItemMap($this->_magentoOrder->getItemsCollection());
        }
        return $this->_keyToOrderItemMap;
    }
    
}


?>

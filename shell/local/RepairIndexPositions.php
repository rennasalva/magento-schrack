<?php

require_once 'shell.php';
 
class Schracklive_Shell_RepairIndexPositions extends Schracklive_Shell {
    
    public function run() {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $indexModel = Mage::getModel('schracksales/order_index');
        $indexCollection = $indexModel->getCollection();
        $indexCollection->addFieldToFilter('shipment_id',          array('null' => true));
        $indexCollection->addFieldToFilter('invoice_id',           array('null' => true));
        $indexCollection->addFieldToFilter('credit_memo_id',       array('null' => true));
        $indexCollection->addFieldToFilter('is_offer',             0);
        $indexCollection->addFieldToFilter('is_order_confirmation',0);
        $indexCollection->addFieldToFilter('is_processing',        0);
        
        foreach ( $indexCollection as $ndx ) {
            $orderId = $ndx->getOrderId();
            $order = Mage::getModel('sales/order');
            $order->load($orderId);
            $orderItems = $order->getItemsCollection();
            $indexPositionModel = Mage::getModel('schracksales/order_index_position');
            $ndxPosCollection = $indexPositionModel->getCollection();
            $ndxPosCollection->addFieldToFilter('parent_id',$ndx->getEntityId());
            $write->beginTransaction();
            try {
                $changed = $this->updateIndexPositions($orderItems,$ndx,$ndxPosCollection);
                if ( $changed ) {
                    $write->commit();
                    echo 'C';
                }
                else {
                    $write->rollback();
                    echo '.';
                }
            } catch ( Exception $ex ) {
                $write->rollback();
                echo 'ERROR: '.$ex.PHP_EOL;
                // $ex->getTraceAsString();
                die();
            }
        }
        
        echo PHP_EOL.'done.'.PHP_EOL;
    }

    function updateIndexPositions($orderItems,$ndx,$ndxPosCollection) {
        $changed = false;
        $ndxPosArray = array();
        foreach ( $ndxPosCollection as $ndxPos ) {
            $ndxPosArray[$ndxPos->getPosition()] = $ndxPos;
        }
        
        foreach ( $orderItems as $orderItem ) {
            $pos = $orderItem->getSchrackPosition();
            $ndxPos = $ndxPosArray[$pos];
            if ( isset($ndxPos) ) {
                continue;
            }
            $indexPositionModel = Mage::getModel('schracksales/order_index_position');
            $indexPositionModel->setPosition($pos);
            $indexPositionModel->setParentId($ndx->getEntityId());
            $indexPositionModel->setSku($orderItem->getSku());
            $indexPositionModel->setDescription($orderItem->getName());
            $indexPositionModel->save();
            $changed = true;
        }
        
        return $changed;
    }
    
}

$shell = new Schracklive_Shell_RepairIndexPositions();
$shell->run();
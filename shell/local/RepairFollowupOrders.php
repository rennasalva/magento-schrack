<?php

require_once 'shell.php';

class Schracklive_Shell_RepairFollowupOrders extends Schracklive_Shell {
    
    const ORDER_NO_NDX = 0;
    const ORIGINAL_ORDER_NO_NDX = 1;
    const ORDER_DATE_NDX = 2;
    const OFFER_NO_NDX = 3;
    const OFFER_DATE_NDX = 4;
    const SHIPMENT_NO_NDX = 5;
    const SHIPMENT_DATE_NDX = 6;
    const INVOICE_NO_NDX = 7;
    const INVOICE_DATE_NDX = 8;
    
    const ONLY_CREDITMEMOS = 1;

    public function run() {
        echo 'running Schracklive_Shell_RepairFollowupOrders...'.PHP_EOL;
        
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $shopOrderNums = $this->_getAllWwsOrderNums();
        echo count($shopOrderNums) . " ordernums read.".PHP_EOL;
        
        $fileName = $this->getArg('file');
        echo "Processing file $fileName ...".PHP_EOL;
        $cnt = 0;

        if ( ($handle = fopen($fileName, "r")) !== false ) {
            $i = 0;
            while ( ($data = fgetcsv($handle, 1000, ";")) !== false ) {
                $origOrderNo = $data[self::ORIGINAL_ORDER_NO_NDX];
                $orderNo     = $data[self::ORDER_NO_NDX];
                $orderDt     = $data[self::ORDER_DATE_NDX];
                $offerNo     = $data[self::OFFER_NO_NDX];
                $offerDt     = $data[self::OFFER_DATE_NDX];
                $shipmentNo  = $data[self::SHIPMENT_NO_NDX];
                $shipmentDt  = $data[self::SHIPMENT_DATE_NDX];
                $invoiceNo   = $data[self::INVOICE_NO_NDX];
                $invoiceDt   = $data[self::INVOICE_DATE_NDX];
                if ( $origOrderNo === $orderNo ) {
                    continue;
                }
                
                if ( array_key_exists($orderNo,$shopOrderNums) ) {
                    $write->beginTransaction();
                    echo $orderNo.': ';
                    ++$cnt;
                    try {
                        $order = $this->_getExistingOrder($orderNo);
                        $indices = $this->_getExistingIndices($order);
                        foreach ( $indices as $index ) {
                            $positionModel = Mage::getModel('schracksales/order_index_position');
                            $positionCollection = $positionModel->getCollection();
                            $positionCollection->addFieldToFilter('parent_id',$index->getEntityId());
                            foreach ( $positionCollection as $position ) {
                                $position->delete();
                                echo 'p';
                            }
                            $index->delete();
                            echo 'x';
                        }
                        $order->delete();
                        echo 'o';
                        $write->commit();
                        echo PHP_EOL;
                    } catch ( Exception $ex ) {
                        $write->rollback();
                        echo 'ERROR: '.$ex.PHP_EOL;
                    }
                }
                
            }
            fclose($handle);
        }

        echo $cnt.' wrong followups found.'.PHP_EOL.'done.'.PHP_EOL;
    }

    private function _getAllWwsOrderNums () {
        $orderModel = Mage::getModel('sales/order');
        $orderCollection = $orderModel->getCollection();
        $orderCollection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('schrack_wws_order_number');
        $res = array();
        foreach ( $orderCollection as $order ) {
            $res[$order->getSchrackWwsOrderNumber()] = true;
        }
        return $res;
    }
    
    private function _getExistingOrder ( $orderNumber ) {
        $orderModel = Mage::getModel('sales/order');
        $orderCollection = $orderModel->getCollection();
        $orderCollection->addFieldToFilter('schrack_wws_order_number',$orderNumber);
        $orderCollection->getSelect();
        if ( $orderCollection->getSize() > 0 ) {
            return $orderCollection->getFirstItem();
        }
        return null;
    }
    
    private function _getExistingIndices ( $order ) {
        $indexModel = Mage::getModel('schracksales/order_index');
        $indexCollection = $indexModel->getCollection();
        $indexCollection->addFieldToFilter('order_id',$order->getEntityId());
        $res = array();
        foreach ( $indexCollection as $index ) {
            $res[] = $index;
        }
        return $res;
    }
    
    private function _formatDate ( $srcDate ) {
        $srcDate = trim($srcDate);
        $dt = DateTime::createFromFormat('d/m/y',$srcDate);
        $res = $dt->format('Y-m-d');
        return $res;
    }
}

$shell = new Schracklive_Shell_RepairFollowupOrders();
$shell->run();

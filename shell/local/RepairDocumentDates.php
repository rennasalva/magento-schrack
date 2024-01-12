<?php

require_once 'shell.php';

class Schracklive_Shell_RepairDocumentDates extends Schracklive_Shell {
    
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
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $fileName = $this->getArg('file');
        echo "Processing file $fileName ...".PHP_EOL;

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
                
                echo PHP_EOL.$origOrderNo.': ';

                if ( self::ONLY_CREDITMEMOS && ($shipmentNo || ! $invoiceNo) ) {
                    echo '-';
                    continue;
                }
                $order = $this->_getExistingOrder($origOrderNo);
                if ( ! $order ) {
                    echo '-';
                    continue;
                }
                $indices = $this->_getExistingIndices($order);
                
                /*
                echo "OrigOrderNo:" . $origOrderNo . PHP_EOL;
                echo "OrderNo:"     . $orderNo     . PHP_EOL;
                echo "OrderDt:"     . $orderDt     . PHP_EOL;
                echo "OfferNo:"     . $offerNo     . PHP_EOL;
                echo "OfferDt:"     . $offerDt     . PHP_EOL;
                echo "ShipmentNo:"  . $shipmentNo  . PHP_EOL;
                echo "ShipmentDt:"  . $shipmentDt  . PHP_EOL;
                echo "InvoiceNo:"   . $invoiceNo   . PHP_EOL;
                echo "InvoiceDt:"   . $invoiceDt   . PHP_EOL;
                echo PHP_EOL;
                */
                
                $write->beginTransaction();
                try {
                    if ( ! self::ONLY_CREDITMEMOS && $origOrderNo === $orderNo ) {
                        $order->setSchrackWwsCreationDate($this->_formatDate($orderDt));
                        $order->save();
                        echo 'o';
                        foreach ( $indices as $index ) {
                            if ( ! $index->getShipmentId() && ! $index->getInvoiceId() && ! $index->getCreditMemoId() && ! $index->getIsOffer() ) {
                                $index->setDocumentDateTime($this->_formatDate($orderDt));
                                $index->save();
                                echo 'i';
                            }
                        }
                    }
                    
                    if ( ! self::ONLY_CREDITMEMOS && isset($offerNo) && strlen($offerNo) > 2 ) {
                        $order->setSchrackWwsOfferDate($this->_formatDate($offerDt));
                        $order->save();
                        echo 'f';
                        foreach ( $indices as $index ) {
                            if ( $index->getIsOffer() ) {
                                $index->setDocumentDateTime($this->_formatDate($offerDt));
                                $index->save();
                                echo 'i';
                                break;
                            }
                        }
                    }
                    
                    if ( ! self::ONLY_CREDITMEMOS && isset($shipmentNo) && strlen($shipmentNo) > 2 ) {
                        $shipments = $order->getShipmentsCollection();
                        foreach ( $shipments as $shipment ) {
                            if ( $shipment->getSchrackWwsShipmentNumber() === $shipmentNo ) {
                                $shipment->setSchrackWwsDocumentDate($this->_formatDate($shipmentDt));
                                $shipment->save();
                                echo 's';
                                foreach ( $indices as $index ) {
                                    if ( $index->getShipmentId() === $shipment->getEntityId() ) {
                                        $index->setDocumentDateTime($this->_formatDate($shipmentDt));
                                        $index->save();
                                        echo 'i';
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }
                    
                    $noInvoice = true;
                    if ( ! self::ONLY_CREDITMEMOS && isset($invoiceNo) && strlen($invoiceNo) > 2 ) {
                        $invoices = $order->getInvoiceCollection();
                        foreach ( $invoices as $invoice ) {
                            if ( $invoice->getSchrackWwsInvoiceNumber() === $invoiceNo ) {
                                $invoice->setSchrackWwsDocumentDate($this->_formatDate($invoiceDt));
                                $invoice->save();
                                $noInvoice = false;
                                echo 'v';
                                foreach ( $indices as $index ) {
                                    if ( $index->getInvoiceId() === $invoice->getEntityId() ) {
                                        $index->setDocumentDateTime($this->_formatDate($invoiceDt));
                                        $index->save();
                                        echo 'i';
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }
                    
                    if ( $noInvoice ) { // is credit memo
                        $creditMemos = $order->getCreditmemosCollection();
                        foreach ( $creditMemos as $creditMemo ) {
                            if ( $creditMemo->getSchrackWwsCreditmemoNumber() === $invoiceNo ) {
                                $creditMemo->setSchrackWwsDocumentDate($this->_formatDate($invoiceDt));
                                $creditMemo->save();
                                echo 'c';
                                foreach ( $indices as $index ) {
                                    if ( $index->getCreditMemoId() === $creditMemo->getEntityId() ) {
                                        $index->setDocumentDateTime($this->_formatDate($invoiceDt));
                                        $index->save();
                                        echo 'i';
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }
                    
                    $write->commit();
                } catch ( Exception $ex ) {
                    $write->rollback();
                    echo 'ERROR: '.$ex.PHP_EOL;
                    // $ex->getTraceAsString();
                }
                
            }
            fclose($handle);
        }

        echo PHP_EOL.'done.'.PHP_EOL;
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

$shell = new Schracklive_Shell_RepairDocumentDates();
$shell->run();

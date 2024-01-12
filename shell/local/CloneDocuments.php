<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_CloneDocuments extends Mage_Shell_Abstract {

    private $sourceCustomerID = '';
    private $destCustomerID = '';
    private $fromDate = '';
    private $toDate = '';
    private $read;
    private $write;
    private $destCustomer;
    private $dbName;
    private $eavConfig;
    private $storeId;
    private $amountMultiplier = 4;

    function __construct() {
        parent::__construct();
        $this->sourceCustomerID = $this->getArg('source_customer');
        $this->destCustomerID = $this->getArg('dest_customer');
        $this->fromDate = $this->getArg('from');
        $this->toDate = $this->getArg('to');
        if ( ! $this->sourceCustomerID || ! $this->destCustomerID ) {
            $this->showUsage();
        }
        if ( ! $this->fromDate ) {
            $this->fromDate = '2010-01-01';
        }
        if ( ! $this->toDate ) {
            $this->toDate = '2100-01-01';
        }
        $this->read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->dbName = $this->write->getConfig()['dbname'];
        $this->eavConfig = Mage::getSingleton('eav/config');
        $this->storeId = Mage::app()->getStore('default')->getId();
    }

    public function run () {
        $this->destCustomer = Mage::helper('account')->getSystemContactByWwsCustomerId($this->destCustomerID);
        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addAttributeToSelect('*');
        $orderCollection->addAttributeToFilter('schrack_wws_customer_id',$this->sourceCustomerID);
        $orderCollection->addAttributeToFilter('schrack_wws_creation_date',array('gteq' => $this->fromDate));
        $orderCollection->addAttributeToFilter('schrack_wws_creation_date',array('lteq' => $this->toDate));
        $orderCollection->addAttributeToSort('schrack_wws_creation_date');
        foreach ( $orderCollection as $srcOrder ) {
            $this->cloneOrder($srcOrder);
        }
        echo 'done.' . PHP_EOL;
    }

    private function cloneOrder ( Schracklive_SchrackSales_Model_Order $order ) {
        if ( substr( $order->getSchrackWwsOrderNumber(),0,1) == 'D' ) {
            return;
        }
        $this->write->beginTransaction();
        try {
            $this->deleteClonedOrder($order);
            echo 'ORDER: ' . $order->getSchrackWwsCreationDate() . ' ' . $order->getSchrackWwsOrderNumber() . PHP_EOL;
            $replacements = array('customer_id'         => $this->destCustomer->getEntityId(),
                                  'increment_id'        => $this->eavConfig->getEntityType('order')->fetchNewIncrementId($this->storeId),
                                  'billing_address_id'  => $this->destCustomer->getDefaultBilling(),
                                  'quote_address_id'    => $this->destCustomer->getDefaultBilling(),
                                  'shipping_address_id' => $this->destCustomer->getDefaultShipping(),
                                  'schrack_wws_customer_id' => $this->destCustomerID);
            $newOrderId = $this->sqlClone('sales_flat_order','entity_id',$order->getId(),$replacements);
            $indexIDs = $this->cloneIndex($order->getId(),$newOrderId);
            foreach ( $order->getItemsCollection() as $item ) {
                $newItemId = $this->sqlClone('sales_flat_order_item','item_id',$item->getId(),array('order_id' => $newOrderId));
                $this->cloneIndexPosition($indexIDs['old'],$indexIDs['new'],$item->getSchrackPosition());
            }

            foreach ( $order->getShipmentsCollection() as $shipment ) {
                echo '  SHIPMENT: ' . $shipment->getSchrackWwsDocumentDate() . ' ' . $shipment->getSchrackWwsShipmentNumber() . PHP_EOL;
                $replacements = array('order_id'            => $newOrderId,
                                      'increment_id'        => $this->eavConfig->getEntityType('shipment')->fetchNewIncrementId($this->storeId),
                                      'schrack_wws_parcels' => "''",
                                      'billing_address_id'  => $this->destCustomer->getDefaultBilling(),
                                      'shipping_address_id' => $this->destCustomer->getDefaultShipping());
                $newShipmentId = $this->sqlClone('sales_flat_shipment','entity_id',$shipment->getId(),$replacements);
                $this->cloneIndex($order->getId(),$newOrderId,$shipment->getId(),$newShipmentId);
                foreach ( $shipment->getItemsCollection() as $item ) {
                    $this->sqlClone('sales_flat_shipment_item','entity_id',$item->getId(),array('parent_id' => $newShipmentId));
                }
            }
            foreach ( $order->getInvoiceCollection() as $invoice ) {
                echo '  INVOICE: ' . $invoice->getSchrackWwsDocumentDate() . ' ' . $invoice->getSchrackWwsInvoiceNumber() . PHP_EOL;
                $replacements = array('order_id'            => $newOrderId,
                                      'increment_id'        => $this->eavConfig->getEntityType('invoice')->fetchNewIncrementId($this->storeId),
                                      'billing_address_id'  => $this->destCustomer->getDefaultBilling(),
                                      'shipping_address_id' => $this->destCustomer->getDefaultShipping());
                $newInvoiceId = $this->sqlClone('sales_flat_invoice','entity_id',$invoice->getId(),$replacements);
                $this->cloneIndex($order->getId(),$newOrderId,null,null,$invoice->getId(),$newInvoiceId);
                foreach ( $invoice->getItemsCollection() as $item ) {
                    $this->sqlClone('sales_flat_invoice_item','entity_id',$item->getId(),array('parent_id' => $newInvoiceId));
                }
            }
            foreach ( $order->getCreditmemosCollection() as $creditmemo ) {
                echo '  CREDITMEMO: ' . $creditmemo->getSchrackWwsDocumentDate() . ' ' . $creditmemo->getSchrackWwsCreditmemoNumber() . PHP_EOL;
                $replacements = array('order_id'            => $newOrderId,
                                      'increment_id'        => $this->eavConfig->getEntityType('invoice')->fetchNewIncrementId($this->storeId),
                                      'billing_address_id'  => $this->destCustomer->getDefaultBilling(),
                                      'shipping_address_id' => $this->destCustomer->getDefaultShipping());
                $newCreditmemoId = $this->sqlClone('sales_flat_creditmemo','entity_id',$creditmemo->getId(),$replacements);
                $this->cloneIndex($order->getId(),$newOrderId,null,null,null,null,$creditmemo->getId(),$newCreditmemoId);
                foreach ( $creditmemo->getItemsCollection() as $item ) {
                    $this->sqlClone('sales_flat_creditmemo_item','entity_id',$item->getId(),array('parent_id' => $newCreditmemoId));
                }
            }
            $this->write->commit();
        } catch ( Exception $ex ) {
            $this->write->rollback();
            echo 'Exception thrown: ' . $ex->getMessage() . PHP_EOL;
            echo 'Trace:' .PHP_EOL;
            echo $ex->getTraceAsString() . PHP_EOL . PHP_EOL;
        }
    }

    private function deleteClonedOrder ( Schracklive_SchrackSales_Model_Order $order ) {
        $orderNo = $order->getSchrackWwsOrderNumber();
        $orderNo = 'D' . substr($orderNo,1);
        $from = " FROM sales_flat_order WHERE schrack_wws_order_number = '$orderNo';";
        $sql = "SELECT COUNT(*)" . $from;
        $cnt = $this->read->fetchOne($sql);
        if ( $cnt > 1 ) {
            throw new Exception("More than one possible deletes for order number $orderNo");
        } else if ( $cnt == 1 ) {
            $sql = "DELETE" . $from;
            $this->write->query($sql);
        }
    }

    private function sqlClone ( $tableName, $keyName, $srcKeyValue, $changeFiledValMap = array() ) {
        $fields = $this->getFieldsWithoutPrimaryKey($tableName,$keyName,$changeFiledValMap);
        $sql = "INSERT INTO $tableName SELECT $fields FROM $tableName WHERE $keyName = $srcKeyValue;";
        $this->write->query($sql);
        $sql2 = "SELECT $keyName FROM $tableName ORDER BY $keyName DESC LIMIT 1";
        $res = $this->write->fetchOne($sql2);
        return $res;
    }

    private function getFieldsWithoutPrimaryKey ( $tableName, $keyName, $changeFiledValMap ) {
        $res = null;
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$this->dbName' AND TABLE_NAME = '$tableName';";
        $fields = $this->read->fetchAll($sql);
        foreach ( $fields as $field ) {
            $s = $field['COLUMN_NAME'];
            if ( $s === $keyName ) {
                $s = 'NULL';
            }
            else if ( isset($changeFiledValMap[$s]) ) {
                $s = "'".$changeFiledValMap[$s]."'";
            }
            else if ( isset($this->amountFields[$s]) ) {
                $s = "IF($s IS NULL,NULL,$s * {$this->amountMultiplier})";
            } else if ( isset($this->schrackIdFields[$s]) ) {
                $s = "IF($s IS NULL,NULL,CONCAT('D',SUBSTRING($s,2)))";
            } else if ( isset($this->replaceTextRandimlyFieldMap[$s]) ) {
                $cnt = count($this->replaceTextRandimlyFieldMap[$s]);
                $s = "'" . $this->replaceTextRandimlyFieldMap[$s][rand(0,--$cnt)] . "'";
            }
            if ( $res == null ) {
                $res = $s;
            } else {
                $res .= ', ';
                $res .= $s;
            }
        }
        return $res;
    }

    private function cloneIndexPosition ( $parentId, $newParentId, $position ) {
        $sql = "SELECT entity_id FROM sales_flat_order_schrack_index_position WHERE parent_id = $parentId AND position = $position;";
        $fields = $this->read->fetchCol($sql);
        if ( count($fields) != 1 ) {
            throw new Exception("Count of index position entries {count($fields)} <> 1 !");
        }
        // $keyName, $srcKeyValue, $changeFiledValMap = array() ) {
        $this->sqlClone('sales_flat_order_schrack_index_position','entity_id',$fields[0],array('parent_id' => $newParentId));
    }

    private function cloneIndex ( $orderId, $newOrderId, $shipmentId = null, $newShipmentId = null, $invoiceId = null, $newInvoiceId = null, $creditmemoId = null, $newCreditmemoId = null ) {
        $res = array();
        $newVals = array(
            'order_id' => $newOrderId,
            'shipment_id' => $newShipmentId,
            'invoice_id' => $newInvoiceId,
            'credit_memo_id' => $newCreditmemoId,
            'wws_customer_id' => $this->destCustomerID
        );
        $oldIDs = $this->findIndexIDs($orderId,$shipmentId,$invoiceId,$creditmemoId);
        foreach ( $oldIDs as $oldID ) {
            if ( ! isset($res['old']) ) {
                $res['old'] = $oldID;
            }
            $newID = $this->sqlClone('sales_flat_order_schrack_index', 'entity_id', $oldID, $newVals);
            if ( ! isset($res['new']) ) {
                $res['new'] = $newID;
            }
        }
        return $res;
    }

    private function findIndexIDs ( $orderId, $shipmentId = null, $invoiceId = null, $creditmemoId = null ) {
        $shipmentIdX = $shipmentId     ? '= ' . $shipmentId   : 'IS NULL';
        $invoiceIdX  = $invoiceId      ? '= ' . $invoiceId    : 'IS NULL';
        $creditmemoIdX = $creditmemoId ? '= ' . $creditmemoId : 'IS NULL';
        $sql = "SELECT entity_id FROM sales_flat_order_schrack_index WHERE order_id = $orderId AND shipment_id $shipmentIdX AND invoice_id $invoiceIdX AND credit_memo_id $creditmemoIdX";
        $indexIDs = $this->read->fetchCol($sql);
        return $indexIDs;
    }

    private function showUsage () {
        echo 'Usage: php CloneDocuments.php --source_customer <ID> --dest_customer <ID> [--from <YYYY-MM-DD>] [--to <YYYY-MM-DD>]' . PHP_EOL . PHP_EOL;
        echo 'default for --from = 2010-01-01' . PHP_EOL;
        echo 'default for --to   = 2100-01-01' . PHP_EOL;
        die();
    }

    private $schrackIdFields = array(
        'schrack_wws_offer_number' => true,
        'schrack_wws_order_number' => true,
        'schrack_wws_shipment_number' => true,
        'schrack_wws_invoice_number' => true,
        'schrack_wws_creditmemo_number' => true,
        'wws_document_number' => true,
        'wws_followup_order_number' => true,
    );

    private $replaceTextRandimlyFieldMap = array(
        'schrack_wws_reference' => array('Baustelle Hinterholz', 'Lager', 'Auftrag 4711', 'Bestellung 0815', 'Zu Handen Herrn Mustermann')
    );

    private $amountFields = array(
        'amount_refunded' => true,
        'base_adjustment_negative' => true,
        'base_adjustment_positive' => true,
        'base_amount_refunded' => true,
        'base_cod_fee' => true,
        'base_cod_fee_invoiced' => true,
        'base_cod_tax_amount' => true,
        'base_cod_tax_amount_invoiced' => true,
        'base_cost' => true,
        'base_discount_amount' => true,
        'base_discount_canceled' => true,
        'base_discount_invoiced' => true,
        'base_discount_refunded' => true,
        'base_grand_total' => true,
        'base_hidden_tax_amount' => true,
        'base_hidden_tax_invoiced' => true,
        'base_hidden_tax_refunded' => true,
        'base_original_price' => true,
        'base_price' => true,
        'base_price_incl_tax' => true,
        'base_row_invoiced' => true,
        'base_row_total' => true,
        'base_row_total_incl_tax' => true,
        'base_shipping_amount' => true,
        'base_shipping_canceled' => true,
        'base_shipping_discount_amount' => true,
        'base_shipping_hidden_tax_amount' => true,
        'base_shipping_incl_tax' => true,
        'base_shipping_invoiced' => true,
        'base_shipping_refunded' => true,
        'base_shipping_tax_amount' => true,
        'base_shipping_tax_refunded' => true,
        'base_subtotal' => true,
        'base_subtotal_canceled' => true,
        'base_subtotal_incl_tax' => true,
        'base_subtotal_invoiced' => true,
        'base_subtotal_refunded' => true,
        'base_tax_amount' => true,
        'base_tax_before_discount' => true,
        'base_tax_canceled' => true,
        'base_tax_invoiced' => true,
        'base_tax_refunded' => true,
        'base_to_global_rate' => true,
        'base_to_order_rate' => true,
        'base_total_canceled' => true,
        'base_total_due' => true,
        'base_total_invoiced' => true,
        'base_total_invoiced_cost' => true,
        'base_total_offline_refunded' => true,
        'base_total_online_refunded' => true,
        'base_total_paid' => true,
        'base_weee_tax_applied_amount' => true,
        'base_weee_tax_applied_row_amount' => true,
        'base_weee_tax_disposition' => true,
        'base_weee_tax_row_disposition' => true,
        'cod_fee' => true,
        'cod_fee_invoiced' => true,
        'cod_tax_amount' => true,
        'cod_tax_amount_invoiced' => true,
        'discount_amount' => true,
        'discount_invoiced' => true,
        'discount_percent' => true,
        'grand_total' => true,
        'hidden_tax_amount' => true,
        'hidden_tax_canceled' => true,
        'hidden_tax_invoiced' => true,
        'hidden_tax_refunded' => true,
        'original_price' => true,
        'payment_authorization_amount' => true,
        'price' => true,
        'price_incl_tax' => true,
        'row_invoiced' => true,
        'row_total' => true,
        'row_total_incl_tax' => true,
        'schrack_basic_price' => true,
        'schrack_row_total_excl_surcharge' => true,
        'schrack_row_total_surcharge' => true,
        'schrack_surcharge' => true,
        'schrack_tax_total' => true,
        'shipping_amount' => true,
        'shipping_discount_amount' => true,
        'shipping_hidden_tax_amount' => true,
        'shipping_incl_tax' => true,
        'shipping_tax_amount' => true,
        'shipping_tax_refunded' => true,
        'store_to_base_rate' => true,
        'store_to_order_rate' => true,
        'subtotal' => true,
        'subtotal_canceled' => true,
        'subtotal_incl_tax' => true,
        'subtotal_invoiced' => true,
        'subtotal_refunded' => true,
        'tax_amount' => true,
        'tax_before_discount' => true,
        'tax_canceled' => true,
        'tax_invoiced' => true,
        'tax_refunded' => true,
        'total_canceled' => true,
        'total_due' => true,
        'total_invoiced' => true,
        'total_offline_refunded' => true,
        'total_online_refunded' => true,
        'total_paid' => true,
        'total_qty_ordered' => true,
        'total_refunded' => true,
        'weee_tax_applied_amount' => true,
        'weee_tax_applied_row_amount' => true,
        'weee_tax_disposition' => true,
        'weee_tax_row_disposition' => true,
    );
}

(new Schracklive_Shell_CloneDocuments())->run();

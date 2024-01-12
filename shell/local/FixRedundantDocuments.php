<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixRedundantDocuments extends Mage_Shell_Abstract {

    private $readConnection, $writeConnection;
    private $dryRun = true;
    private $customerID = false;

    function __construct () {
        parent::__construct();
        if ( $this->getArg('dry_run') !== false) {
            $this->dryRun = ($this->getArg('dry_run') === '0' ? false : true);
        }
        if ( $this->getArg('customer_id') ) {
            $this->customerID = $this->getArg('customer_id');
        }
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

	public function run() {
        echo PHP_EOL . '==< shipments: >========' . PHP_EOL;
        $this->fix('sales_flat_shipment','shipment_id','schrack_wws_shipment_number');
        echo PHP_EOL . '==< invoices: >========' . PHP_EOL;
        $this->fix('sales_flat_invoice','invoice_id','schrack_wws_invoice_number');
        echo PHP_EOL . '==< creditmemos: >========' . PHP_EOL;
        $this->fix('sales_flat_creditmemo','credit_memo_id','schrack_wws_creditmemo_number');
		echo PHP_EOL . 'done.' . PHP_EOL;
	}

    private function fix ( $table, $fieldInIndex, $numberField ) {
        $sql = " SELECT doc.$numberField as doc_no, ndx.wws_followup_order_number as wws_order_no FROM $table doc"
             . " JOIN sales_flat_order_schrack_index ndx ON ndx.$fieldInIndex = doc.entity_id AND ndx.wws_followup_order_number > 0"
             .($this->customerID ? " WHERE ndx.wws_customer_id = {$this->customerID}" : "")
             . " GROUP BY doc.$numberField, ndx.wws_followup_order_number, ndx.wws_followup_order_number HAVING count(*) > 1;";
        $dbRes = $this->readConnection->fetchAll($sql);
        $docNumberSize = count($dbRes);
        $docNumberCnt = 0;
        $deleteCount = 0;
        $ids2delete = array();
        foreach ( $dbRes as $row ) {
            $number = $row['doc_no'];
            $wwsOrder = $row['wws_order_no'];
            ++$docNumberCnt;
            echo "$table: $number -- wws_order: $wwsOrder ($docNumberCnt/$docNumberSize)" . PHP_EOL;
            $sql = " SELECT $fieldInIndex as entity_id, order_id FROM sales_flat_order_schrack_index"
                 . " WHERE entity_id IS NOT NULL AND wws_document_number = ? AND wws_followup_order_number = ?"
                 . " ORDER BY entity_id desc";
            $res = $this->readConnection->fetchAll($sql,array($number,$wwsOrder));
            $orderIDs2docIDs = array();
            foreach ( $res as $row ) {
                $entityID = $row['entity_id'];
                $orderID  = $row['order_id'];
                $orderIDs2docIDs[$orderID][] = $entityID;
            }
            foreach ( $orderIDs2docIDs as $orderID => $docIDs ) {
                $latestDoc = array_shift($docIDs);
                echo "    keeping $latestDoc (order $orderID)" . PHP_EOL;
                foreach ( $docIDs as $entityID ) {
                    echo "    deleting $entityID (order $orderID)" . PHP_EOL;
                    $ids2delete[] = $entityID;
                    if ( ++$deleteCount == 100 ) {
                        $this->delete($table, $ids2delete, $fieldInIndex);
                        $ids2delete = array();
                        $deleteCount = 0;
                    }
                }
            }
        }
        if ( count($ids2delete) > 0 ) {
            $this->delete($table, $ids2delete, $fieldInIndex);
        }
    }

    private function delete ( $table, array $entityIDs, $fieldInIndex ) {
        $ar = implode(",",$entityIDs);
        $sql1 = "DELETE from $table WHERE entity_id IN ($ar)";
        $sql2 = "DELETE FROM sales_flat_order_schrack_index WHERE $fieldInIndex IN ($ar)";
        if ( $this->dryRun ) {
            echo $sql1 . PHP_EOL;
            echo $sql2 . PHP_EOL;
        } else {
            $this->writeConnection->beginTransaction();
            try {
                $this->writeConnection->query($sql1);
                $this->writeConnection->query($sql2);
                $this->writeConnection->commit();
            } catch ( Exception $ex ) {
                $this->writeConnection->rollback();
                throw $ex;
            }
        }
    }
}

$shell = new Schracklive_Shell_FixRedundantDocuments();
$shell->run();

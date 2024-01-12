<?php

        const DEBUG = false;

require_once 'shell.php';

class Schracklive_Shell_RepairInvoices extends Schracklive_Shell {

    const TYPE_CREDITMEMO = 'creditmemo';
    const TYPE_INVOICE = 'invoice';
    const INVOICE_NO = 'invoice_no';
    const ORDER_NO = 'order_no';
    const FOLLOWUP_ORDER_NO = 'followup_order_no';
    const POSITION = 'position';
    const SKU = 'sku';
    const QUANTITY = 'qty';
    const PRICE = 'price';
    const ROW_TOTAL = 'row_total';
    const ORIG_DATA = 'orig_data';

    var $_importFile;
    var $_type;
    var $_ctry;
    var $_data;

    public function __construct() {
        ini_set('memory_limit', '-1');
        error_reporting(E_ERROR | E_PARSE);
        parent::__construct();
        if ($this->getArg('file')) {
            $this->_importFile = $this->getArg('file');
            $fn = basename($this->_importFile);
            list($type, $rest) = explode('_', $fn);
            $type = trim(strtolower($type));
            if ($type !== self::TYPE_CREDITMEMO && $type !== self::TYPE_INVOICE) {
                throw new Exception('File name must start with "creditmemo_" or "invoice_" !');
            }
            $this->_type = $type;
            $rest2 = explode('.', $rest);
            $this->_ctry = $rest2[0];
        } else {
            $this->usage();
        }
    }

    private function usage() {
        die('Usage: ImportProductStockInfos --file <CVS-File>'.PHP_EOL);
    }

    public function run() {
        $magentoCountryCode = strtolower(Mage::getStoreConfig('general/country/default'));
        if ($magentoCountryCode !== strtolower($this->_ctry)) {
            throw new Exception("File name must have country code $magentoCountryCode (<type>_<country>.csv)");
        }
        
        $this->removeDuplicates();

        $data = array();
        $lastInvNo = null;
        $invNo = null;

        $i = 0;
        if (($handle = fopen($this->_importFile, 'r')) !== FALSE) {
            while (($csvLine = fgetcsv($handle, 2000, ';')) !== FALSE) {
                if ( ++$i % 100 == 0 ) {
                    gc_collect_cycles();
                }
                $invNo = $csvLine[0];
                if ($lastInvNo != null && $invNo != $lastInvNo) {
                    $this->doRec($lastInvNo);
                }
                $lastInvNo = $this->csvLine2data($csvLine);
                unset($csvLine);
            }
            $this->doRec($lastInvNo);
            fclose($handle);
        }
        if ($invNo != null) {
            $this->doRec($invNo);
        }

        echo PHP_EOL.'done.'.PHP_EOL;
    }

    private function doRec($invNo) {
        $this->hackDmatSkus($invNo);
        $this->optimizeNode($invNo);
        $this->correct($invNo);
        unset($this->_data);
    }

    private function hackDmatSkus($invNo) {
        $passThroughMaterialCounts = array();
        $rec = &$this->_data;
        $cnt = 1;
        foreach ($rec as &$line) {
            $sku = $line[self::SKU];
            $ordNo = $line[self::ORDER_NO];
            $key = $ordNo.$sku;
            if (strlen($sku) < 9 && substr($sku, 0, 1) == 'D') {
                if (!isset($passThroughMaterialCounts[$key])) {
                    $passThroughMaterialCounts[$key] = 1;
                } else {
                    ++$passThroughMaterialCounts[$key];
                }
                $sku = sprintf('%s#%03d', $sku, $passThroughMaterialCounts[$key]);
                $line[self::SKU] = $sku;
            }
        }
    }

    private function optimizeNode($invNo) {
        $newRec = array();
        $rec = $this->_data;
        foreach ($rec as $line) {
            $orderNo = $line[self::ORDER_NO];
            $fOrderNo = $line[self::FOLLOWUP_ORDER_NO];
            $sku = $line[self::SKU];
            $qty = $line[self::QUANTITY];
            $price = $line[self::PRICE];
            $rowTotal = $line[self::ROW_TOTAL];
            $this->addData($newRec, $orderNo, $sku, $qty, $price, $rowTotal);
            if ($orderNo !== $fOrderNo) {
                $this->addData($newRec, $fOrderNo, $sku, $qty, $price, $rowTotal);
            }
        }
        if (DEBUG) {
            $newRec[self::ORIG_DATA] = $rec;
        }
        $this->_data = $newRec;
    }

    private function addData(&$newRec, $orderNo, $sku, $qty, $price, $rowTotal) {
        if (!isset($newRec[$orderNo])) {
            $newRec[$orderNo] = array();
        }
        if (!isset($newRec[$orderNo][$sku])) {
            $newRec[$orderNo][$sku] = array();
            $newRec[$orderNo][$sku][self::QUANTITY] = $qty;
            $newRec[$orderNo][$sku][self::PRICE] = $price;
            $newRec[$orderNo][$sku][self::ROW_TOTAL] = $rowTotal;
        } else {
            $newRec[$orderNo][$sku][self::QUANTITY] += $qty;
            $newRec[$orderNo][$sku][self::PRICE] += $price;
            $newRec[$orderNo][$sku][self::ROW_TOTAL] += $rowTotal;
        }
    }

    private function correct($invNo) {
        if (DEBUG) {
            echo $invNo.' ';
        }
        $changed = 0;
        $rec = $this->_data;
        $model = null;
        if ($this->_type === self::TYPE_CREDITMEMO) {
            $modelName = 'sales/order_creditmemo_collection';
            $docFieldName = 'schrack_wws_creditmemo_number';
        } else {
            $modelName = 'sales/order_invoice_collection';
            $docFieldName = 'schrack_wws_invoice_number';
        }
        $collection = Mage::getResourceModel($modelName);
        $collection->addAttributeToFilter($docFieldName, $invNo);
        $collection->getSelect();
        if (DEBUG) {
            if ($collection->count() < 1) {
                echo 'O'.PHP_EOL;
                return;
            }
        }
        foreach ($collection as $document) {
            $cnt = 0;
            $orderNum = null;
            $items = $document->getItemsCollection();
            foreach ($items as $item) {
                ++$cnt;
                if ($orderNum == null) {
                    $orderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                    $orderNum = $orderItem->getOrder()->getSchrackWwsOrderNumber();
                }
                $sku = $item->getSku();

                $line = $rec[$orderNum][$sku];

                if ( isset($line) ) {
                    $item->setData('qty',$line[self::QUANTITY]);
                    $item->setRowTotal($line[self::ROW_TOTAL]);
                    $item->setPrice($line[self::PRICE]);
                    $item->save();
                    ++$changed;
                }
            }
            if (DEBUG) {
                if ($changed < $cnt) {
                    echo $changed.'/'.$cnt.PHP_EOL;
                    echo "magento items:".PHP_EOL;
                    foreach ($items as $item) {
                        $orderItem = Mage::getModel('sales/order_item')->load($item->getOrderItemId());
                        $orderNum = $orderItem->getOrder()->getSchrackWwsOrderNumber();
                        $sku = $item->getSku();
                        $qty = $item->getQty();
                        echo "  orderNum = $orderNum , sku = $sku , qty = $qty".PHP_EOL;
                    }
                    echo "corrections:".PHP_EOL;
                    foreach ($rec[self::ORIG_DATA] as $line) {
                        $orderNum = $line[self::ORDER_NO];
                        $qty = $line[self::QUANTITY];
                        $sku = $line[self::SKU];
                        echo "  orderNum = $orderNum , sku = $sku , qty = $qty".PHP_EOL;
                    }
                }
            } else {
                if ($changed < $cnt) {
                    echo PHP_EOL . $invNo . PHP_EOL;
                }
            }
        }
        if (DEBUG) {
            echo PHP_EOL;
        }
    }

    private function csvLine2data($line) {
        $invoice_no = $line[0];
        $order_no = $line[1];
        $f_order_no = $line[2];
        $position = $line[3];
        $sku = $line[4];
        $quantity = $line[5];
        $price = $line[6];
        $row_total = $line[7];
        $rec = array();
        $rec[self::INVOICE_NO] = $invoice_no;
        $rec[self::ORDER_NO] = $order_no;
        $rec[self::FOLLOWUP_ORDER_NO] = $f_order_no;
        $rec[self::POSITION] = $position;
        $rec[self::SKU] = strtoupper($sku);
        $rec[self::QUANTITY] = $this->prepareNum($quantity);
        $rec[self::PRICE] = $this->prepareNum($price);
        $rec[self::ROW_TOTAL] = $this->prepareNum($row_total);

        if (!isset($this->_data)) {
            $this->_data = array();
        }
        $this->_data[] = $rec;
        return $invoice_no;
    }

    private function prepareNum($strVal) {
        $strVal = str_replace(',', '.', $strVal);
        return floatval($strVal);
    }

    private function removeDuplicates () {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');
        $orderTableName = $resource->getTableName('sales_flat_order');
        $query = "select schrack_wws_order_number from {$orderTableName} where schrack_wws_order_number > '' group by schrack_wws_order_number having count(schrack_wws_order_number) > 1;";
        $duplicateOrderNums = $readConnection->fetchAll($query);
        $doncnt = count($duplicateOrderNums);
        $i = 0;
        foreach ( $duplicateOrderNums as $rec ) {
            $duplicateOrderNum = $rec['schrack_wws_order_number'];
            ++$i;
            $collection = Mage::getResourceModel('sales/order_collection');
            $collection->addAttributeToFilter('schrack_wws_order_number',$duplicateOrderNum);
            $dir = $searchParams->isSortAsc ? 'ASC' : 'DES';
            $collection->addOrder('updated_at','DES');
            $cnt = 0;
            foreach ( $collection as $order ) {
                if ( ++$cnt == 1 ) {
                    continue;
                }
                $orderId = $order->getId();
                $sql = "delete from sales_flat_order_schrack_index_position where parent_id in (select entity_id from sales_flat_order_schrack_index where order_id = {$orderId});";
                $writeConnection->query($sql);
                $sql = "delete from sales_flat_order_schrack_index where order_id = {$orderId};";
                $writeConnection->query($sql);
                $order->delete();
            }
        }
    }
}

$shell = new Schracklive_Shell_RepairInvoices();
$shell->run();


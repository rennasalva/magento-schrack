<?php

require_once 'AbstractSoapLogParser.php';

class Schracklive_Shell_FindDuplicateOrderPositions extends Schracklive_Shell_AbstractSoapLogDocParser {
    private $insertUpdateOrderCnt = 0;
    private $duplicateOrderCnt = 0;

    protected function getNecessaryAdditionalArgCnt () { return -1; }

    protected function showUsage () {
        echo 'Usage: php FindDuplicateOrderPositions.php'.PHP_EOL;
    }

    protected function getFileNames ( array &$args ) {
        // $this->verbose = true;
        $scriptDir = realpath(dirname(__FILE__));
        $baseDir = dirname(dirname($scriptDir));
        $logDir = $baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
        $mask = $logDir . 'schracklive_soap_client_wws_request.log*';
        $res = glob($mask);
        return $res;
    }

    protected function handleDocument ( DOMDocument $doc, $fullInfoLine, $ts ) {
        $fn = '';
        $el = $doc->getElementsByTagName("log_entry")->item(0); // log_entry
        if ( ! isset($el) ) {
            throw new Exception("Unexpected error 101");
        }
        $el = $el->getElementsByTagName("Envelope")->item(0);  // SOAP-ENV:Envelope
        if ( ! isset($el) ) {
            throw new Exception("Unexpected error 102");
        }
        $el = $el->getElementsByTagName("Body")->item(0);  // SOAP-ENV:Body
        if ( ! isset($el) ) {
            throw new Exception("Unexpected error 103");
        }
        $el = $el->getElementsByTagName("*")->item(0);  // function
        if ( ! isset($el) ) {
            throw new Exception("Unexpected error 104");
        }
        $fn = $el->tagName;
        $p = strpos($fn,':');
        if ( $p > 0 ) {
            $fn = substr($fn,++$p);
        }

        if ( $fn !== 'insert_update_order' ) {
            return;
        }
        $this->insertUpdateOrderCnt++;

        $skuQtyArray = array();

        $posEl = $el->getElementsByTagName('tt_pos')->item(0);
        if ( ! isset($posEl) ) {
            throw new Exception("Unexpected error 105");
        }
        $hasDoubles = false;
        $items = $posEl->getElementsByTagName('item');
        foreach ( $items as $item ) {
            $sku = $item->getElementsByTagName('ItemID')->item(0)->textContent;
            $qty = $item->getElementsByTagName('Qty')->item(0)->textContent;
            $key = "$sku : $qty";
            if ( ! isset($skuQtyArray[$key]) ) {
                $skuQtyArray[$key] = 1;
            } else {
                $skuQtyArray[$key]++;
                $hasDoubles = true;
            }
        }

        if ( $hasDoubles ) {
            $this->duplicateOrderCnt++;
            $ordEl = $el->getElementsByTagName('tt_order')->item(0);
            if ( ! isset($posEl) ) {
                throw new Exception("Unexpected error 106");
            }
            $ordEl = $ordEl->getElementsByTagName('item')->item(0);
            if ( ! isset($posEl) ) {
                throw new Exception("Unexpected error 107");
            }
            $customerNumber = $ordEl->getElementsByTagName('CustomerNumber')->item(0)->textContent;
            echo "duplicated items found: $fullInfoLine\nCustomer: $customerNumber\nFile: $this->originalSrcFileName\n";
            foreach ( $skuQtyArray as $key => $cnt ) {
                if ( $cnt > 1 ) {
                    echo "    $key ($cnt times)\n";
                }
            }
            echo PHP_EOL;
        }
    }

    protected function exitWriting () {
        echo "$this->duplicateOrderCnt orders with duplicated positions found in $this->insertUpdateOrderCnt insert_update_order requests.\n";
    }
}

(new Schracklive_Shell_FindDuplicateOrderPositions())->run($argv);

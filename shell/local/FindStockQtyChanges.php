<?php

require_once 'AbstractSoapLogParser.php';

class Schracklive_Shell_FindStockQuantityChanges extends Schracklive_Shell_AbstractSoapLogElementParser {
    private $sku, $stockNo;

    public function __construct () {
        parent::__construct('tt_stockRow');
    }

    protected function showUsage () {
        echo 'Usage: php FindStockQtyChanges.php [-v] <log file name> <Sku> [StockNumber]'.PHP_EOL;
    }

    protected function getNecessaryAdditionalArgCnt () {
        return 1;
    }

    protected function handleAdditionalArgs ( array $argv ) {
        $this->sku = $argv[0];
        $this->stockNo = isset($argv[1]) ? $argv[1] : null;
    }

    protected function filterElement ( DOMElement $element ) {
        foreach ( $element->childNodes as $node ) {
            if ( strcasecmp($node->nodeName,'Sku') == 0 ) {
                if ( strncasecmp($node->nodeValue,$this->sku,strlen($this->sku)) != 0 ) {
                    return true;
                }
            }
            else if ( $this->stockNo && strcasecmp($node->nodeName,'StockNumber') == 0 ) {
                if ( strncasecmp($node->nodeValue, $this->stockNo, strlen($this->stockNo) ) != 0 ) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function printDone () {
        $this->printlnVerbose(PHP_EOL."done, $this->found elements of $this->cntRead found.");
    }
}

(new Schracklive_Shell_FindStockQuantityChanges())->run($argv);

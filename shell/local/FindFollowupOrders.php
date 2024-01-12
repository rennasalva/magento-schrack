<?php

require_once 'AbstractSoapLogParser.php';

if ( ! defined('DS') ) {
    define('DS',DIRECTORY_SEPARATOR);
}

class Schracklive_Shell_FindFollowupOrders extends Schracklive_Shell_AbstractSoapLogDocParser {
    const CSV_FILE = '/tmp/FindFollowupOrders.csv';

    var $orders = array();
    var $csvFile = null;

    protected function getNecessaryAdditionalArgCnt () { return -1; }

    protected function showUsage () {
        echo 'Usage: php FindFollowupOrders.php'.PHP_EOL;
    }

    public function run ( array $argv ) {
        ini_set('memory_limit','1024M');
        $this->verbose = true;
        parent::run($argv);
    }

    protected function getFileNames ( array &$args ) {
        $res = array();
        $dir = dirname(dirname(dirname(__FILE__))) . DS . 'var' . DS . 'log' . DS;
        $files = scandir($dir);
        foreach ( $files as $file ) {
            $p = strpos($file,'schracklive_soap_server_api_v2_request.log');
            if ( $p === 0 ) {
                $res[] = $dir . $file;
            }
        }
        return $res;
    }

    protected function handleDocument ( DOMDocument $doc, $fullInfoLine, $ts ) {
        $fn = $this->getWebserviceFunctionName($doc);
        if ( $fn === 'salesOrderSchrackInsertUpdate' ) {
            echo 'X';
            $el = $doc->getElementsByTagName("log_entry")->item(0); // log_entry
            if ( isset($el) ) {
                $el = $el->getElementsByTagName("Envelope")->item(0);  // SOAP-ENV:Envelope
                if ( isset($el) ) {
                    $el = $el->getElementsByTagName("Body")->item(0);  // SOAP-ENV:Body
                    if ( isset($el) ) {
                        $el = $el->getElementsByTagName("*")->item(0);  // function
                        if ( isset($el) ) {
                            $el = $el->getElementsByTagName("data_order")->item(0);  // data_order
                            if ( isset($el) ) {
                                $i=0;
                                $orderRows = $doc->getElementsByTagName("tt_wwsorderRow"); // tt_wwsorderRow
                                foreach ( $orderRows as $el ) {
                                    $rec = array();
                                    $rec['TimeStamp']                  = $ts;
                                    $rec['OrderNumber']                = $el->getElementsByTagName("OrderNumber")->item(0)->nodeValue;
                                    $rec['OriginalOrderNumber'] = $onr = $el->getElementsByTagName("OriginalOrderNumber")->item(0)->nodeValue;
                                    $rec['WWSStatus']                  = $el->getElementsByTagName("WWSStatus")->item(0)->nodeValue;
                                    $rec['CustomerNumber']             = $el->getElementsByTagName("CustomerNumber")->item(0)->nodeValue;
                                    $rec['broken']                     = '0';
                                    $rec['LogFile']                    = $this->srcFileName;
                                    if ( ! isset($this->orders[$onr]) ) {
                                        $this->orders[$onr] = array();
                                    }
                                    $this->orders[$onr][] = $rec;
                                }
                            }
                        }
                    }
                }
            }

        } else {
            echo '.';
        }
    }

    protected function exitWriting () {
        foreach ( $this->orders as $key => $orderChain ) {
            // lookup for followup:
            $isFollowup = false;
            foreach ( $orderChain as $rec ) {
                if ( $rec['OrderNumber'] !== $rec['OriginalOrderNumber'] && $rec['WWSStatus'] !== 'DEL' ) {
                    $isFollowup = true;
                    break;
                }
            }
            if ( ! $isFollowup ) {
                unset($this->orders[$key]);
                continue;
            }
            // sort by timestamp
            uasort($orderChain,'tscmp');
            $last = end($orderChain);
            reset($orderChain);
            $wwsState = strtoupper($last['WWSStatus']);
            if ( $wwsState !== 'LA5' && $wwsState !== 'LA8' ) {
                array_pop($orderChain);
                $last['broken'] = '1';
                $orderChain[] = $last;
            }
            $this->orders[$key] = $orderChain;
        }
        uasort($this->orders,'tscmp2');
        $this->csvFile = fopen(self::CSV_FILE,'w');
        if ( ! $this->csvFile ) {
            throw Exdeption("cannot open file '" . self::CSV_FILE . "'!");
        }
        fputcsv($this->csvFile,array('TimeStamp','OrderNumber','OriginalOrderNumber','WWSStatus','CustomerNumber','broken','CustomerNumber','LogFile'));
        foreach ( $this->orders as $key => $orderChain ) {
            echo PHP_EOL;
            foreach ( $orderChain as $rec ) {
                $this->printRec($rec);
            }
        }
        fclose($this->csvFile);
    }

    private function printRec ( $rec ) {
        fputcsv($this->csvFile,$rec);
        echo 'TimeStamp: ' . $rec['TimeStamp'] . '; ';
        echo 'OrderNumber: ' . $rec['OrderNumber'] . '; ';
        echo 'OriginalOrderNumber: ' . $rec['OriginalOrderNumber'] . '; ';
        echo 'WWSStatus: ' . $rec['WWSStatus'] . '; ';
        echo 'broken: ' . $rec['broken'] . '; ';
        echo 'CustomerNumber: ' . $rec['CustomerNumber'] . '; ';
        echo 'LogFile: ' . $rec['LogFile'] . PHP_EOL;
    }
}

function tscmp ($a, $b) {
    if ($a['TimeStamp'] == $b['TimeStamp']) {
        return 0;
    }
    return ($a['TimeStamp'] < $b['TimeStamp']) ? -1 : 1;
}

function tscmp2 ($a, $b) {
    return tscmp(reset($a),reset($b));
}


(new Schracklive_Shell_FindFollowupOrders())->run($argv);
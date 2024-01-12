<?php

// define('QUEUE_DEBUG',true);

require_once 'ProtoQueueProductImportBase.php';

class Schracklive_Shell_ProtoQueueProductImport extends Schracklive_Shell_ProtoQueueProductImportBase {

    /* @var $_importer Schracklive_SchrackCatalog_Model_Protoimport */
    var $_importer;
    var $_maxMessageCount = 1;
    var $_originTimestamp;


    public function __construct () {
        parent::__construct();

        Mage::register(Schracklive_Schrack_Helper_Stomp::MQ_IMPORT_MARKER,true,true);

        if ($this->getArg('help')) {
            die($this->usageHelp());
        }

        $this->aquireSemaphore();

        $x = Mage::getStoreConfig('schrack/product_import/process_messages_per_call_count'); //
        if ( is_int($x) ) {
            $this->_maxMessageCount = $x;
        }
        $x = $this->getArg('max_messages');
        if ( $x ) {
            $this->_maxMessageCount = intval($x);
        }

        $this->_importer = Mage::getModel('schrackcatalog/protoimport');
        $this->_importer->setIgnoreLastPackages(true);
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("START: Worker",null,'queue.log');
        }
    }

    public function __destruct () {
        $this->releaseSemaphore();
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("STOP: Worker",null,'queue.log');
        }
        // parent::__destruct();
    }

    public function run () {
        if ( $this->getArg('fake') ) {
            eratosthenes(50000);

            return;
        }

        if ( $this->getArg('finalize') ) {
            echo 'finalizing...' . PHP_EOL;
            $this->_importer->enableFinalize();
            $data = null;
            $this->_importer->run($data);
        } else {
            if ( $this->getArg('megamenu') ) {
                echo 'update only megamenu timestamp...' . PHP_EOL;
                $this->_importer->disableAll();
                $this->_importer->disableMegaMenuUpdate(false);
                $data = null;
                $this->_importer->run($data);
            } else {
                $stompClient = $this->_stompHelper->createAndSubscribeStompClientFromConfigPaths($this->getUrlCoreConfigPath(),
                    $this->getInQueueCoreConfigPath());

                echo "Polling...\n";
                $msgCount = 0;
                while ( $stompClient->hasFrame() && ($this->_maxMessageCount < 0 || $msgCount < $this->_maxMessageCount) ) {
                    $msg = $stompClient->readFrame();
                    if ( !$msg ) {
                        echo 'No more messages in queue' . PHP_EOL;
                        break;
                    }
                    echo 'processing message ' . $msgCount . '/' . $this->_maxMessageCount . PHP_EOL;
                    $jmsTimestamp = date("Y-m-d H:i:s T", $msg->headers['timestamp'] / 1000);
                    $originTimestamp = $msg->headers['Origin_Timestamp'];
                    $data = $msg->body; // because of saving memory on full file imports, run() unsets the given variable asap
                    try {
                        $this->_importer->dump2file($data, $msg->headers);
                        $this->_importer->run($data, null, $originTimestamp);
                    } catch ( Schracklive_SchrackCatalog_Model_Protoimport_DuplicateMessageException $dupMsgEx ) {
                        self::log("ignoring duplicate message");
                    } catch ( Exception $ex ) {
                        echo "exception caught: " . $ex->getMessage() . " - writing to error queue\n";
                        $headers = $msg->headers;
                        $headers['ErrorMessage'] = $ex->getMessage();
                        $msgId = $headers['message-id'];
                        unset($headers['message-id']);
                        unset($headers['destination']);
                        unset($headers['content-length']);
                        $errorQueue = Mage::getStoreConfig('schrack/product_import/error_queue');
                        $targetQueue = $this->getQueuePathForName($errorQueue);
                        $res = $stompClient->send($targetQueue, $msg->body, $headers);
                        if ( !$res ) {
                            $error = $stompClient->error();
                            $error = $error ? $error : '(unknown error)';
                            unset($stompClient);
                            throw new Exception('MQ message sending failed: ' . $error);
                        }
                    }
                    $stompClient->ack($msg);
                    unset($msg);
                    ++$msgCount;
                    sleep(1);
                }
                echo "\n\nRead $msgCount Messages.\nDone.\n";
                $this->_stompHelper->unsubscribeStompClient($stompClient);
                unset($stompClient);
            }
        }
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f ProtoQueueProductImport.php [--max_messages <cnt>] [--finalize] [--megamenu]



USAGE;
    }

}

function eratosthenes($n) {
    $all=array();
    $prime=1;
    $i=3;
    while($i<=$n) {
        if(!in_array($i,$all)) {
            echo $i . PHP_EOL;
            $prime+=1;
            $j=$i;
            while($j<=($n/$i)) {
                array_push($all,$i*$j);
                $j+=1;
            }
        }
        $i+=2;
    }
    return;
}

$shell = new Schracklive_Shell_ProtoQueueProductImport();
$shell->run();

?>

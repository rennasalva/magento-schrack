<?php

// define('QUEUE_DEBUG',true);

require_once 'ProtoQueueProductImportBase.php';

class Schracklive_Shell_ReadMessages extends Schracklive_Shell_ProtoQueueProductImportBase {

    const KEYSET_FILEPATH = '/tmp/readmessages_keyset.bin';

    private $_fromQueue = false;
    private $_maxMessageCount = -1;
    private $_resetKeyset = false;

    public function __construct () {
        parent::__construct();

        $x = $this->getArg('from_queue');
        if ( $x ) {
            $this->_fromQueue = $x;
        }
        $x = $this->getArg('max_messages');
        if ( $x ) {
            $this->_maxMessageCount = intval($x);
        }
        $x = $this->getArg('reset_keyset');
        if ( $x ) {
            $this->_resetKeyset = true;
        }
    }

    public function run () {
        if ( !  $this->_fromQueue ) {
            die($this->usageHelp());
        }

        if ( ! $this->_resetKeyset && file_exists(self::KEYSET_FILEPATH) ) {
            $ser = file_get_contents(self::KEYSET_FILEPATH);
            $keySet = unserialize($ser);
        } else {
            $keySet = array();
        }

        $receiptVal =  'webshop-' . time();

        $stompUrl = $this->getStompUrl();
        $inQueue = $this->getQueuePathForName($this->_fromQueue);
        echo "read @stomp url $stompUrl from $inQueue\n";
        sleep(2);

        /** @var Stomp $stompClient */
        $stompClient = new Stomp($stompUrl);
        $stompClient->subscribe($inQueue,array('receipt'=>$receiptVal));

        $msgCount = 0;
        $duplicateCount = 0;
        while ($stompClient->hasFrame() && ($this->_maxMessageCount < 0 || $msgCount < $this->_maxMessageCount)) {
            $msg = $stompClient->readFrame();
            if ( ! $msg ) {
                echo 'No more messages in queue' . PHP_EOL;
                break;
            }
            ++$msgCount;
            $originTimestamp = $msg->headers['Origin_Timestamp'];
            $md5 = md5($msg->body);
            $key = "$originTimestamp\t$md5";
            if ( isset($keySet[$key]) ) {
                $dup = " DUPLICATED!";
                ++$duplicateCount;
            } else {
                $dup = '';
                $keySet[$key] = true;
            }
            echo "$msgCount\t$key\t$dup\n";
            $stompClient->ack($msg);
            sleep(1);
        }
        echo "\n\n$msgCount messages read, $duplicateCount of them was duplicated.\n\n";
        $stompClient->unsubscribe($inQueue ,array('receipt'=>$receiptVal) );
        unset($stompClient);
        $ser = serialize($keySet);
        file_put_contents(self::KEYSET_FILEPATH,$ser);
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f ReadMessages.php --from_queue <name> [--max_messages <cnt>] [--reset_keyset]



USAGE;
    }
}

$shell = new Schracklive_Shell_ReadMessages();
$shell->run();

?>

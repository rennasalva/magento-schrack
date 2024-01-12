<?php

// DLA20161021: Nobody knows why bloody php needs that "require_once" stuff just for the MailingListTypeMessage and e.g. not for the ContactMessage - ARHG!
$libDir = Mage::getBaseDir('lib');
require_once $libDir . "/DrSlump/Protobuf/Message.php";
require_once $libDir . "/com/schrack/queue/protobuf/MailingListTypeTransfer/MailingListTypeMessage.php";

use com\schrack\queue\protobuf\Message;
use com\schrack\queue\protobuf\AccountTransfer\AccountMessage;

class Schracklive_Account_Helper_Protobuf extends Schracklive_Schrack_Helper_Stomp {
    const TYPE_ACCOUNT  = 1;
    const TYPE_CONTACT  = 2;
    const TYPE_ADDRESS  = 3;
    const TYPE_PROSPECT = 4;
    const TYPE_ML_TYPE  = 5;
    // ha! note how they're all the same length!?

    const STOMP_URL_CFG_PATH       = 'schrack/account/stomp_url';
    const STOMP_IN_QUEUE_CFG_PATH  = 'schrack/account/message_queue_inbound';
    const STOMP_OUT_QUEUE_CFG_PATH = 'schrack/account/message_queue_outbound';
    const STOMP_ERR_QUEUE_CFG_PATH = 'schrack/account/message_queue_error';

    const SENDER_ID = 'shop';

    const QUEUE_IN = 1;
    const QUEUE_OUT = 2;
    const QUEUE_ERR = 3;
    const QUEUE_UNKNOWN = 0;

    public function __construct() {
        Schracklive_Account_Model_Protoimport_Base::initProtobuf();
    }

    public function importMessage ( $type, $message, $filename = null, $headers = array() ) {
        if ($filename !== null) {
            $fh = fopen($filename, 'r');
            $data = fread($fh, filesize($filename));
            fclose($fh);
        } else {
            $data = $message;
        }
        $this->logMessage($data,$headers,self::QUEUE_IN);

        $dateStr = date('Ymd');
        $dirPath = '/tmp/protoimport/' . $dateStr;
        if (!file_exists($dirPath)) {
            $res = mkdir($dirPath, 0777, true);
        }

        $timestamp = $this->microDateTime();

        $filePath = "{$dirPath}/protoimportdump.{$type}.{$timestamp}.bin";
        Mage::log("recieved portoimporter update message dumped in = '$filePath'");
        @file_put_contents($filePath, $data);

        $className = $this->_getPHPMessageClassName($type);
        $msg = new $className($data);
        /* @var $importModel Schracklive_XXXX_Model_Protoimport */
        $modelName = $this->_getModelName($type);
        $importModel = Mage::getModel($modelName);
        $importModel->insertOrUpdateOrDelete($msg,$headers);
    }

    public function dumpMessage( $type, &$data ) {
        $className = $this->_getPHPMessageClassName($type);
        $msg = new $className($data);
        $binData = null;
        $codec = new \DrSlump\Protobuf\Codec\TextFormat();
        $textData = $codec->encode($msg);
        print($textData.PHP_EOL);
    }

    /**
     * @param int $type message type constant from this class
     * @param stompmessage $msg stomp message
     * @return bool success
     */
    public function sendMessage( $type, $msg ) {
        // Mage::log('Schracklive_Account_Helper_Protobuf::sendMessage called with type=' . $type . ', message=' . print_r($msg, true), null, Schracklive_Account_Model_Protoimport::LOG_FILE_NAME);
        $stomp = $this->createStompClientFromConfigPath(self::STOMP_URL_CFG_PATH);
        $queue = $this->getCountryQueuePath(self::STOMP_OUT_QUEUE_CFG_PATH);
        Mage::log('Schracklive_Account_Helper_Protobuf::sendMessage will send to queue ' . $queue, null, Schracklive_Account_Model_Protoimport::LOG_FILE_NAME);
        $headers = $this->mkStompHeaders($type);
        $this->logMessage($msg,$headers,self::QUEUE_OUT);
        return $stomp->send($queue,$msg,$headers);
    }

    public function sendErrorMessage ( $type, $msg, $errorMsg, $originalHeaders ) {
        if ( $msg instanceof \DrSlump\Protobuf\Message ) {
            $codec = new \DrSlump\Protobuf\Codec\Binary();
            $msg = $msg->serialize($codec);
        }
        $stomp = $this->createStompClientFromConfigPath(self::STOMP_URL_CFG_PATH);
        $queue = $this->getQueuePath(self::STOMP_ERR_QUEUE_CFG_PATH);
        $headers = $originalHeaders;
        $headers['error_message'] = $errorMsg;
        unset($headers['content-length']);
        unset($headers['destination']);
        unset($headers['original-destination']);
        unset($headers['message-id']);
        unset($headers['timestamp']);
        $this->logMessage($msg,$headers,self::QUEUE_ERR);
        return $stomp->send($queue,$msg,$headers);
    }

    private function mkStompHeaders ( $type, $originTimestamp = null ) {
        if ($originTimestamp == null) {
            $aux =  microtime(true);
            $date = DateTime::createFromFormat('U.u', $aux);

            // If microtime(true) only returns zeroes as decimals, just fix microseconds to create object from ->createFromFormat
            if (is_bool($date)) {
                $aux = $aux + 0.001;
                $date = DateTime::createFromFormat('U.u', $aux);

                if (is_bool($date)) {
                    $originTimestamp = date('Y-m-d H:i:s') . '.800300';
                }
            }

            if ($originTimestamp == null || $originTimestamp == '') {
                // 2015-10-04 19:30:32.552
                $originTimestamp = $date->format("Y-m-d H:i:s.u");
            }
        }
        $headers = array(
            'sender_id'        => self::SENDER_ID,
            'protobuf_class'   => $this->_getJavaMessageClassName($type),
            'country_shop'     => strtoupper(Mage::getStoreConfig('schrack/general/country')),
            'country_wws'      => Mage::helper('schrack')->getWwsCountry(),
            'Origin_Timestamp' => $originTimestamp
        );
        return $headers;
    }

    private function _getModelName($type) {
        switch ($type) {
            case self::TYPE_ACCOUNT:
                return 'Schracklive_Account_Model_Protoimport';
            case self::TYPE_CONTACT:
                return 'Schracklive_SchrackCustomer_Model_Protoimport';
            case self::TYPE_PROSPECT:
                return 'Schracklive_SchrackCustomer_Model_Prospect_Protoimport';
            case self::TYPE_ADDRESS:
                return 'Schracklive_SchrackCustomer_Model_Address_Protoimport';
            case self::TYPE_ML_TYPE:
                return 'Schracklive_SchrackCustomer_Model_Mailinglisttype_Protoimport';
        }
    }

    private function _getPHPMessageClassName($type) {
        switch ($type) {
            case self::TYPE_ACCOUNT:
                return '\com\schrack\queue\protobuf\AccountTransfer\AccountMessage';
            case self::TYPE_CONTACT:
                return '\com\schrack\queue\protobuf\ContactTransfer\ContactMessage';
            case self::TYPE_ADDRESS:
                return '\com\schrack\queue\protobuf\AddressTransfer\AddressMessage';
            case self::TYPE_PROSPECT:
                return '\com\schrack\queue\protobuf\ProspectTransfer\ProspectMessage';
            case self::TYPE_ML_TYPE:
                return '\com\schrack\queue\protobuf\MailingListTypeTransfer\MailingListTypeMessage';
        }
    }

    private function _getJavaMessageClassName($type) {
        switch ($type) {
            case self::TYPE_ACCOUNT:
                return 'com.schrack.queue.protobuf.AccountTransfer.AccountMessage';
            case self::TYPE_CONTACT:
                return 'com.schrack.queue.protobuf.ContactTransfer.ContactMessage';
            case self::TYPE_ADDRESS:
                return 'com.schrack.queue.protobuf.AddressTransfer.AddressMessage';
            case self::TYPE_PROSPECT:
                return 'com.schrack.queue.protobuf.ProspectTransfer.ProspectMessage';
            case self::TYPE_ML_TYPE:
                return 'com.schrack.queue.protobuf.MailingListTypeTransfer.MailingListTypeMessage';
        }
    }

    function microDateTime () {
        $time =microtime(true);
        $micro_time=sprintf("%06d",($time - floor($time)) * 1000000);
        $date=new DateTime( date('Y-m-d H:i:s.'.$micro_time,$time) );
        return $date->format("Ymd_His.u");
    }

    public static function logMessage ( $msg, $headers = array(), $targetQueue = self::QUEUE_UNKNOWN, $module = 'account' ) {
        $className = str_replace('.', '\\', $headers['protobuf_class']);
        if ( class_exists($className) ) {
            try {
                $protobufMessage = \DrSlump\Protobuf::decode($className, $msg);
                $dump = $protobufMessage->serialize(new \DrSlump\Protobuf\Codec\TextFormat());
            } catch ( Exception $ex ) {
                $dump = "Cannot decode or serialize message of class '$className'. DrSlump error: " . $ex->getMessage();
            }
        } else {
           $dump = "Cannot dump unexpected Protocol Buffers message type: {$className}";
        }
        $logFileName = Mage::getBaseDir('var').DS.'log'.DS.'schracklive_mq_' . $module . '_';
        switch ( $targetQueue ) {
            case self::QUEUE_IN      : $logFileName .= 'in';  break;
            case self::QUEUE_OUT     : $logFileName .= 'out'; break;
            case self::QUEUE_ERR     : $logFileName .= 'err'; break;
            case self::QUEUE_UNKNOWN :
            default                  : $logFileName .= 'unknown';
        }
        $logFileName .= '.log';
        $startDateTime = date('Y-m-d H:i:s T',time());
        $headerLine = $startDateTime.chr(10);
        $fileHandel = @fopen($logFileName,'a');
        if ( $fileHandel ) {
            @fwrite($fileHandel,$headerLine);
            @fwrite($fileHandel,'Headers:'.chr(10));
            foreach ( $headers as $key => $val ) {
                $s = '    ' . $key . ':   ' . $val . chr(10);
                @fwrite($fileHandel,$s);
            }
            @fwrite($fileHandel,chr(10).'Message:'.chr(10));
            @fwrite($fileHandel,$dump);
            @fwrite($fileHandel, chr(10).chr(10));
            @fclose($fileHandel);
        }
    }
}

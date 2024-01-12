<?php

use \DrSlump\Protobuf\Message;

abstract class Schracklive_Account_Model_Protoimport_Base {

    const DEFAULT_STORE_ID  = 1;
    
    const LOG_FILE_NAME       = 'proto_import_account.log';
    const DEBUG_LOG_FILE_NAME = 'proto_import_account_debug.log';
    
    private static $protbufAutoloadSwitchedOn = false;
    
    private static $traceMap = array();
    
    public static $_echoLog = true;
    public static $_logDebug = true;
    public static $_logTrace = false;

    protected static $_isInInsertUpdateMap = array();

    protected $_writeConnection;
    protected $_readConnection;
    protected $_mqHelper;

    protected static function isInInsertUpdate ( $messageKey ) {
        return isset(self::$_isInInsertUpdateMap[$messageKey]) && self::$_isInInsertUpdateMap[$messageKey];
    }

    function __construct() {
        self::initProtobuf();
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_mqHelper = Mage::helper("schrack/mq");
    }

    abstract protected function insertOrUpdateOrDeleteImpl ( Message $message );
    abstract protected function getMessageKeyFromProtobuf ( Message $message );
    abstract protected function getType ();
    abstract protected function checkInstance ( Message $message );
    abstract protected function getMappingNamesProtobufToMagento ();

    protected function mapProtobufToMagento ( Message $protobufMsg, Mage_Core_Model_Abstract $magentoModel ) {
        $namesMap = $this->getMappingNamesProtobufToMagento();
        $protoVals = get_object_vars($protobufMsg);
        foreach ( $namesMap as $fromName => $toName ) {
            $val = $protoVals[$fromName];
            $magentoModel->setData($toName,$val);
        }
    }

    protected function mapMagentoToProtobuf ( Mage_Core_Model_Abstract $magentoModel, Message $protobufMsg ) {
        $namesMap = $this->getMappingNamesProtobufToMagento();
        $protoNameToNumMap = array();
        foreach ( $protobufMsg->descriptor()->getFields() as $field ) {
            $protoNameToNumMap[$field->getName()]= $field->getNumber();
        }
        foreach ( $namesMap as $protoName => $magentoName ) {
            $val = $magentoModel->getData($magentoName);
            $protobufMsg->_set($protoNameToNumMap[$protoName],$val);
        }
    }

    public function insertOrUpdateOrDelete ( Message $message, $originalHeaders ) {
        // Emergency, which message-types should have "Origin_Timestamp", and doesn't have it in real:
        if ( isset($originalHeaders['protobuf_class']) && !isset($originalHeaders['Origin_Timestamp']) ) {
            Mage::log('Missing Origin_Timestamp Header in ' . $originalHeaders['protobuf_class'], null, 'missing_message_headers.log');
            Mage::helper('schrack/email')->sendDeveloperMail('ATTENTION: Missing "Origin_Timestamp" in',$originalHeaders['protobuf_class']);
        }

        $originTimestampStr = $originalHeaders['Origin_Timestamp'];
        if ( ! $this->checkInstance($message) ) {
            throw new Exception('Wrong message type got!');
        }
        $msgKey = $this->getMessageKeyFromProtobuf($message);
        $isLast = $this->_mqHelper->isLatestUpdate($msgKey, $originTimestampStr);
        if ( ! $isLast ) {
            Mage::log('skipping msg ' . $msgKey . ' because its ts is too old', null, 'deprecated_message_headers.log');
            return;
        }

        self::$_isInInsertUpdateMap[$msgKey] = true;
        $this->_writeConnection->beginTransaction();
        try {
            $this->insertOrUpdateOrDeleteImpl($message);
            if ( $originTimestampStr ) {
                $this->_mqHelper->saveLatestUpdate($msgKey, $originTimestampStr);
            } else {
                $this->_mqHelper->removeTimestamp($msgKey);
            }
            $this->_writeConnection->commit();
        } catch ( Mage_Api_Exception $apiEx ) {
            $this->_writeConnection->rollback();
            Mage::logException($apiEx);
            $text = $apiEx->getMessage() . ': ' . $apiEx->getCustomMessage();
            Mage::helper('account/protobuf')->sendErrorMessage($this->getType(),$message,$text,$originalHeaders);
        } catch (Exception $e) {
            $this->_writeConnection->rollback();
            Mage::logException($e);
            Mage::helper('account/protobuf')->sendErrorMessage($this->getType(),$message,$e->getMessage(),$originalHeaders);
        }
        self::$_isInInsertUpdateMap[$msgKey] = false;
    }

    public static function initProtobuf () {
        Mage::helper("schrack/protobuf")->initProtobuf();
        $libDir = Mage::getBaseDir('lib');
        require_once $libDir . '/com/schrack/queue/protobuf/AccountTransfer/AccountMessage.php';
        require_once $libDir . '/com/schrack/queue/protobuf/ContactTransfer/ContactMessage.php';
        require_once $libDir . '/com/schrack/queue/protobuf/AddressTransfer/AddressMessage.php';
        require_once $libDir . '/com/schrack/queue/protobuf/ProspectTransfer/ProspectMessage.php';
    }
    
	protected static function log ( $data ) {
        self::_flushCharBuf();
        Mage::log($data,null,self::LOG_FILE_NAME);
		if ( self::$_echoLog ) {
    		$data = "[".date("y.m.d H:i:s")."] ".$data;
			echo $data . PHP_EOL;
		}
    }    

	protected static function beginTrace ( $name ) {
		if ( ! self::$_logDebug || ! self::$_logTrace ) {
            return;
        }
        self::$traceMap[$name] = self::getCurrentMs();
        $s = '>>> ' . $name;
        self::logDebug($s);
    }
    
	protected static function endTrace ( $name ) {
		if ( ! self::$_logDebug || ! self::$_logTrace ) {
            return;
        }
        if ( isset(self::$traceMap[$name]) ) {
            $then = self::$traceMap[$name];
            $now = self::getCurrentMs();
            $duration = $now - $then;
            $s = '<<< ' . $name . ': ' . $duration;
            self::logDebug($s);
            unset(self::$traceMap[$name]);
        }
        else {
            self::logDebug('wrong usage of beginTrace/endTrace !!!');
        }
    }
    
    private static function getCurrentMs () {
        return (int) (microtime(true) * 1000);
    }
    
	protected static function logDebug ( $data ) {
		if ( self::$_logDebug ) {
            Mage::log($data,null,self::DEBUG_LOG_FILE_NAME);
        }
    }  
    
    protected static function logDebugMem () {
		if ( self::$_logDebug ) {
            $s = "memory_get_peak_usage returns: " . memory_get_peak_usage(true);
            self::logDebug($s);
        }
    }
    
    private static $_charBuf = '';
    
	protected static function logProgressChar ( $char ) {
		if ( self::$_echoLog ) {
            echo $char;
        }
		if ( self::$_logDebug ) {
            self::logDebug('action = ' . $char);
        }
        self::$_charBuf .= $char;
        if ( strlen(self::$_charBuf) >= 80 ) {
            self::_flushCharBuf();
        }
    }
    
    private static function _flushCharBuf () {
		if ( self::$_echoLog ) {
            echo PHP_EOL;
        }
        if ( ! strlen(self::$_charBuf) ) {
            return;
        }
        Mage::log(self::$_charBuf,null,self::LOG_FILE_NAME);
        self::$_charBuf = '';
    }

    protected function createProtobufErrorMessage($message, $exception) {
        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $message->serialize($codec);
        return $data;
    }
}

?>

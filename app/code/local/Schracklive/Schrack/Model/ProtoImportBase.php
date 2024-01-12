<?php

abstract class Schracklive_Schrack_Model_ProtoImportBase {

    /* @var $_writeConnection Magento_Db_Adapter_Pdo_Mysql */
    protected $_writeConnection;
    /* @var $_readConnection Magento_Db_Adapter_Pdo_Mysql */
    protected $_readConnection;
    protected $_storeId;
    protected $_originTimestamp;

    private static $traceMap = array();

    public static $_echoLog = true;
    public static $_logDebug = true;
    public static $_logTrace = false;

    private static $_logFileName;
    private static $_debugLogFileName;

    function __construct ( $drSlumpBindingFile, $originTimestamp = null ) {
        self::initProtobuf($drSlumpBindingFile);
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_storeId = Mage::app()->getStore('default')->getStoreId();
        $this->_originTimestamp = $originTimestamp;
        self::$_logFileName = $this->getLogFileBaseName() . '.log';
        self::$_debugLogFileName = $this->getLogFileBaseName() . '_debug.log';
    }

    public static function initProtobuf ( $drSlumpBindingFile ) {
        Mage::helper("schrack/protobuf")->initProtobuf();
        $libDir = Mage::getBaseDir('lib');
        require_once $libDir . "/com/schrack/queue/protobuf/$drSlumpBindingFile";
    }

    abstract protected function getLogFileBaseName ();
    abstract protected function getDumpFileBaseName ();

	protected static function log ( $data ) {
        self::_flushCharBuf();
        Mage::log($data,null,self::$_logFileName);
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
            Mage::log($data,null,self::$_debugLogFileName);
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
        Mage::log(self::$_charBuf,null,self::$_logFileName);
        self::$_charBuf = '';
    }

    protected function getStorePath ( $name, $extension ) {
        $countryCode = Schracklive_SchrackCatalog_Model_Protoimport::getCountryCode();
        $path = "/tmp/protoimport";
        if ( ! file_exists($path) ) {
            mkdir($path);
        }
        $path = "$path/${name}_${countryCode}.{$extension}";
        return $path;
    }

    protected function backupIfExists ( $path, $count = 10 ) {
        if ( file_exists($path) ) {
            if ( substr($path,-4) == '.sav' ) {
                $backup = $path . '.old';
            } else {
                $backup = $path . '.sav';
                $this->backupIfExists($backup);
            }
            copy($path,$backup);
        }
        return $path;
    }

    private function microDateTime () {
        $time = microtime(true);
        $micro_time = sprintf("%06d",($time - floor($time)) * 1000000);
        $date = new DateTime( date('Y-m-d H:i:s.'.$micro_time,$time) );
        return $date->format("Ymd_His.u");
    }

    public function dump2file ( &$binData, $headers = null ) {
        $dirPath = '/tmp/protoimport/';
        $this->checkDiskSpace($dirPath);
        $dateStr = date('Ymd');
        $dirPath .= $dateStr;
        if ( ! file_exists($dirPath) ) {
            $res = mkdir($dirPath,0777,true);
        }
        $timestamp = $this->microDateTime();
        $countryCode = Schracklive_SchrackCatalog_Model_Protoimport::getCountryCode();
        $baseName = $this->getDumpFileBaseName();
        $filePath = "{$dirPath}/protoimportdump.{$baseName}.{$countryCode}.{$timestamp}.bin";
        if ( $headers ) {
            $this->checkMessageDuplicate($binData, $headers, $filePath);
        }
        self::log("recieved portoimporter update message dumped in = '$filePath'");
        @file_put_contents($filePath,$binData);
        $this->_lastDumpFilePath = $filePath;
    }

    protected function checkMessageDuplicate ( &$binData, $headers, $filePath ) {
        $originTimestamp = $headers['Origin_Timestamp'];
        $md5 = md5($binData);
        $headersJson = json_encode($headers, JSON_PRETTY_PRINT);
         // mysql timestamp format:     YYYY-MM-DD hh:mm:ss[.fraction]'
        // msg origin_timestamp format: 2020-04-12 17:43:26.010
        $sql = "SELECT * FROM schrack_catalog_importer_processed_message_log WHERE origin_timestamp = ? AND md5_sum = ?";
        $rows = $this->_readConnection->fetchAll($sql,array($originTimestamp,$md5));
        foreach ( $rows as $row ) {
            $origFilePath = $row['file_system_path'];
            $origHeadersJson = $row['all_headers_json'];
            $msg = "Message with headers \n$headersJson\nis duplicate to already imported massage $origFilePath with headers \n$origHeadersJson\n";
            Mage::log($msg,null,'proto_import_message_check.log');
            throw new Schracklive_SchrackCatalog_Model_Protoimport_DuplicateMessageException($msg);
        }
        $sql = " INSERT INTO schrack_catalog_importer_processed_message_log"
             . " (origin_timestamp,md5_sum,file_system_path,all_headers_json) VALUES(?,?,?,?)";
        $this->_writeConnection->query($sql,array($originTimestamp,$md5,$filePath,$headersJson));
    }

    protected function checkDiskSpace ( $dirPath ) {
        $freeBytes = disk_free_space($dirPath);
        $freeGigaBytes = $freeBytes / (1024 * 1024 * 1024);
        if ( $freeGigaBytes < 1.0 ) {
            $msg = "Cannot proceed import, free disk space is less than 1GB!";
            $ex = new Exception($msg);
            Mage::logException($ex);
            self::log($ex->__toString());
            $this->createAlertMails($msg);
            throw $ex;
        }
    }

    protected function createAlertMails ( $mailText ) {
        try {
            Mage::helper('schrack/mailalert')->createAlertMails($mailText,null,true);
        } catch ( Exception $ex ) {
            self::logDebug($mailText . ' E-Mail transfer failed');
            // no throw again, regular flow should continue
        }
    }

    protected function checkDumpFilePath ( $name ) {
        $msg = null;
        $refFilePath = $this->getRefPath($name);
        if ( file_exists($refFilePath) ) {
            $dumpFilePath = trim(file_get_contents($refFilePath));
            if ( file_exists($dumpFilePath) ) {
                return true;
            } else {
                $msg = "Binary dump file '$dumpFilePath' as reference for $name does not exist.";
            }
        } else {
            $msg = "Reference file '$refFilePath' for references $name not found.";
        }
        $msg = "Finalize aborted: $msg";
        $ex = new Exception($msg);
        Mage::logException($ex);
        self::log($ex->__toString());
        $this->createAlertMails($msg);
        throw $ex;
    }

    protected function getRefPath ( $name ) {
        return $this->getStorePath($name,'ref');
    }

    protected function unzipMessage ( &$binData, &$unzippedBinData ) {
        $unzippedBinData = '';
        $tmpDir = Mage::getBaseDir('tmp');
        $zipFile = tempnam($tmpDir,'zip');
        file_put_contents($zipFile,$binData);
        unset($binData);
        if ( $zip = zip_open($zipFile) ) {
            if ( $zipEntry = zip_read($zip) ) { // is usually a while, but in that case we support only one entry.
                if ( zip_entry_open($zip,$zipEntry,'r') ) {
                    while ( $buffer = zip_entry_read($zipEntry) ) {
                        $unzippedBinData .= $buffer;
                    }
                    zip_entry_close($zipEntry);
                }
            }
            zip_close($zip);
        }
        unlink($zipFile);
    }
}

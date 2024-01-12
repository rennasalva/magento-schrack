<?php
use com\schrack\queue\protobuf\AccountMessage;
use com\schrack\queue\protobuf\Message;

require_once 'shell.php';

class Schracklive_Shell_S4YConnector extends Schracklive_Shell {
    const FULL_FILENAME_BASE = '/tmp/S4YConnector_';
    const FILENAME_EXT  = '.pid';

    var $_import = false;
    var $_importFile = null;
    var $_messageType = null;
    var $_dumpFile = false;
    var $_dumpStruct = false;
    var $_serialize = false;
    var $_unserialize = false;
    var $_poll = false;
    var $_exportId = false;
    var $_schrackWwsCustomerId = null;
    var $_exportPending = false;
    var $_lockFileHandle = null;
    var $_maxMessages = 10;
    var $_pollingTimeSeconds = 50;
    var $_pauseTimeBetweenMsgPackagesSeconds = 3;

    public function __construct($tempDir = null) {
        parent::__construct();

        Mage::register(Schracklive_Schrack_Helper_Stomp::MQ_IMPORT_MARKER,true,true);

        $this->_messageType = $this->getArg('type');

        if ( $this->getArg('poll') ) {
            $this->_poll = true;
            if ( ! $this->tryLock() ) {
                die('Detected another instance running for this country.');
            }
            if ( $x = $this->getArg('max_messages') ) {
                $this->_maxMessages = intval($x);
            }

        } else if ( $this->getArg('file') ) {
            $this->_importFile = $this->getArg('file');

            if ($this->getArg('import')) {
                $this->_import = true;
                if (!$this->_messageType) {
                    echo "No message type given.\n";
                    die($this->usageHelp());
                }
            }

            if ($this->getArg('dump')) {
                $this->_dumpFile = true;
                if (!$this->_messageType) {
                    echo "No message type given.\n";
                    die($this->usageHelp());
                }
            }
        } else if ($this->getArg('export-pending')) { // export to s4y like the cronjob would do (process pending accounts)
            $this->_exportPending = true;
        } else if ($this->getArg('export-id')) {
            die('todo, not yet implemented');
        } else if ($this->getArg('help')) {
            die($this->usageHelp());
        } else {
            die($this->usageHelp());
        }
    }

    public function __destruct () {
        $this->unlock();
    }

    private function tryLock () {
        $countryId = Mage::getStoreConfig('schrack/general/country');
        $fileName =  self::FULL_FILENAME_BASE . substr(strtolower($countryId),0,2) . self::FILENAME_EXT;
        if ( file_exists($fileName) ) {
            $this->_lockFileHandle = fopen($fileName,"r+");
        } else {
            $this->_lockFileHandle = fopen($fileName,"w");
        }
        if( ! $this->_lockFileHandle ) {
            die("cannot open file $fileName" . PHP_EOL);
        }
        if ( ! flock($this->_lockFileHandle,LOCK_EX | LOCK_NB) ) {
            fclose($this->_lockFileHandle);
            $this->_lockFileHandle = null;
            return false;
        }

        ftruncate($this->_lockFileHandle, filesize($fileName));
        fwrite($this->_lockFileHandle,'' . getmypid());
        fflush($this->_lockFileHandle);
        return true;
    }

    private function unlock () {
        if ( $this->_lockFileHandle ) {
            flock($this->_lockFileHandle,LOCK_UN);
            fclose($this->_lockFileHandle);
            $this->_lockFileHandle = null;
        }
    }

    public function run() {
        /** @var $helper Schracklive_Account_Helper_Protobuf **/
        $helper = Mage::helper('account/protobuf');
        if ( $this->_poll ) {
            $this->_poll($helper);
        } else if ( $this->_import && $this->_importFile !== null && $this->_messageType !== null ) {
            if ( !file_exists($this->_importFile) || !is_readable($this->_importFile) ) {
                throw new Exception('Unable to read file ' . $this->_importFile);
            }
            $data = file_get_contents($this->_importFile);
            $type = $this->_getTypeFromArg($this->_messageType);
            $helper->importMessage($type, $data);
        } else if ( $this->_dumpFile && $this->_importFile !== null ) {
            if ( !file_exists($this->_importFile) || !is_readable($this->_importFile) ) {
                throw new Exception('Unable to read file ' . $this->_importFile);
            }
            $data = file_get_contents($this->_importFile);
            $type = $this->_getTypeFromArg($this->_messageType);
            $helper->dumpMessage($type, $data);
        } else if ( $this->_exportPending ) {
            $cronjob = Mage::getModel('crm/cronjob');
            $cronjob->processPending();
        } else if ( $this->_exportId && isset($this->_schrackWwsCustomerId) ) {
            die('not yet implemented.');
        } else {
            die('wrong options.' . PHP_EOL . PHP_EOL . $this->usageHelp());
        }
    }

    private function _poll ( $helper ) {

        $stompClient = $helper->createAndSubscribeStompClientFromConfigPaths(Schracklive_Account_Helper_Protobuf::STOMP_URL_CFG_PATH,Schracklive_Account_Helper_Protobuf::STOMP_IN_QUEUE_CFG_PATH);

        echo "Polling...\n";

        $msgCount = 0;
        $spentSeconds = 0;

        do {
            $ts = time();
            while ( $msgCount < $this->_maxMessages && $stompClient->hasFrame() ) {
                $frame = $stompClient->readFrame();
                if ( !$frame ) {
                    echo 'No more messages in queue' . PHP_EOL;
                    break;
                }
                try {
                    $type = $this->_getTypeFromClassName($frame->headers["protobuf_class"]);
                } catch ( Exception $ex ) {
                    Mage::logException($ex);
                    echo 'x';
                    // $helper->sendErrorMessage(null, $frame->body, $ex->getMessage(), $frame->headers);
                    $stompClient->ack($frame);
                    continue;
                }
                if ( $type ) {
                    $helper->importMessage($type, $frame->body, null, $frame->headers);
                    echo ".";
                } else {
                    echo ' ';
                    Mage::log("S4YConnector: ignoring message type " . $frame->headers["protobuf_class"]);
                }
                ++$msgCount;
                $stompClient->ack($frame);
                unset($frame);
            }
            if ( $this->_pollingTimeSeconds > 0 ) {
                echo "p";
                sleep($this->_pauseTimeBetweenMsgPackagesSeconds);
                $ts = time() - $ts;
                $spentSeconds += $ts;
                $msgCount = 0;
            }
        } while ( $this->_pollingTimeSeconds > 0 && $spentSeconds < $this->_pollingTimeSeconds );

        $helper->unsubscribeStompClient($stompClient);
        unset($stompClient);
        $datetime = date('Y-m-d H:i:s');
        echo "\n\nRead $msgCount Messages.\n$datetime Done.\n";
    }

    private function _getTypeFromClassName($className) {
        if ( $className == null ) {
            throw new Exception('protobuf_class: missing header protobuf_class');
        }
        $matches = array();
        $rv = preg_match('/(\w+)$/', $className, $matches);
        if (!$rv) {
            throw new Exception('protobuf_class: class name does not match: ' . $className);
        }
        $realClassName = $matches[1];
        switch ($realClassName) {
            case 'AccountMessage':
                return Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT;
            case 'ContactMessage':
                return Schracklive_Account_Helper_Protobuf::TYPE_CONTACT;
            case 'AddressMessage':
                return Schracklive_Account_Helper_Protobuf::TYPE_ADDRESS;
            case 'ProspectMessage':
                return Schracklive_Account_Helper_Protobuf::TYPE_PROSPECT;
            case 'MailingListTypeMessage':
                return Schracklive_Account_Helper_Protobuf::TYPE_ML_TYPE;
        }
        return null;
    }

    private function _getTypeFromArg($arg) {
        switch ($arg) {
            case "account":
                return Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT;
            case "contact":
                return Schracklive_Account_Helper_Protobuf::TYPE_CONTACT;
            case "address":
                return Schracklive_Account_Helper_Protobuf::TYPE_ADDRESS;
            case "prospect":
                return Schracklive_Account_Helper_Protobuf::TYPE_PROSPECT;
            default:
                throw new Exception('no such protobuf type as ' . $arg);
        }
        return null;
    }

    public function _haveStomp() {
        return $this->_pollAccounts; // || _pollXXX
    }

    public function usageHelp() {
        global $argv;

        return <<<USAGE
   
Usage:  php -f $argv[0] [options]

  --import                          Read from a file
  --poll                            Read from the network / queue
  --export-pending                  Export pending accounts to S4Y
  --export-id <customer-id>         Export this SchrackWwsCustomerId, regardless of its status
  --file <file>                     The protobuf file to import
  --type <account|contact|address>  Type of the imported message. Mandatory if reading from a file, igonred otherwise
  --dump                            Dump a text representation of the given message(s) to stdout

  --help                          this help
  
USAGE;

    }

}

$shell = new Schracklive_Shell_S4YConnector();
$shell->run();

?>

<?php

require_once 'ProtoQueueImportBase.php';

class Schracklive_Shell_ProtoQueueAccountImportDispatcher extends Schracklive_Shell_ProtoQueueImportBase {
    const FILENAME_BASE = 'S4YConnector_';

    var $_runningProcesses = 8;
    var $_messagesPerProcess = 10;
    var $_dryRun = false;


    public function __construct () {
        parent::__construct();
        $x = $this->getArg('dry_run');
        if ( $x ) {
            $this->_dryRun = true;
        }
        // $x = 'AT,BA,BE,BG,COM,CZ,DE,HR,HU,PL,RO,RS,RU,SA,SI,SK';
        $x = Mage::getStoreConfig('schrack/account_import_dispatcher/countries');
        if ( $x ) {
            $this->_countries = explode(',',$x);
        }
        $x = Mage::getStoreConfig('schrack/account_import_dispatcher/running_processes');
        if ( $x ) {
            $this->_runningProcesses = intval($x);
        }
        $x = Mage::getStoreConfig('schrack/account_import_dispatcher/msgs_per_process');
        if ( $x ) {
            $this->_messagesPerProcess = intval($x);
        }
    }

    public function run () {
        $this->log('dispatcher running...');
        $runningCountryIDs = $this->getCurrentRunningProcessCountryIDs();
        var_dump($runningCountryIDs); echo '###' . PHP_EOL;
        $currentRunningCount = count($runningCountryIDs);
        if ( $currentRunningCount >= $this->_runningProcesses ) {
            $this->log("already $currentRunningCount processes running; ...dispatcher done.");
            return;
        }
        $havingQueueDataCountries = $this->getHavingQueueDataCountries();
        $notRunningButHaveDataCountries = array();
        foreach ( $havingQueueDataCountries as $code => $flag ) {
            if ( $flag && ! isset($runningCountryIDs[$code]) ) {
                $notRunningButHaveDataCountries[] = $code;
            }
        }
        while ( count($notRunningButHaveDataCountries) > 0 && $currentRunningCount < $this->_runningProcesses ) {
            if ( count($notRunningButHaveDataCountries) == 1 ) {
                $ndx = 0;
            } else {
                $ndx = rand(0, count($notRunningButHaveDataCountries) - 1);
            }
            $code = $notRunningButHaveDataCountries[$ndx];
            array_splice($notRunningButHaveDataCountries,$ndx,1);
            $this->startWorker($code);
            ++$currentRunningCount;
        }
        $this->log('...dispatcher done.');
    }

    private function startWorker ( $countryCode ) {
        $countryCode = substr(strtolower($countryCode),0,2);
        $worker = "/var/www/$countryCode/htdocs/shell/local/S4YConnector.php";
        if ( $this->_messagesPerProcess ) {
            $worker .= (' --poll --max_messages ' . $this->_messagesPerProcess);
        }
        $out = "/tmp/S4YConnector_$countryCode.out";
        $commandLine = "setsid php $worker >> $out 2>&1 &";
        $this->log("Starting worker for country $countryCode : $commandLine");
        if ( ! $this->_dryRun ) {
            exec($commandLine);
        }
        sleep(1);
    }

    protected function getLogFileName () {
        return 'proto_queue_account_dispatcher.log';
    }

    protected function getPidFileNameBase () {
        return self::FILENAME_BASE;
    }

    protected function getInQueueCoreConfigPath () {
        return Schracklive_Account_Helper_Protobuf::STOMP_IN_QUEUE_CFG_PATH;
    }

    protected function getStompUrlCoreConfigPath () {
        return Schracklive_Account_Helper_Protobuf::STOMP_URL_CFG_PATH;
    }

}

chdir("/"); // necessary, because bloody php/zend/magento finds installations relative to working
            // dir instead the right ones in the absolute path in launched child processes
$shell = new Schracklive_Shell_ProtoQueueAccountImportDispatcher();
$shell->run();

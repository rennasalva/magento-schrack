<?php

// define('QUEUE_DEBUG',true);

require_once 'ProtoQueueProductImportBase.php';

class Schracklive_Shell_ProtoQueueProductImportDispatcher extends Schracklive_Shell_ProtoQueueProductImportBase {

    var $_country2PrioMap = array(
        'AT'  => 1,
        'BA'  => 2,
        'BE'  => 2,
        'BG'  => 2,
        'CH'  => 3,
        'COM' => 3,
        'CZ'  => 2,
        'DE'  => 3,
        'HR'  => 2,
        'HU'  => 2,
        'NL'  => 4,
        'PL'  => 2,
        'RO'  => 2,
        'RS'  => 2,
        'RU'  => 4,
        'SA'  => 4,
        'SI'  => 2,
        'SK'  => 2
    );

    var $_runningProcesses = 8;
    var $_runningFinalizeProcesses = 4;
    var $_messagesPerProcess = null;
    var $_startTime;
    var $_stopTime;
    var $_stopFinalizeTime;
    var $_currentTime;
    var $_isNonStopDay = null;
    var $_dryRun = false;
    var $_stompHelper = null;


    public function __construct () {
        parent::__construct();
        if ($this->getArg('help')) {
            die($this->usageHelp());
        }
        // $x = 'AT=1,BA=2,BE=2,BG=2,COM=3,CZ=2,DE=3,HR=2,HU=2,PL=2,RO=2,RS=2,RU=4,SA=4,SI=2,SK=2';
        $x = Mage::getStoreConfig('schrack/product_import_dispatcher/contries_and_priorities');
        if ( $x ) {
            $this->_country2PrioMap = array();
            $xarr = explode(',', $x);
            foreach ( $xarr as $y ) {
                $y = trim($y);
                $yarr = explode('=',$y);
                $this->_country2PrioMap[trim($yarr[0])] = intval(trim($yarr[1]));
            }
        }
        asort($this->_country2PrioMap);
        $x = Mage::getStoreConfig('schrack/product_import_dispatcher/running_processes');
        if ( $x ) {
            $this->_runningProcesses = intval($x);
        }
        $x = Mage::getStoreConfig('schrack/product_import_dispatcher/running_finalize_processes');
        if ( $x ) {
            $this->_runningFinalizeProcesses = intval($x);
        }
        $x = $this->getArg('process-count');
        if ( $x ) {
            $this->_runningProcesses = intval($x);
        }
        $x = $this->getArg('finalize-process-count');
        if ( $x ) {
            $this->_runningFinalizeProcesses = intval($x);
        }
        $x = $this->getArg('msgs_per_process');
        if ( $x ) {
            $this->_messagesPerProcess = intval($x);
        }
        $x = $this->getArg('dry_run');
        if ( $x ) {
            $this->_dryRun = true;
        }
        $x = $this->getArg('force_nonstop');
        if ( $x ) {
            $this->_isNonStopDay = true;
        }
        $this->_stompHelper =  Mage::helper('schrack/stomp');

        $this->_startTime   = $this->getConfigTimeInSeconds('schrack/product_import_dispatcher/begin_working_time');
        $this->_stopTime    = $this->getConfigTimeInSeconds('schrack/product_import_dispatcher/end_working_and_start_finalizing_time');
        $this->_stopFinalizeTime    = $this->getConfigTimeInSeconds('schrack/product_import_dispatcher/latest_finalize_start_time');

        $this->_currentTime = $this->getCurrentTimeInSeconds();
        if ( $this->isNonStopWorkingDay() ) {
            $this->log('Dispatcher started. Today is non-stop day.');
        } else {
            $this->log('Dispatcher started. start time: ' . $this->_startTime . ', current time: ' . $this->_currentTime . ', stop time: ' . $this->_stopTime. ', stop finalize time: ' . $this->_stopFinalizeTime);
        }
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("START: Dispatcher",null,'queue.log');
        }
    }

    public function __destruct () {
        $this->log('Dispatcher finished.');
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("STOP: Dispatcher",null,'queue.log');
        }
    }

    public function run () {
        $runningCountryIDs = $this->getCurrentRunningProcessCountryIDs();
        $currentRunningCount = count($runningCountryIDs);
        $noRunningCountries = $this->_country2PrioMap;
        foreach ( $runningCountryIDs as $code ) {
            unset($noRunningCountries[$code]);
        }
        if ( $this->inWorkingTimeRange() ) {
            $havingQueueDataCountries = $this->getHavingQueueDataCountries();
        } else {
            $havingQueueDataCountries = array();
        }
        $currentPrioNRCountries = array();
        $maxCnt = $this->inWorkingTimeRange() ? $this->_runningProcesses : $this->_runningFinalizeProcesses;
        while ( $currentRunningCount < $maxCnt && count($noRunningCountries) > 0 ) {
            if ( count($currentPrioNRCountries) == 0 ) {
                $currentPrioNRCountries = $this->getHighestPrioCountries($noRunningCountries);
                if ( count($currentPrioNRCountries) == 0 ) {
                    break; // nothing more to do
                }
            }
            $nexCountry = array_shift($currentPrioNRCountries);
            if ( $this->inWorkingTimeRange() ) {
                if ( $havingQueueDataCountries[$nexCountry] ) {
                    $this->setFinalizeTodo($nexCountry);
                    $this->startWorker($nexCountry);
                    ++$currentRunningCount;
                }
            } else if($this->_currentTime < $this->_stopFinalizeTime) {
                if ( $this->hasFinalizeTodo($nexCountry) ) {
                    $this->startWorker($nexCountry,true);
                    $this->removeFinalizeTodo($nexCountry);
                    ++$currentRunningCount;
                }
            } else {
                if ( $this->hasFinalizeTodo($nexCountry) ) {
                    $this->log("Finalize for country $nexCountry postponed because it's after the latest finalize start time.");
                }
            }
        }
    }

    protected function getLogFileName () {
        return 'proto_queue_product_dispatcher.log';
    }

    protected function getCountries () {
        return array_keys($this->_country2PrioMap);
    }

    private function setFinalizeTodo ( $country ) {
        if ( ! $this->_dryRun ) {
            touch ($this->getFinalizeTodoFileName($country));
        }
    }

    private function hasFinalizeTodo ( $country ) {
        return file_exists($this->getFinalizeTodoFileName($country));
    }

    private function removeFinalizeTodo ( $country ) {
        unlink($this->getFinalizeTodoFileName($country));
    }

    private function getFinalizeTodoFileName ( $country ) {
        return '/tmp/finalize_' . $country . '.todo';
    }

    private function inWorkingTimeRange () {
        if ( $this->isNonStopWorkingDay() ) {
            return true;
        }
        if ( $this->_startTime < $this->_stopTime ) {
            return $this->_currentTime > $this->_startTime && $this->_currentTime < $this->_stopTime;
        } else {
            return $this->_currentTime > $this->_startTime || $this->_currentTime < $this->_stopTime;
        }
    }

    private function isNonStopWorkingDay () {
        if ( $this->_isNonStopDay == null ) {
            $this->_isNonStopDay = false;
            $x = Mage::getStoreConfig('schrack/product_import_dispatcher/non_stop_weekdays');
            if ($x) {
                $currentDay = date('w');
                $days = explode(',', $x);
                foreach ( $days as $day ) {
                    if ( $day === $currentDay ) {
                        $this->_isNonStopDay = true;
                        break;
                    }
                }
            }
        }
        return $this->_isNonStopDay;
    }

    private function startWorker ( $countryCode, $finalize = false ) {
        $countryCode = substr(strtolower($countryCode),0,2);
        $worker = Mage::getStoreConfig('schrack/product_import_dispatcher/worker_script');
        if ( ! $worker ) {
            $worker = "/var/www/html/%country%/htdocs/shell/local/ProtoQueueProductImport.php";
        }
        if ( $finalize ) {
            $worker .= ' --finalize';
        }
        if ( $this->_messagesPerProcess ) {
            $worker .= ' --max_messages ' . $this->_messagesPerProcess;
        }
        $x = Mage::getStoreConfig('schrack/product_import_dispatcher/commandline');
        if ( $x ) {
            $commandLine = str_replace('%script%',$worker,$x);
        } else {
            $commandLine = "setsid php $worker > /dev/null 2>&1 &";
        }
        $commandLine = str_replace('%country%',$countryCode,$commandLine);
        if ( $this->_dryRun ) {
            $this->log("DRY: would start worker for country $countryCode : $commandLine");
        } else {
            $this->log("Starting worker for country $countryCode : $commandLine");
            exec($commandLine);
        }
        sleep(1);
    }

    private function getHighestPrioCountries ( &$srcArray ) {
        $res = array();
        if ( count($srcArray) == 0 ) {
            throw new Exception("SNH: no countries given!");
        }
        $countries = array_keys($srcArray);
        $res[] = $countries[0];
        $prio = array_shift($srcArray);
        for ( $i = 1; $i < count($srcArray) && $srcArray[$countries[$i]] == $prio; ++$i ) {
            $res[] = $countries[$i];
            array_shift($srcArray);
        }
        shuffle($res);
        return $res;
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f ProtoQueueProductImportDispatcher.php [--process-count <cnt>] [--finalize-process-count <cnt>] [--msgs_per_process <cnt>] [--dry_run] [--force_nonstop]



USAGE;
    }

}

chdir("/"); // necessary, because bloody php/zend/magento finds installations relative to working
            // dir instead the right ones in the absolute path in launched child processes
$shell = new Schracklive_Shell_ProtoQueueProductImportDispatcher();
$shell->run();

?>

<?php

require_once 'shell.php';

abstract class Schracklive_Shell_ProtoQueueImportBase extends Schracklive_Shell {

    protected $_countries = array('AT','BA','BE','BG','CH','COM','CZ','DE','HR','HU','NL','PL','RO','RS','RU','SA','SI','SK');
    protected $_stompHelper;
    protected $_magentoOptions;
    protected $_pidFileName;
    protected $_semaphor;

    public function __construct () {
		parent::__construct();
        $this->_magentoOptions = Mage::getConfig()->getOptions();
        /** @var Schracklive_Schrack_Helper_Stomp _stompHelper */
        $this->_stompHelper =  Mage::helper('schrack/stomp');
    }

    abstract protected function getPidFileNameBase ();
    abstract protected function getInQueueCoreConfigPath ();

    protected function aquireSemaphore () {
        $this->_pidFileName = $this->getPidFileName();
        $aquired = false;
        $pid = getmypid();
        if ( file_exists($this->_pidFileName) ) {
            $no = ftok($this->_pidFileName,'I');
            $this->_semaphor = sem_get($no);
            sem_acquire($this->_semaphor);
            $aquired = true;
        }
        $fp = fopen($this->_pidFileName,'w');
        fwrite($fp,'' . $pid);
        fclose($fp);
        if ( ! $aquired ) {
            $no = ftok($this->_pidFileName, 'I');
            $this->_semaphor = sem_get($no);
            sem_acquire($this->_semaphor);
        }
    }

    protected function releaseSemaphore () {
        sem_release($this->_semaphor);
        unlink($this->_pidFileName);
        sem_remove($this->_semaphor); // not sure, if that is a good idea - we'll try it out...

    }


    protected function getLogFileName () {
        return 'proto_queue.log';
    }

    protected function getCountries () {
        return $this->_countries;
    }

    protected function getStompUrlCoreConfigPath () {
        return 'schrack/product_import/stomp_url';
    }

    protected function getPidFileName ( $countryId = null ) {
        if ( ! $countryId ) {
            $countryId = $this->getCountryId();
        }
        return '/tmp/' . $this->getPidFileNameBase() . substr(strtolower($countryId),0,2) . '.pid';
    }

    protected function getQueueName ( $countryId = null ) {
        $base = Mage::getStoreConfig($this->getInQueueCoreConfigPath());
        $p = strrpos($base,'$$');
        if ( $p != false ) {
            $base = substr($base,0,$p);
            return $base;
        }
        if ( ! $countryId ) {
            $countryId = $this->getCountryId();
        }
        return $base . '_' . strtoupper($countryId);
    }

    protected function getQueuePathForName ( $queueName ) {
        return '/queue/' . $queueName;
    }

    protected function getCountryId () {
        return Mage::getStoreConfig('schrack/general/country');
    }

    protected function getStompUrl () {
        $stompUrl = Mage::getStoreConfig($this->getStompUrlCoreConfigPath());
        if ( ! $stompUrl ) {
            throw new Exception('No Stomp URL configured!');
        }
        return $stompUrl;
    }

    protected function getHavingQueueDataCountries () {
        $res = array();
        // $stompClient = $this->_stompHelper->createStompClientFromConfigPath($this->getStompUrlCoreConfigPath());
        foreach ( $this->getCountries() as $country ) {
            // DLA, 20170920: bloody php stomp client keeps some information from previous subscription/reading,
            // so that it has to be created newly for every loop :-((
            $stompClient = $this->_stompHelper->createStompClientFromConfigPath($this->getStompUrlCoreConfigPath());
            $queue = $this->_stompHelper->getCountryQueuePath($this->getInQueueCoreConfigPath(),$country);
            $stompClient->subscribe($queue);
            $res[$country] = false;
            $msg = null;
            if ( $stompClient->hasFrame() && ($msg = $stompClient->readFrame()) ) {
                $res[$country] = true;
            }
            if ( defined('QUEUE_DEBUG') ) {
                Mage::log('country ' . $country . ' = ' . ($res[$country] ? 'true' : 'false'),null,'queue.log');
            }
            $stompClient->unsubscribe($queue);
        }
        unset($stompClient);
        return $res;
    }

    protected function checkProcessIsRunningForCountryCode ( $code ) {
        $fn = $this->getPidFileName($code);
        if ( file_exists($fn) ) {
            $fp = fopen($fn, 'r');
            $pid = fgets($fp);
            fclose($fp);
            if ( $this->isProcessRunning($pid) ) {
                return true;
            } else {
                unlink($fn);
            }
        }
        return false;
    }

    protected function isProcessRunning ( $pid ) {
        $output = array();
        exec("ps -ef | grep $pid | grep -v grep", $output);
        return count($output) > 0;
    }

    protected function getCurrentRunningProcessCountryIDs () {
        $res = array();
        foreach ( $this->getCountries() as $code ) {
            if ( $this->checkProcessIsRunningForCountryCode($code) ) {
                $res[] = $code;
            }
        }
        return $res;
    }

    protected function log ( $data, $echo = true ) {
        $data = "[".date("y.m.d H:i:s")."] ".$data."\n";
        if ( $echo ) {
            echo $data;
        }
        $log = fopen($this->_magentoOptions['log_dir'].DS.$this->getLogFileName(), 'a');
        fwrite($log, $data);
        fclose($log);
    }

}

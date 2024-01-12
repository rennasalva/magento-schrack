<?php
class Schracklive_Schrack_Helper_Stomp extends Mage_Core_Helper_Abstract {

    const MQ_IMPORT_MARKER = 'mqimport';

    private $_receiptHeader = false;

    public function getReceiptHeader () {
        if ( ! $this->_receiptHeader ) {
            $this->_receiptHeader = array(
                'receipt' => 'webshop-' . time() . '-' . rand(1,999999)
            );
        }
        return $this->_receiptHeader;
    }

    public function getQueuePath ( $configPath ) {
        $base = Mage::getStoreConfig($configPath);
        if ( !$base ) {
            throw new Exception("No queue ($configPath) configured!");
        }
        $base = '/queue/' . $base;
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("getQueuePath: configPath = $configPath, base = $base",null,'queue.log');
        }
        return $base;
    }

    public function getCountryQueuePath ( $configPath, $countryId = null ) {
        $base = $this->getQueuePath($configPath);
        $p = strrpos($base,'$$');
        if ( $p != false ) {
            $base = substr($base,0,$p);
            if ( defined('QUEUE_DEBUG') ) {
                Mage::log("getCountryQueuePath: configPath = $configPath, base = $base",null,'queue.log');
            }
            return $base;
        }
        if ( ! $countryId ) {
            $countryId = Mage::getStoreConfig('schrack/general/country');
        }
        $base = $base . '_' . strtoupper($countryId);
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("getCountryQueuePath: configPath = $configPath, base = $base",null,'queue.log');
        }
        return $base;
    }

    public function createAndSubscribeStompClientFromConfigPaths ( $urlCfgPath, $queueCfgPath ) {
        $queue = $this->getCountryQueuePath($queueCfgPath);
        $url = $this->getStompUrl($urlCfgPath);
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("createAndSubscribeStompClientFromConfigPaths: url = $url, queue = $queue",null,'queue.log');
        }
        return $this->createAndSubscribeStompClient($url,$queue);
    }

    public function createStompClientFromConfigPath ( $urlCfgPath ) {
        $url = $this->getStompUrl($urlCfgPath);
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("createStompClientFromConfigPath: url = $url",null,'queue.log');
        }
        return $stompClient = new Stomp($url);
    }

    public function createAndSubscribeStompClient ( $url, $queuePath ) {
        $stompClient = new Stomp($url);
        $stompClient->subscribe($queuePath,$this->getReceiptHeader());
        $stompClient->___memorizedQueuePath = $queuePath;
        if ( defined('QUEUE_DEBUG') ) {
            Mage::log("createAndSubscribeStompClient: url = $url",null,'queue.log');
        }
        return $stompClient;
    }

    public function unsubscribeStompClient ( $stompClient ) {
        if ( isset($stompClient->___memorizedQueuePath) ) {
            $stompClient->unsubscribe($stompClient->___memorizedQueuePath, $this->getReceiptHeader());
            unset($stompClient->___memorizedQueuePath);
        }
    }

    public function getStompUrl ( $cfgPath ) {
        $url = Mage::getStoreConfig($cfgPath);
        if ( ! $url ) {
            throw new Exception("No Stomp URL ($cfgPath) configured!");
        }
        return $url;
    }

}
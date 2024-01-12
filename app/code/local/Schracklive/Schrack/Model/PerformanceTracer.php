<?php

class Schracklive_Schrack_Model_PerformanceTracer {
    private $SID, $file, $startTimeMS, $lastTimeMS, $logFileName, $filterUserEmail = false;

    public function __construct (  $file, $logFileName = 'performance_trace.log' ) {
        $this->SID = Mage::getSingleton('core/session')->getEncryptedSessionId(); //current session id
        $this->file = basename($file);
        $this->lastTimeMS = $this->startTimeMS = $this->getTimeMS();
        $this->logFileName = $logFileName;
    }

    public function filterUserEmail ( $userEmail ) {
        $this->filterUserEmail = $userEmail;
    }

    public function trace ( $line, $message = false ) {
        if (    $this->filterUserEmail
             && (   ! Mage::getSingleton('customer/session')->getCustomer()
                 || Mage::getSingleton('customer/session')->getCustomer()->getEmail() != $this->filterUserEmail) ) {
            return;
        }
        $curTimeMS = $this->getTimeMS();
        $msSinceLastTime = $curTimeMS -  $this->lastTimeMS;
        $msSinceStart = $curTimeMS -  $this->startTimeMS;
        if ( ! $message ) {
            $message = '';
        } else {
            $message = '- ' . $message;
        }
        Mage::log("{$this->file}:$line session: {$this->SID} ms: $msSinceLastTime, ms total: $msSinceStart $message",null,$this->logFileName);
        $this->lastTimeMS = $curTimeMS;
    }

    private function getTimeMS () {
        return (int) round(microtime(true) * 1000);
    }
}

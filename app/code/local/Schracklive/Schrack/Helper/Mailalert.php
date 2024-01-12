<?php

class Schracklive_Schrack_Helper_Mailalert {

    private $_mailInfoMap = array();
    private $_writeConnection, $_readConnection;

    function __construct() {
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    public function createAlertMails ( $message, $subject = null, $throwException = false ) {
        if ( $this->checkSendMails($message) ) {
            $to = Mage::getStoreConfig('schrackdev/alertmails/recipients');
            if ( strpos($to,',') !== false ) {
                $to = explode(',',$to);
            }
            $from = Mage::getStoreConfig('web/secure/base_url');
            try {
                $mail = new Zend_Mail('utf-8');
                $mail->setFrom($from)
                    ->setSubject($subject ? $subject : $message)
                    ->setBodyHtml($message)
                    ->addTo($to)
                    ->send();
                $this->saveSentMailInfo($message);
            } catch ( Exception $ex ) {
                Mage::logException($ex);
                if ( $throwException ) {
                    throw $ex;
                }
            }
        }
    }

    private function checkSendMails ( $msg ) {
        $sql = "SELECT * FROM mail_alerts WHERE message = ?";
        $rows = $this->_readConnection->fetchAll($sql,$this->prepareMsg4db($msg));
        if ( count($rows) == 0 ) {
            // never sent before
            return true;
        }
        $rec = reset($rows);
        $this->_mailInfoMap[$msg] = $rec;
        $now = time();
        $lastMailTime = strtotime($rec['last_email_time']);
        if ( $now - $lastMailTime > (60 * 60) ) {
            // last mail older than 1 hour
            return true;
        }
        $lastOccurrenceTime = strtotime($rec['last_occurrence_time']);
        $occurrenceCount = intval($rec['occurrences_since_last_mail']);
        if ( $occurrenceCount > 100 && $now - $lastMailTime > (60 * 10) ) {
            // last mail at least 10 minutes ago and meanwhile at least 100 more occurrences
            return true;
        }
        // else just update db info
        $currentTimestamp = date('Y-m-d H:i:s');
        $this->updateDb($msg,$currentTimestamp,$rec['last_email_time'],$occurrenceCount + 1);
        return false;
    }
    
    
    private function saveSentMailInfo ( $msg ) {
        $currentTimestamp = date('Y-m-d H:i:s');
        if ( ! isset($this->_mailInfoMap[$msg]) ) {
            // insert new
            $sql = " INSERT INTO mail_alerts (message,last_occurrence_time,last_email_time,occurrences_since_last_mail)"
                 . " VALUES(?,?,?,0)";
            $this->_writeConnection->query($sql,array($this->prepareMsg4db($msg),$currentTimestamp,$currentTimestamp));
        } else {
            // update
            $this->updateDb($msg,$currentTimestamp,$currentTimestamp,0);
        }
    }

    private function updateDb ( $msg, $occTime, $mailTime, $occCount ) {
        $sql = " UPDATE mail_alerts SET last_occurrence_time= :ot, last_email_time= :mt, occurrences_since_last_mail= :cnt"
             . " WHERE message= :msg";
        $this->_writeConnection->query($sql,
            array('ot' => $occTime, 'mt' => $mailTime, 'cnt' => $occCount, 'msg' => $this->prepareMsg4db($msg))
        );
    }

    private function prepareMsg4db ( $msg ) {
        return substr($msg,0,255);
    }
}


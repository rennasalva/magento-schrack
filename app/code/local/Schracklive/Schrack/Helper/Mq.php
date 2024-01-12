<?php

class Schracklive_Schrack_Helper_Mq {


    public function isLatestUpdate ( $key, $currentTsStr ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT last_modified_ts FROM msg_modification_timestamps WHERE msg_key = '$key';";
        $savedTsStr = $readConnection->fetchOne($sql);
        if ( ! $savedTsStr ) {
            return true;
        }
        $cmp = $this->compareStrTimestamps($savedTsStr,$currentTsStr);
        if ( $cmp <= 0 ) {
            Mage::log ("WARNING: ts compare for key '$key': last ts = $savedTsStr, current ts = $currentTsStr, cmp = $cmp", null, 'ts.log');
        }
        return $cmp > 0;
    }

    public function saveLatestUpdate ( $key, $currentTsStr ) {
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO msg_modification_timestamps (msg_key,last_modified_ts) VALUES ('$key','$currentTsStr') ON DUPLICATE KEY UPDATE last_modified_ts=VALUES(last_modified_ts);";
        $writeConnection->query($sql);
    }

    public function removeTimestamp ( $key ) {
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "DELETE FROM msg_modification_timestamps WHERE msg_key = '$key';";
        $writeConnection->query($sql);
    }

    /**
     * compare timestamps in format yyyy-MM-dd hh:mm:ss.SSS
     * @param $left
     * @param $right
     * @return int < 0 means $left > $right, 0 means eqaual, > 0 means $right > $left
     */
    public function compareStrTimestamps ( $left, $right ) {
        $leftTs = strtotime($right);
        $rightTs = strtotime($left);
        $res = $leftTs - $rightTs;
        if ( $res != 0 )
            return $res;
        // else, if equal, we have to look at the milliseconds (strtotime returns just seconds):
        $leftTs = intval(explode('.', $right)[1]);
        $rightTs = intval(explode('.', $left)[1]);
        $res = $leftTs - $rightTs;
        return $res;
    }
}

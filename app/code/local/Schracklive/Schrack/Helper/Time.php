<?php

class Schracklive_Schrack_Helper_Time extends Mage_Core_Helper_Abstract {

    public function getCurrentTimeInSeconds () {
        return time() % 86400;
    }

    public function getConfigTimeInSeconds ( $path ) {
        $x = Mage::getStoreConfig($path);
        if ( ! $x ) {
            return null;
        }
        $xarr = explode('.',$x);
        if ( count($xarr) < 3 ) {
            $xarr = explode(',',$x); // fu*ing locale...
        }
	    return $this->getTimeInSecondsAr($xarr);
    }

    public function getTimeInSecondsHMS ( $h, $m, $s ) {
	    return $this->getTimeInSecondsAr(array($h,$m,$s));
    }

    private function getTimeInSecondsAr ( $xarr ) {
        return intval($xarr[0]) * 3600 + intval($xarr[1]) * 60 + intval($xarr[2]);
    }

}

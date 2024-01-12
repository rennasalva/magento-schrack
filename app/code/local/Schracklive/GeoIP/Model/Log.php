<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Log
 *
 * @author c.friedl
 */
class Schracklive_GeoIP_Model_Log extends Mage_Core_Model_Abstract {
    public function _construct()
    {
        parent::_construct();
        $this->_init('geoip/log');
    }
    
    public function log($sourceHost, $sourceUri, $userCountryId, $targetCountryId, $userIp=null, $userAgent=null) {
        try {
            $this->setSourceHost($sourceHost);
            $this->setSourceUri($sourceUri);        
            $this->setUserCountryId($userCountryId);
            $this->setTargetCountryId($targetCountryId);
            $this->setUserIp($userIp);
            $this->setUserAgent($userAgent);
            $this->save();
        } catch(Exception $e) { // it makes little sense to bark an error to the user when we're just logging
            Mage::logException($e);
        }
    }
    
    /**
     * Set date of last update for tracking table
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();        
        if (!$this->getLogId())
            $this->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        return $this;
    }    
}

?>

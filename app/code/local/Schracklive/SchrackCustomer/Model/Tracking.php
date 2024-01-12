<?php

class Schracklive_SchrackCustomer_Model_Tracking extends Schracklive_SchrackCustomer_Model_AbstractTracking {
    
    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schrackcustomer/tracking');
    }
        
    /**
     * set the session id - this is not done via the automagic __call method,
     * because we sha1 the session before storing, so there is no direct link
     * from session-id to customer-id in the database
     * 
     * @param string $sessionId
     */
    public function setSessionId($sessionId) {
        parent::setSessionId(sha1($sessionId));
    }
    
    /**
     * increment the counter
     */
    public function increment() {
        $cnt = $this->getCnt();
        if (isset($cnt))
            ++$cnt;
        $this->setCnt($cnt);
    }
    
    public function loadBySessionIdAndSku($sessionId, $sku) {
        $sessionId = sha1($sessionId);
        $collection = $this->getCollection()
            ->addFieldToFilter('session_id', array('=' => $sessionId))
            ->addFieldToFilter('sku', array('=' => $sku))
            ->load();
        $item = $collection->getFirstItem();
        $this->addData($item->getData());
        return $this;
    }
    
     public function loadLastBySessionId($sessionId) {
        $sessionId = sha1($sessionId);
        $collection = $this->getCollection()
            ->addFieldToFilter('session_id', array('=' => $sessionId));
        $collection
            ->getSelect()->order('created_at DESC');
        $collection->load();
        $item = $collection->getFirstItem();
        
        return $item;
    }
        
    
    public function setCustomerIdToSession($sessionId, $schrackWwsCustomerId, $schrackWwsContactNumber) {
        $collection = $this->getCollection()
            ->addFieldToFilter('session_id', array('=' => sha1($sessionId)));
        foreach ($collection as $item) {  
            $item->setSchrackWwsCustomerId($schrackWwsCustomerId);
            $item->setSchrackWwsContactNumber($schrackWwsContactNumber);
            $item->save();
        }      
        
        return $this;
    }
}
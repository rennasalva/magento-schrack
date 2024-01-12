<?php
class Anowave_Ec_Model_Resource_Ab_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	protected function _construct()
    { 
        $this->_init('ec/ab');
    }
    
    public function addStoreFilter($store)
    {
    	if ($store instanceof Mage_Core_Model_Store) 
    	{
    		$store = array
    		(
    			$store->getId()
    		);
    	}
    	
    	if (!is_array($store)) 
    	{
    		$store = array($store);
    	}
    	
    	$this->addFieldToFilter('ab_store_id', array
    	(
    		'in' => $store
    	));

    	return $this;
    }
}

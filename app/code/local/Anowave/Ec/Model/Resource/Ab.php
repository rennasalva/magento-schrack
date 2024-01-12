<?php
class Anowave_Ec_Model_Resource_Ab extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
    {
        $this->_init('ec/ab','ab_id');
    }
}
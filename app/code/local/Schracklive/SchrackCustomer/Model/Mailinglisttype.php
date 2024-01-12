<?php
/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 20.10.2016
 * Time: 16:07
 */ 
class Schracklive_SchrackCustomer_Model_Mailinglisttype extends Mage_Core_Model_Abstract
{

    protected function _construct () {
        $this->_init('schrackcustomer/mailinglisttype');
    }

    public function loadByCode ( $code ) {
        $this->getResource()->loadByCode($this,$code);
        return $this;
    }

}
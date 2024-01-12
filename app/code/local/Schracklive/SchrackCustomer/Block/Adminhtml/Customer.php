<?php

class Schracklive_SchrackCustomer_Block_Adminhtml_Customer extends Mage_Adminhtml_Block_Customer {

    public function __construct()
    {
        parent::__construct();
        $this->_removeButton('add');
    }

}
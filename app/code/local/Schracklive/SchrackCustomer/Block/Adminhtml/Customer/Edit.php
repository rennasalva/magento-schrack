<?php

class Schracklive_SchrackCustomer_Block_Adminhtml_Customer_Edit extends Mage_Adminhtml_Block_Customer_Edit {

    public function __construct()
    {
        parent::__construct();

        $this->_removeButton('delete');
    }
}
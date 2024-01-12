<?php

class Schracklive_SchrackCustomer_Block_Adminhtml_Customer_Grid extends Mage_Adminhtml_Block_Customer_Grid {

    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();
        $this->getMassactionBlock()->removeItem('delete');
        return $this;
    }
}
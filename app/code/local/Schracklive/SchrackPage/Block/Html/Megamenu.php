<?php

class Schracklive_SchrackPage_Block_Html_Megamenu extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime' => 3600,
        ));
        $this->setCacheKey('html_megamenu_' . md5(serialize(Mage::app()->getRequest()->getParams())));
    }
}

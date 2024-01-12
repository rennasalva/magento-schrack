<?php

class Schracklive_MoCalc_Block_Html extends Mage_Page_Block_Html {
    /** @var Schracklive_MoCalc_Helper_Data $helper */
    private $helper;

    public function __construct () {
        parent::__construct();
        $this->helper = Mage::helper('moCalc');
    }

    public function getMoData () {
        return $this->helper->getData();
    }

    public function getPossibleProperties ( $name ) {
        return $this->helper->getPossibleProperties($name);
    }

    public function getPossibleBaseAccessories  ( $name ) {
        return $this->helper->getPossibleBaseAccessories($name);
    }

    public function getPossibleOptionalAccessories  () {
        return $this->helper->getPossibleOptionalAccessories();
    }
}

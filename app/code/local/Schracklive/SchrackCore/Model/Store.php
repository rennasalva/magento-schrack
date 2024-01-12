<?php

class Schracklive_SchrackCore_Model_Store extends Mage_Core_Model_Store {
    public function formatPrice($price, $includeContainer = true)
    {
        if ($this->getCurrentCurrency()) {
            return $this->getCurrentCurrency()->format($price, array('symbol' => null, 'display' => Zend_Currency::NO_SYMBOL),false); /// xian XXXXXX
        }
        return $price;
    }
    
    public function formatCurrency() {
        return $this->getCurrentCurrencyCode();
    }
    
}

?>

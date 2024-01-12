<?php

class Schracklive_SchrackSales_Model_Quote_Item extends Mage_Sales_Model_Quote_Item {

    /**
     * Quote Item Before Save prepare data process
     *
     * @return Mage_Sales_Model_Quote_Item
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $loggedInCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
        if (isset($loggedInCustomer)) {
            $this->setCreatedBy($loggedInCustomer->getId());
        } else {
            $this->setCreatedBy(Mage::getSingleton('customer/session')->getCustomer()->getId());
        }
        return $this;
    }    
    
	public function representProduct($product) {
		if (!parent::representProduct($product)) {
			return false;
		}
		if ($product->getSchrackDrumNumber() != $this->getSchrackDrumNumber()) {
			return false;
		}
        if ( $product->getCustomOption('unique_position') ) {
            return false;
        }
		return true;
	}

    public function getProduct () {
        $product = parent::getProduct();
        if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
            Mage::helper('schrackcatalog/preparator')->prepareProduct($product);
        }
        return $product;
    }

    public function calcRowTotal()
    {
        $qty        = $this->getTotalQty();
        // Remove that wrong rounding here - that's the reason for overriding that method
        $total      = $this->getCalculationPriceOriginal() * $qty;
        $baseTotal  = $this->getBaseCalculationPriceOriginal() * $qty;

        $this->setRowTotal($this->getStore()->roundPrice($total));
        $this->setBaseRowTotal($this->getStore()->roundPrice($baseTotal));
        return $this;
    }
}

?>
<?php

class Schracklive_SchrackCheckout_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer {

	protected $_drums = array(); // sku-drumNumber keyed because a block is a singleton

	public function getDrumName() {
		return $this->_getDrum()->getName();
	}

	public function getDrumNumber() {
		$item = $this->getItem();
        return $item->getSchrackDrumNumber();
	}
    
    public function isQuotePickup () {
		$item = $this->getItem();
        $res = $item->getQuote()->getIsPickup();
        return $res;
    }

	protected function _getDrum() {
		$item = $this->getItem();
		$sku = $this->getItem()->getSku();
		$drumKey = $sku.'-'.$item->getSchrackDrumNumber();
		if (!isset($this->_drums[$drumKey])) {
			$this->_drums[$drumKey] = Mage::getModel('schrackcatalog/drum'); // null object
			if ($item->getSchrackDrumNumber() && Mage::helper('schracksales/quote_item')->hasDrums($item)) {
				$drums = Mage::helper('schrackcatalog/drum')->getDrums($item->getProduct(), Mage::helper('schrackshipping')->getWarehouseIds(), 1);
				foreach ($drums as $warehouseDrums) {
					foreach ($warehouseDrums as $drum) {
						if ($drum->getWwsNumber() == $item->getSchrackDrumNumber()) {
							$this->_drums[$drumKey] = $drum;
							break;
						}
					}
				}
			}
		}
		return $this->_drums[$drumKey];
	}

	public function getDrumDescription() {
		return $this->_getDrum()->getDescription();
	}

    public function getDrumId () {
        return $this->_getDrum()->getWwsNumber();
    }

	public function getSchrackProductQtyunit() {
		return $this->getProduct()->getSchrackQtyunit();
	}

    private function _getSummarizedQtyForProduct($product) {
        $cart = Mage::getSingleton('checkout/cart');
        $quote = $cart->getQuote();
        return $quote->getSummarizedQtyForProduct($product);
    }

    public function getSummarizedQtyForProduct() {
        $product = $this->getItem()->getProduct();
        if ( $product->getSchrackIsCable() === '1' ) {
            return $this->_getSummarizedQtyForProduct($product);
        } else {
            return $this->getQty();
        }
    }
}

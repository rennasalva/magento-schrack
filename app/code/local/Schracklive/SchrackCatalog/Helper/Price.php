<?php

class Schracklive_SchrackCatalog_Helper_Price extends Mage_Core_Helper_Abstract {

	public function getFormattedPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer) {
		return Mage::helper('core')->formatPrice($this->_getPriceForCustomer('getTierPriceForCustomer', $product, $customer));
	}

	public function getFormattedBasicPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer) {
		return Mage::helper('core')->formatPrice($this->_getPriceForCustomer('getBasicTierPriceForCustomer', $product, $customer));
	}

    public function getUnformattedBasicPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer, $qty = 1) {
        return Mage::helper('schrackcatalog/info')->getBasicTierPriceForCustomer($product, $qty, $customer);
    }

  	public function getCurrencyForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer, $qty = 1) {
        return Mage::helper('schrackcatalog/info')->getCurrencyForCustomer($product, $qty, $customer);
    }

	protected function _getPriceForCustomer($method, Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer) {
		return Mage::helper('schrackcatalog/info')->$method($product, 1, $customer);
	}

	public function getNextGraduatedPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
		$prices = Mage::helper('schrackcatalog/info')->getGraduatedPricesForCustomer($product, $customer, $qty);
		$priceInfo = array();
		if ($prices) {
			foreach ($prices as $graduatedPrice) {
				if ($graduatedPrice['qty'] > $qty) {
					$priceInfo = $graduatedPrice;
					break;
				}
			}
		}
		return $priceInfo;
	}

	public function getMinimalGraduatedPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer) {
		$prices = Mage::helper('schrackcatalog/info')->getGraduatedPricesForCustomer($product, $customer);
		$priceInfo = array();
		if ($prices) {
			$priceInfo = $prices[count($prices) - 1];
		}
		return $priceInfo;
	}

    public function doOfferProjectPrice(Schracklive_SchrackCatalog_Model_Product $product) {
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $res = Mage::getStoreConfig('schrack/shop/do_offer_project_price');
        $show = ( isset($res) && strlen($res) && $res !== '0' && !$session->isLoggedIn()
            && !Mage::helper('catalog/product')->isSale($product,$customer) );
        if ( $show ) {
            try {
                $price = $this->getUnformattedBasicPriceForCustomer($product, $customer);
            } catch(Exception $e) {
                return false;
            }
            $show = ( doubleval($price) > 0 );
        }
        return $show;
    }
    
    public function doShowListPrice () {
        // Wenn es auf 1 gesetzt ist, dann den Listenpreis anzeigen, der immer aus der STS kommt:
        $res = Mage::getStoreConfig('schrack/shop/do_show_list_price');
        return isset($res) && strlen($res) && $res !== '0';
    }

    public function getProjectPriceUrl($sku)
    {
        $param = sprintf($this->__('Request project price for article %s'), $sku);
        return Mage::getStoreConfig('schrack/typo3/typo3url') . 'onlinecontact/?article=' . urlencode($param);
    }

    /**
     * @note (c.friedl) : The Dieter has approved of this function name!!!!!!!!1111elf
     * @param $product
     * @param $customer
     * @return array(price, currency)
     */
    public function getBasicPriceAndCurrencyForProductAndCustomer($product, $customer, $format = true) {
        $prices = array_reverse(Mage::helper('schrackcatalog/info')->getGraduatedPricesForCustomer($product, $customer));
        $currency = $this->getCurrencyForCustomer($product, $customer);
        if (count($prices) > 0) {
            $price = $prices[0]['price'];
        }
        else {
            try {
                $price = $this->getUnformattedBasicPriceForCustomer($product, $customer);
            } catch(Exception $e) {
                $price = null;
                $currency = null;
            }
        }
        if ( $format ) {
            $price = Mage::helper('core')->formatPrice($price);
        }
        return array($price, $currency);
    }
}

<?php

class Schracklive_SchrackCheckout_Model_Session extends Mage_Checkout_Model_Session {

	/**
	 * Get checkout quote instance by current session
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote() {
        $isFirst = $this->_quote === null;
		parent::getQuote();
        if ( $isFirst ) {
            return $this->_getQuoteAfter();
        }
        return $this->_quote;
	}

	// a separate function is easier to handle during a Magento upgrade
	protected function _getQuoteAfter() {
		// for WWS module
		if ($this->_quote->getIsActive()) {
			Mage::dispatchEvent('schrack_checkout_get_quote_after', array('quote' => $this->_quote));
			if ($this->_quote->getClearQuote()) {
				$this->clear();
				parent::getQuote();
			}
		}
		return $this->_quote;
	}

    public function setQuote($quote) {
        $this->_quote = $quote;
    }

}


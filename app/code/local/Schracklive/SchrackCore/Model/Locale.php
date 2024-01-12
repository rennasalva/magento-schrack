<?php

class Schracklive_SchrackCore_Model_Locale extends Mage_Core_Model_Locale {

	/**
	 * Create Zend_Currency object for current locale
	 *
	 * @param   string $currency
	 * @return  Zend_Currency
	 */
	public function currency($currency) {
		Varien_Profiler::start('locale/currency');
		if (!isset(self::$_currencyCache[$this->getLocaleCode()][$currency])) {
			try {
				$currencyObject = new Zend_Currency($currency, $this->getLocale());
				// SCHRACKLIVE - use ISO code as symbol
				$currencyObject->setFormat(array('symbol' => $currency));
			} catch (Exception $e) {
				$currencyObject = new Zend_Currency($this->getCurrency(), $this->getLocale());
				$options = array(
					'name' => $currency,
					'currency' => $currency,
					'symbol' => $currency
				);
				$currencyObject->setFormat($options);
			}

			self::$_currencyCache[$this->getLocaleCode()][$currency] = $currencyObject;
		}
		Varien_Profiler::stop('locale/currency');
		return self::$_currencyCache[$this->getLocaleCode()][$currency];
	}

}

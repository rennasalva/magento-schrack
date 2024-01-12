<?php

class Schracklive_SchrackSales_Model_Quote_Observer {

	// sales_quote_collect_totals_after
	public function collectTotals($observer) {
		$quote = $observer->getQuote();
		/* @var $item Mage_Sales_Model_Quote */

		foreach ($quote->getAllAddresses() as $address) {
			/* @var $address Mage_Sales_Model_Quote_Address */
			$items = $address->getAllItems();
			foreach ($items as $item) {
				$priceUnit = $item->getProduct()->getSchrackPriceunit();
				$item->setSchrackRowTotalExclSurcharge($item->getQty() * $item->getSchrackBasicPrice() / $priceUnit);
				$item->setSchrackRowTotalSurcharge($item->getQty() * $item->getSchrackSurcharge() / $priceUnit);
			}
		}
	}

}

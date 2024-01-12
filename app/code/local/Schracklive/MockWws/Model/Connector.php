<?php

class Schracklive_MockWws_Model_Connector extends Schracklive_Wws_Model_Connector
{

	public function getProductInfoForCustomer($qty, Mage_Catalog_Model_Product $product, $customer, $returnAll = false)
	{
		if (!Mage::getStoreConfig('schrackdev/wws/info')) {
			return parent::getProductInfoForCustomer($qty, $product, $customer, $returnAll);
		}

		$data = array(
			'price' => 100,
			'surcharge' => 5,
			'currency' =>  Mage::getModel('directory/currency')->load('EUR'),
			'pickupstock' => 70,
			'pickupstate' => 1,
			'pickupqty' => 200,
			'deliverystock' => 80,
			'deliveryqty' => 300,
			'deliverystate' => 0,
			'priceunit' => 'Stk',
			'pricetype' => 1,
		);

		if ($returnAll) {
			return array('70' => $data);
		} else {
			return $data;
		}
	}

	public function fillInWwsQuoteDetails(Mage_Sales_Model_Quote $wwsQuote, $loggedInCustomer=null)
	{
		$this->_placeRequest($request, false);

		if ($createCustomer) {
			// send customer to CRM
		}
	}

	public function fillInWwsOrderDetails(Mage_Sales_Model_Quote $wwsOrder, $loggedInCustomer=null)
	{
		$this->_placeRequest($request, true);
	}

    public function finalizeWwsQuote(Mage_Sales_Model_Quote $wwsQuote, $loggedInCustomer=null)
    {

    }

	protected function _placeRequest($request, $isOrder)
	{
		if (!Mage::getStoreConfig('schrackdev/wws/prepare')) {
			return parent::_placeRequest($request, $isOrder);
		}

		$taxTotal = 0.0;
		foreach ($request->getAllItems() as $item) {
			if ($item->getSchrackKabel()) {
				$item->setSchrackRowTotalExclSurcharge((string)($item->getRowTotal() * 0.9));
				$item->setSchrackSurcharge((string)($item->getRowTotal() * 0.1));
			} else {
				$item->setSchrackRowTotalExclSurcharge($item->getRowTotal());
			}
			$item->setTaxPercent(20);
			$item->setTaxAmount((string)($item->getRowTotal() * 0.2));
			$item->setSchrackBackorderQty(0);
			$item->setSchrackWwsPlaceMemo('');

			$taxTotal += $item->getTaxAmount();
		}

		if (!$request->getSchrackWwsOrderNumber()) {
			$request->setSchrackWwsOrderNumber('TEST' . uniqid());
		}
		$request->setGrandTotal((string)($request->getSubtotal() + $taxTotal));
		$request->setBaseGrandTotal($request->getGrandTotal());
		$request->setSchrackWwsCustomerId($request->getCustomer()->getSchrackWwsCustomerId());
		$request->setSchrackPaymentTerms('ASAP!');
		$request->setSchrackShipmentMode('tomorrow, may be next week');
		$request->setSchrackWwsPlaceMemo('Mock service!');
		$request->setSchrackTaxTotal($taxTotal);

		$address = $request->getShippingAddress();
		$address->setSubtotalInclTax((string)($address->getSubtotal() + $taxTotal));
		$address->setTaxAmount($taxTotal);
		$address->setGrandTotal($request->getGrandTotal());
		$address->setBaseGrandTotal($request->getGrandTotal());
		// required structure - doesn't need to reflect real product/customer tax classes
		$address->setAppliedtaxes(array(
			'MwSt.' => array(
				'rates' => array(
					array(
						'code' => 'MwSt.',
						'title' => 'MwSt.',
						'percent' => 20,
						'position' => 0,
						'priority' => 0,
					)
				),
				'percent' => 20,
				'id' => 'MwSt.',
				'process' => 0,
				'amount' => $taxTotal,
				'base_amount' => $taxTotal,
			)
		));

	}

	public function _finalizeRequest($request, $isOrder, $loggedInCustomer=null)
	{
		if (!Mage::getStoreConfig('schrackdev/wws/finalize')) {
			return parent::finalizeOrder($request);
		}

		$request->setSchrackWwsShipMemo('Mock service!');

		foreach ($request->getAllItems() as $item) {
			$item->setSchrackWwsShipMemo('');
		}
	}

}

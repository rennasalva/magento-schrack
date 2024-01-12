<?php

class Schracklive_SchrackSales_Model_Quote_Address extends Mage_Sales_Model_Quote_Address {

	/**
	 * Request shipping rates for entire address or specified address item
	 * Returns true if current selected shipping method code corresponds to one of the found rates
	 *
	 * @param Mage_Sales_Model_Quote_Item_Abstract $item
	 * @return bool
	 */
	public function requestShippingRates(Mage_Sales_Model_Quote_Item_Abstract $item = null) {
		/** @var $request Mage_Shipping_Model_Rate_Request */
		$request = Mage::getModel('shipping/rate_request');
		$request->setAllItems($item ? array($item) : $this->getAllItems());
		$request->setDestCountryId($this->getCountryId());
		$request->setDestRegionId($this->getRegionId());
		$request->setDestRegionCode($this->getRegionCode());
		/**
		 * need to call getStreet with -1
		 * to get data in string instead of array
		 */
		$request->setDestStreet($this->getStreet(-1));
		$request->setDestCity($this->getCity());
		$request->setDestPostcode($this->getPostcode());
		$request->setPackageValue($item ? $item->getBaseRowTotal() : $this->getBaseSubtotal());
		$packageValueWithDiscount = $item ? $item->getBaseRowTotal() - $item->getBaseDiscountAmount() : $this->getBaseSubtotalWithDiscount();
		$request->setPackageValueWithDiscount($packageValueWithDiscount);
		$request->setPackageWeight($item ? $item->getRowWeight() : $this->getWeight());
		$request->setPackageQty($item ? $item->getQty() : $this->getItemQty());

		/**
		 * Need for shipping methods that use insurance based on price of physical products
		 */
		$packagePhysicalValue = $item ? $item->getBaseRowTotal() : $this->getBaseSubtotal() - $this->getBaseVirtualAmount();
		$request->setPackagePhysicalValue($packagePhysicalValue);

		$request->setFreeMethodWeight($item ? 0 : $this->getFreeMethodWeight());

		/**
		 * Store and website identifiers need specify from quote
		 */
		/* $request->setStoreId(Mage::app()->getStore()->getId());
		  $request->setWebsiteId(Mage::app()->getStore()->getWebsiteId()); */

		$request->setStoreId($this->getQuote()->getStore()->getId());
		$request->setWebsiteId($this->getQuote()->getStore()->getWebsiteId());
		$request->setFreeShipping($this->getFreeShipping());
		/**
		 * Currencies need to convert in free shipping
		 */
		$request->setBaseCurrency($this->getQuote()->getStore()->getBaseCurrency());
		$request->setPackageCurrency($this->getQuote()->getStore()->getCurrentCurrency());
		$request->setLimitCarrier($this->getLimitCarrier());
                $request->setBaseSubtotalInclTax($this->getBaseSubtotalInclTax() + $this->getBaseExtraTaxAmount()); // Nagarro added: from Magento 1.9 core to set base total with tax
		/**
		 * Schracklive: Add some customer info to request
		 */
		$customer = $this->getQuote()->getCustomer();
		/* @var $customer Schracklive_SchrackCustomer_Model_Customer */
		$request->setSchrackCustomerGroupId($customer->getGroupId());
		$request->setSchrackWarehouseId($customer->getSchrackPickup());

		$result = Mage::getModel('shipping/shipping')->collectRates($request)->getResult();

		$found = false;
		if ($result) {
			$shippingRates = $result->getAllRates();

			foreach ($shippingRates as $shippingRate) {
				$rate = Mage::getModel('sales/quote_address_rate')
								->importShippingRate($shippingRate);
				if (!$item) {
				$this->addShippingRate($rate);
				}

				if ($this->getShippingMethod() == $rate->getCode()) {
					if ($item) {
						$item->setBaseShippingAmount($rate->getPrice());
					} else {
						/**
						 * possible bug: this should be setBaseShippingAmount(),
						 * see Mage_Sales_Model_Quote_Address_Total_Shipping::collect()
						 * where this value is set again from the current specified rate price
						 * (looks like a workaround for this bug)
						 */
					$this->setShippingAmount($rate->getPrice());
					}

					$found = true;
				}
			}
		}
		return $found;
		}

	/**
	 * Validate address attribute values
	 *
	 * @return bool
	 */
	public function validate() {
		return Mage::helper('schrackcustomer/address')->validate($this);
	}

	/**
	 * Tells if the address is one of a Schrack account.
	 *
	 * return bool
	 */
	public function belongsToAccount() {
		// quote addresses always belong to the ordering customer, so check the customer's address
		$customerAddress = Mage::getModel('customer/address')->load($this->getCustomerAddressId());
		if ($customerAddress->getId()) {
			return Mage::helper('schrackcustomer/address')->belongsToAccount($customerAddress);
		} else {
			return false;
		}
	}

	public function setName1($name) {
		$this->setLastname($name);
	}

	public function setName2($name) {
		$this->setMiddlename($name);
	}

	public function setName3($name) {
		$this->setFirstname($name);
	}

	public function getName1() {
		return $this->getLastname();
	}

	public function getName2() {
		return $this->getMiddlename();
	}

	public function getName3() {
		return $this->getFirstname();
	}

}


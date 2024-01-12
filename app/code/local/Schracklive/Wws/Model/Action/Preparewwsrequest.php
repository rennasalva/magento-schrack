<?php

class Schracklive_Wws_Model_Action_Preparewwsrequest extends Schracklive_Wws_Model_Action_Wwsrequest {

	protected $_requestName = 'insertupdateorder';
	protected $_itemsData = array();
	protected $_sendAdress = false;
	protected $_newRegistrationProcessCustomertype = false;

	public function __construct(array $arguments) {
		parent::__construct($arguments);

		if (is_object($this->_quote)) {
			$this->_newRegistrationProcessCustomertype = $this->_quote->getSchrackCustomertype();
		}

		$checkedArguments = $this->_checkArguments($arguments, array(
			'sendAddress' => array('bool', false),
				)
		);

		$this->_sendAdress = $checkedArguments['sendAddress'];
	}

	protected function _isEmployeeForCustomer($loggedInCustomer) {
		return (is_object($loggedInCustomer) && $loggedInCustomer->isEmployee());
	}

	protected function _buildArguments($memo=array()) {
		$wwsHelper = Mage::helper('wws');

		// GUEST ==> non-registering-user
		// NOTE: in some cases (country-dependant), we need a guest that should be handled like a new register prospect:
		if ($this->_newRegistrationProcessCustomertype == 'guest' && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 0) {
			// Get 2-letter uppercase country-code, and assign to WWS variable (special-case customer-number for non-logged-in customer):
			$shopCountryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
			$customerId = 'DIV-CUST=' . $shopCountryCode;
		} elseif ( in_array($this->_newRegistrationProcessCustomertype, array('oldFullProspect', 'oldLightProspect', 'newProspect'))
			      || ($this->_newRegistrationProcessCustomertype == 'guest' && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 1) ) {
			$shopCountryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
			$customerId = 'NEW-CUST=' . $shopCountryCode;
		} else if ( $this->_customer ) {
			$customerId = $wwsHelper->getWwsCustomerId($this->_customer);
		} else {
			throw new Exception("No customer defined!");
		}

		if ( substr($customerId,0,4) === 'TYP=' ) {
			throw new Exception("Wrong customer ID $customerId got for customer {$this->_customer->getEmail()} and request $this->_requestName.");
		}
		$this->_requestArguments = array(
			'wwsCustomerId' => $customerId,
			'wwsContactNumber' => $this->_customer->getSchrackWwsContactNumber(),
			'wwsOrderNumber' => $this->_quote->getSchrackWwsOrderNumber() ? $this->_quote->getSchrackWwsOrderNumber() : '',
		);
		$this->_buildOrderArguments();
		$this->_buildAddressArguments($this->_sendAdress);
		$this->_buildItemArguments();

        // getting the respective id for container or inpost
        $containerID = $this->_quote->getSchrackWwsContainerId();
        $inpostID = $this->_quote->getSchrackWwsInpostId();

        if ( $containerID ) {
            $memo[] = 'VCO=' . $containerID;

        } elseif ($inpostID) {
            $memo[] = 'INP=' . $inpostID;

        } else {
            $memo[] = 'EMTPY';
        }

		parent::_buildArguments($memo);
	}

	protected function _getShippingRate(Mage_Sales_Model_Quote $quote) {
		$address = $quote->getShippingAddress();
		$method = $address->getShippingMethod();

		if (!$method) {
			throw Mage::exception('Schracklive_Wws', 'Missing shipping method '.$method);
		}
		$rate = $address->getShippingRateByCode($method);
		if (!$rate) {
			throw Mage::exception('Schracklive_Wws', 'Invalid shipping rate for shipping method '.$method);
		}
		return $rate;
	}

	protected function _buildAddressArguments($sendAddress) {
		$quoteAddress = $this->_quote->getShippingAddress();
		if (!$sendAddress) {
			$customerAddress = Mage::getModel('customer/address')->load($quoteAddress->getCustomerAddressId());
			if (!$customerAddress->getId() || $customerAddress->getSchrackWwsAddressNumber() < 1) {
				$sendAddress = true;
			}
		}
		$this->_requestArguments['address'] = array();
		if ($sendAddress) {
			// In case of unknown company name:
			if ($quoteAddress->getName1() == '' || $quoteAddress->getName1() == null) {
				// First try finding company name:
				$addressName1 = $this->_quote->getBillingAddress()->getName();
				// Next possibility for company name:
				if ($addressName1 == '' || $addressName1 == null) {
					$addressName1 = $this->_quote->getBillingAddress()->getLastname();
				}
			} else {
				$addressName1 = $quoteAddress->getName1();
			}

			$this->_requestArguments['address']['name1'] = $addressName1;
			$this->_requestArguments['address']['name2'] = $quoteAddress->getName2();
			$this->_requestArguments['address']['name3'] = $quoteAddress->getName3();
			$this->_requestArguments['address']['street'] = $quoteAddress->getStreet1();
			$this->_requestArguments['address']['postcode'] = $quoteAddress->getPostcode();
			$this->_requestArguments['address']['city'] = $quoteAddress->getCity();
			$this->_requestArguments['address']['country_id'] = $quoteAddress->getCountryId();
		} else {
			$this->_requestArguments['wwsAddressNumber'] = (int)$customerAddress->getSchrackWwsAddressNumber();
		}
	}

	protected function _buildOrderArguments() {
		$shippingRate = $this->_getShippingRate($this->_quote);
		/* @var $shippingRate Mage_Shipping_Model_Rate_Abstract */
		$reference = $this->_quote->getSchrackCustomOrderNumber();
		$projectInfo = $this->_quote->getSchrackCustomProjectInfo();
		if ( $projectInfo === null ) {
		    $projectInfo = '';
        }

        $map2wws = Mage::helper('wws/map2wws');
		$this->_requestArguments['order'] = array(
			'reference' => (string)$reference,
			'projectInfo' => (string)$projectInfo,
			'warehouseId' => $map2wws->getWarehouseId($shippingRate),
			'pickupMethod' => $map2wws->getShippingMethod($shippingRate),
			'paymentMethod' => $map2wws->getPaymentMethod($this->_quote->getPayment()->getMethodInstance()),
		);
		if ($this->_customer->isSystemContact()) {
			$this->_requestArguments['order']['user'] = $this->_loggedInCustomer->getSchrackUserPrincipalName();
		} else {
			$this->_requestArguments['order']['user'] = $this->_customer->getEmail();
		}
	}

	protected function _buildItemArguments() {
		$this->_requestArguments['items'] = array();
		foreach ($this->_quote->getAllItems() as $item) {
		    if ( $this->checkNotDoubleItem($item->getSku()) ) {
		        $offerNum = $item->getSchrackOfferNumber();
		        $offerPrice = $item->getSchrackOfferPricePerUnit();
		        if ( intval($offerNum) > 0 && floatval($offerPrice) > 0 ) {
		            $priceUnit = $item->getSchrackOfferPriceUnit();
		            if ( intval($priceUnit) == 0 ) {
                        $priceUnit = $item->getProduct()->getSchrackPriceunit();
                    }
		            $price = (float) $offerPrice * $priceUnit;
                } else {
                    $price = (float) $item->getPrice() * $item->getProduct()->getSchrackPriceunit(); /** @see Schracklive_SchrackSales_Model_Quote_Item_Observer - basic price + surcharge */
                }
                $this->_requestArguments['items'][] = array(
                    'sku' => $item->getSku(),
                    'drumNumber' => $item->getSchrackDrumNumber(),
                    'qty' => $item->getQty(),
                    'price' => $price,
                    'itemDescription' => $item->getSchrackItemDescription(),
                    'offerNum' => $offerNum
                );
            } else {
		        $this->_quote->removeItem($item->getId());
		        $this->_quote->collectTotals()->save();
            }
		}
	}

	private function checkNotDoubleItem ( $sku ) {
	    foreach ( $this->_requestArguments['items'] as $item ) {
	        if ( $item['sku'] == $sku ) {
	            // check if article is cable
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT schrack_is_cable FROM catalog_product_entity WHERE sku = ?";
                $isCable = $readConnection->fetchOne($sql,$sku);
                if ( ! $isCable ) {
                    $customerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
                    Mage::log("Double cart item $sku excluded in insert_update_order. Customer: $customerId",Zend_Log::WARN,'double_cart_position.log');
                    return false;
                }
            }
        }
        return true;
    }

	protected function _buildResponse() {
		$this->_checkOrderResponse();
		$this->_checkItemsResponse();


		$shippingAndHandlingCosts = 0.0;
		$shippingAndHandlingCostTaxes = 0.0;
        $updateMagentoPrices = false;

		foreach ($this->_request->getItems() as $idx => $wwsItem) {
            // TODO: HINT: SELECT * FROM magento_at.sales_flat_quote_item WHERE schrack_wws_place_memo LIKE '%Abweichenden Konditionspreis ermittelt%'
            // TODO: Maybe fix neccessary for update some price-field in sales_flat_quote_item and/or sales_flat_quote
			if (isset($wwsItem['memo']) && stristr($wwsItem['memo'], 'Abweichenden Konditionspreis ermittelt')) {
                $updateMagentoPrices = true;
                // Write log for evaluation:
                Mage::log($wwsItem, null, 'order-price-diff.log');
			}

			if ($this->_itemsData[$idx][self::CODE_CHARGES]) {
                /* TODO: ERDINC (fuer spaeter - wenn Komplettumbau noetig): Erweiterung fuer SHIPPNG AND HANDLING COSTS EINRICHTEN */
                
				$shippingAndHandlingCosts += (float)$wwsItem['amountNet'];
				$shippingAndHandlingCostTaxes += (float)$wwsItem['amountVat'];
				continue;
			}

			$item = $this->_items[$idx];
			$item->setSchrackSurcharge($wwsItem['surcharge']);
			$item->setSchrackRowTotalExclSurcharge($wwsItem['amountNetExclSurcharge']);
			$item->setSchrackRowTotalSurcharge($wwsItem['amountSurchargeNet']);
			$item->setRowTotal($wwsItem['amountNet']); // incl. surcharge (not AmountTot because row totals are without taxes)
			$item->setTaxPercent(Mage::getStoreConfig('schrack/sales/vat'));
			$item->setTaxAmount($wwsItem['amountVat']);
			$item->setSchrackBackorderQty($wwsItem['backorderQty']);
			$item->setSchrackWwsPlaceMemo($wwsItem['memo']);

			if ($wwsItem['memo'] && stristr($wwsItem['memo'], 'CUTTINGFEE')) {
                $arrMemoFields = explode(';', $wwsItem['memo']);

                if ($arrMemoFields && is_array($arrMemoFields)) {
                    foreach ($arrMemoFields as $index => $keyValuePairMemoField) {
                        if (stristr($keyValuePairMemoField, 'CUTTINGFEE')) {
                            $keyValueCuttingFee = str_replace('CUTTINGFEE=', '', $keyValuePairMemoField);
                            $item->setSchrackWwsCuttingfee($keyValueCuttingFee);
                        }
                    }
                }
			}
		}

		$order = $this->_request->getOrder();
		$subtotal = (float)$order['amountNet'] - $shippingAndHandlingCosts; // incl. surcharge (excl. charges)

		$this->_quote->setSchrackWwsOrderNumber($this->_request->getWwsOrderNumber());
		if ( $this->_quote->getSchrackWwsOrderNumberCreatedAt() == null ) {
			$this->_quote->setSchrackWwsOrderNumberCreatedAt(Mage::getSingleton('core/date')->gmtDate());
		}
		$this->_quote->setSchrackWwsCustomerId($this->_request->getWwsCustomerId());
		$this->_quote->setSubtotal($subtotal);
		$this->_quote->setBaseSubtotal($subtotal);
		$this->_quote->setSubtotalWithDiscount($subtotal);
		$this->_quote->setBaseSubtotalWithDiscount($subtotal);
		$this->_quote->setSchrackTaxTotal($order['amountVat']);
		$this->_quote->setGrandTotal($order['amountGross']); // incl. surcharge and charges
		$this->_quote->setBaseGrandTotal($order['amountGross']);
		$this->_quote->setSchrackPaymentTerms($order['paymentTerms']);
		$this->_quote->setSchrackShipmentMode($order['shipmentMode']);
		$this->_quote->setSchrackWwsPlaceMemo($this->_request->getMemo());

        if ($updateMagentoPrices == true) {
            Mage::log($order, null, 'order-price-diff.log');
            Mage::log('SubTotal = ' . $subtotal, null, 'order-price-diff.log');
            Mage::log('shippingAndHandlingCosts = ' . $shippingAndHandlingCosts, null, 'order-price-diff.log');
            Mage::log('------------------------------------------------------------------------------------------', null, 'order-price-diff.log');
            Mage::log('------------------------------------------------------------------------------------------', null, 'order-price-diff.log');
        }

		$address = $this->_quote->getShippingAddress();
		$address->setSubtotal($subtotal);
		$address->setBaseSubtotal($subtotal);
        $address->setBaseTaxAmount($order['amountVat']);
		$address->setTaxAmount($order['amountVat']);
		$address->setShippingAmount($shippingAndHandlingCosts);
		$address->setShippingTaxAmount($shippingAndHandlingCostTaxes);
		$address->setGrandTotal($order['amountGross']); // incl. surcharge (incl . charges)
		$address->setBaseGrandTotal($order['amountGross']); // incl. surcharge (incl . charges)
		// required structure - doesn't need to reflect real product/customer tax classes
		// TODO: figure out why this data isn't preseved in the resulting order
		$address->setAppliedtaxes(array(
			'schrack_vat' => array(
				'rates' => array(
					array(
						'code' => 'schrack_vat',
						'title' => Mage::helper('schrack')->__('VAT'),
						'percent' => Mage::getStoreConfig('schrack/sales/vat'),
						'position' => 0,
						'priority' => 0,
					)
				),
				'percent' => Mage::getStoreConfig('schrack/sales/vat'),
				'id' => 'Schrack VAT '.Mage::getStoreConfig('schrack/sales/vat').'%',
				'process' => 0,
				'amount' => $order['amountVat'],
				'base_amount' => $order['amountVat'],
			)
		));

        $shipping_rates = $address->getAllShippingRates();
        foreach($shipping_rates as $shipping_rate) {
            /** @var $shipping_rate Mage_Sales_Model_Quote_Address_Rate */
            // Mage::Log(var_export($shipping_rate,true));
            $shipping_rate->setPrice($shippingAndHandlingCosts);
            $shipping_rate->setCost($shippingAndHandlingCostTaxes);
            // alternativ $shipping_rate->setData( "cost", $shippingAndHandlingCostTaxes);
            $shipping_rate->save();
        }

		$this->_response = $this->_messages;
	}

	protected function _checkOrderResponse() {
		if ($this->_quote->getSchrackWwsOrderNumber() == '') {
			if (!$this->_request->isNewOrder() || $this->_request->getWwsOrderNumber() == '') {
				throw $this->_requestException('No WWS order has been created.');
			}
		} else {
			if ($this->_quote->getSchrackWwsOrderNumber() != $this->_request->getWwsOrderNumber()) {
				//throw $this->_requestException("WWS order number mismatch: got {$this->_request->getWwsOrderNumber()}, expected {$this->_quote->getSchrackWwsOrderNumber()}");
			}
		}
		// Override check, if new registration is in process::
		if ($this->_newRegistrationProcessCustomertype == false) {
			if ($this->_customer->getSchrackWwsCustomerId() != $this->_request->getWwsCustomerId()) {
				throw $this->_requestException("WWS customer number mismatch: got {$this->_request->getWwsCustomerId()}, expected {$this->_customer->getSchrackWwsCustomerId()}");
			}
		}

		parent::_checkOrderResponse();
	}

	protected function _checkItemsResponse() {
		foreach ($this->_request->getItems() as $idx => $wwsPos) {
			$this->_itemsData[$idx] = array();

			$codes = $this->_parseMemoIntoMessages($wwsPos['memo']);
			if (isset($codes[self::CODE_CHARGES])) {
				$this->_itemsData[$idx][self::CODE_CHARGES] = true;
				continue;
			}
			$this->_itemsData[$idx][self::CODE_CHARGES] = false;

			if (!isset($this->_items[$idx])) {
				throw $this->_requestException('WWS sent an unexpected item: '.$wwsPos['sku']);
			}

			$item = $this->_items[$idx];
			if ($item->getSku() != $wwsPos['sku']) {
				throw $this->_requestException("ItemID/SKU mismatch: got {$wwsPos['sku']}, expected {$item->getSku()}");
			}
			if ((string)$item->getQty() != $wwsPos['qty']) {
				throw $this->_requestException("Quantity mismatch: got {$wwsPos['qty']}, expected {$item->getQty()}");
			}
            /* DLA, 20140428: removed because recently corrected WWS response will cause (wrong) exception
			if ($wwsPos['qty'] < $wwsPos['backorderQty']) {
				throw $this->_requestException("Backorder error: backorder qty {$wwsPos['backorderQty']} higher than order qty {$wwsPos['getQty']}");
			}
             */
			// foreign customers may have no VAT at all - @todo check if customer is foreign (how?)
			if ((float)$wwsPos['amountVat'] != 0.0) {
				if ($this->_isOutsideRoundingErrorRange($wwsPos['amountVat'], $wwsPos['amountNet'] * Mage::getStoreConfig('schrack/sales/vat') / 100)) {
					// ATTENTION : Shop is not allowed to calculate something from WWS. WWS has the data majority !!!!!
					// throw $this->_requestException("VAT mismatch: got {$wwsPos['amountVat']}, expected ".($wwsPos['amountNet'] * Mage::getStoreConfig('schrack/sales/vat') / 100));
                    Mage::log('WWS-Order-ID' . $this->_quote->getSchrackWwsOrderNumber() . '  -- SKU: ' . $wwsPos['sku'] . 'VAT mismatch -> received from WWS: ' . $wwsPos['amountVat'] . ' expected (shop-calculation): ' . ($wwsPos['amountNet'] * Mage::getStoreConfig('schrack/sales/vat')), null, 'vat_mismatch.log');
				}
			}

			/*
			  try {
			  $priceUnit = $item->getProduct()->getSchrackPriceunit();
			  // assert('$wwsPos->AmountWithoutSurcharge == round(($wwsPos->Price / $priceUnit) * $wwsPos->Qty, 2)');
			  //    assert('$wwsPos->AmountWithoutSurcharge == round((($wwsPos->Price / $priceUnit) - $wwsPos->Surcharge) * $wwsPos->Qty, 2)');
			  //     assert('$wwsPos->AmountSurcharge == round(($wwsPos->Surcharge / $priceUnit) * $wwsPos->Qty, 2)');
			  assert('$wwsPos->AmountNet == round($wwsPos->AmountWithoutSurcharge + $wwsPos->AmountSurcharge, 2)');
			  assert('$wwsPos->AmountTot == round($wwsPos->AmountNet + $wwsPos->AmountVat, 2)');
			  } catch (Exception $e) {
			  throw Mage::exception('Schracklive_Wws', $e->getMessage());
			  }
			 * 
			 */
		}
	}

}

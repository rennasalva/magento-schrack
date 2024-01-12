<?php


class Schracklive_Wws_Model_Request_InsertUpdateOrder extends Schracklive_Wws_Model_Request_Abstract {
    const FLAG_CREATE_OFFER_FOR_EMPLOYEE = 3;

	protected $_soapMethod = 'insert_update_order';
	protected $_sendShippingAddress = false;
	/* arguments */
	protected $_wwsOrderNumber;
	protected $_wwsCustomerId;
	protected $_wwsContactNumber;
	protected $_wwsAddressNumber;
	protected $_wwsAddressType;
    protected $_wwsAddressContactPhone;
	protected $_order = array();
	protected $_address = array();
	protected $_items = array();
	protected $_memo;
	/* return values */
	protected $_responseOrderHasBeenCreated = false;
	protected $_responseWwsCustomerId = '';
	protected $_responseOrder = array();
	protected $_responseItems = array();

	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'wwsOrderNumber' => array('numeric', ''),
			'wwsCustomerId',
			'wwsContactNumber' => array('numeric', 0),
			'wwsAddressNumber' => array('int', -1),
			'address' => array('array', array()),
			'order' => 'array',
			'items' => 'array',
			'memo' => array('array', array()),
				));

		$checkedOrder = $this->_checkArguments($checkedArguments['order'], array(
			'reference' => array('string', ''),
			'projectInfo' => array('string', ''),
			'warehouseId',
			'pickupMethod',
			'paymentMethod',
			'user' => array('string', ''),
				));

        if ( count($checkedArguments['items']) < 1 ) {
            throw new InvalidArgumentException('No items in order.');
        }
        
		foreach ($checkedArguments['items'] as $item) {
			$this->_checkArguments($item, array(
				'sku' => 'string',
				'drumNumber' => array('string', ''),
				'qty' => 'float',
				'price' => 'float',
				'itemDescription' => array('string', ''),
			));
		}

		$checkedAddress = $this->_checkArguments($checkedArguments['address'], array(
			'name1' => array('string', ''),
			'name2' => array('string', ''),
			'name3' => array('string', ''),
			'street' => array('string', ''),
			'postcode' => array('string', ''),
			'city' => array('string', ''),
			'country_id' => array('string', ''),
				));

		$this->_wwsOrderNumber = (string)$checkedArguments['wwsOrderNumber'];
		$this->_wwsCustomerId = (string)$checkedArguments['wwsCustomerId'];
		$this->_wwsContactNumber = (string)$checkedArguments['wwsContactNumber'];
		$this->_wwsAddressNumber = $checkedArguments['wwsAddressNumber'];
		$this->_order = $checkedOrder;
		$this->_address = $checkedAddress;
		$this->_items = $checkedArguments['items'];
		$this->_memo = join(';', $checkedArguments['memo']);

		if ($this->_wwsAddressNumber < 1) {
			$this->_sendShippingAddress = true;
		}

		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		// Make sure, that customer name is always used, if sendShippingAddress == true:
		if($this->_sendShippingAddress) {

			// Special route for prospects
			$sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
			if ($sessionCustomerId) {
				$customer = Mage::getModel('customer/customer')->load($sessionCustomerId);
				if (is_object($customer)) {
					$customerType = $customer->getSchrackCustomerType();
					if ($customerType == 'full-prospect') {
						$this->_address['name1'] = $customer->getAccount()->getName1();
					}
				}
			}

			if ($this->_address['name1'] == '') {
				$custName1 = '.';
			} else {
				$custName1 = $this->_address['name1'];
			}
		} else {
			$custName1 = '';
		}

		if ($this->_order['user'] == '' || $this->_order['user'] == null) {
			$checkout = Mage::getSingleton('checkout/session')->getQuote();
			$billingAddress = $checkout->getBillingAddress();
			$billingEmail = $billingAddress->getData('email');
			if (!$billingEmail) {
				$billingEmail = 'Billing Email not defined';
			}

			$orderedByUser = $billingEmail;
		} else {
			$orderedByUser = $this->_order['user'];
		}

// Get customer type first, for check on new self registration:
// Mage::log(Mage::getSingleton('checkout/session')->getQuote(), null, '/prospects/prospects.log');
// Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getStreet();
/*
$sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
$customer = Mage::getModel('customer/customer')->load($sessionCustomerId);
Mage::log(Mage::getSingleton('checkout/session')->getQuote(), null, 'import_prospects.log');
if (is_object($customer)) {
	$customerType = $customer->getSchrackCustomerType();
	if ($customerType == 'full-prospect' || $customerType == 'light-prospect') {
		$customerDefaultBillingAddress = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
		//$this->_sendShippingAddress = true;

		$customerAddressData['name1']      = $customer->getAccount()->getName1();
		$customerAddressData['name2']      = $customer->getAccount()->getName2();
		$customerAddressData['name3']      = $customer->getAccount()->getName3();
		$customerAddressData['street']     = $customerDefaultBillingAddress->getStreet()[0];
		$customerAddressData['postcode']   = $customerDefaultBillingAddress->getPostcode();
		$customerAddressData['city']       = $customerDefaultBillingAddress->getCity();
		$customerAddressData['country_id'] = $customerDefaultBillingAddress->getCountryId();
		//$this->_address = $customerAddressData;
	} else {
		$quoteAddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
	}
}
Mage::log('payment Method: ' . $this->_order['paymentMethod'], null, '/prospects/prospects.log');

*/
		$schrackWwsOrderMemo = Mage::getSingleton('checkout/session')->getQuote()->getSchrackWwsOrderMemo();
		$sessionQuote = Mage::getSingleton('checkout/session')->getQuote();

		if ($schrackWwsOrderMemo) {
			if ($this->_memo) {
				// Concatenating:
				$this->_memo = $this->_memo . ';' . $schrackWwsOrderMemo;
			} else {
				// Set new and only information:
				$this->_memo = $schrackWwsOrderMemo;
			}
		}

		$sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$customer = Mage::getModel('customer/customer')->load($sessionCustomerId);
		if (is_object($customer)) {
			$customerType = $customer->getSchrackCustomerType();
			if ($customerType == 'converted') {
				$wwsCustomerIDFromQuote    = $sessionQuote->getSchrackWwsCustomerId();
				$wwsCustomerIDFromCustomer = $customer->getSchrackWwsCustomerId();

				if (intval($wwsCustomerIDFromCustomer) > 0 && $wwsCustomerIDFromQuote != $wwsCustomerIDFromCustomer) {
					if (intval($sessionQuote->getSchrackWwsOrderNumber()) > 0) {
						// Save old order number:
						Mage::log($sessionQuote->getSchrackWwsOrderNumber(), null, '/prospects/saved_exotic_orders.log');
						// Remove order from insert_update_order to get a new wws_order_number:
						$this->_wwsOrderNumber = '';

						// ...and remove old order from quote:
						$sessionQuote->setSchrackWwsOrderNumber('');

						// ...and change old group to new group:
						$sessionQuote->setCustomerGroupId($customer->getGroupId());

						// ...and change current customer_type to qoute:
						$sessionQuote->setSchrackCustomertype('normal');
					}

                    $sessionQuote->setSchrackWwsCustomerId($wwsCustomerIDFromCustomer);
                    $sessionQuote->save();

                    $this->_wwsCustomerId = $wwsCustomerIDFromCustomer;
				}
			}

            $wwsCustomerIDFromQuote    = $sessionQuote->getSchrackWwsCustomerId();
            $wwsCustomerIDFromCustomer = $customer->getSchrackWwsCustomerId();

            if (intval($wwsCustomerIDFromCustomer) > 0 && $wwsCustomerIDFromQuote != $wwsCustomerIDFromCustomer) {
                $sessionQuote->setSchrackWwsCustomerId($wwsCustomerIDFromCustomer);

                if (intval($sessionQuote->getSchrackWwsOrderNumber()) > 0) {
                    // ...and change old group to new group:
                    $sessionQuote->setCustomerGroupId($customer->getGroupId());

                    // ...and change current customer_type to qoute:
                    $sessionQuote->setSchrackCustomertype('normal');
                }

                $sessionQuote->save();

                $this->_wwsCustomerId = $wwsCustomerIDFromCustomer;
            } else {
                if ($wwsCustomerIDFromCustomer) {
                    $this->_wwsCustomerId = $wwsCustomerIDFromCustomer;
                }
            }
		}

        $resource        = Mage::getSingleton('core/resource');
        $readConnection  = $resource->getConnection('core_read');

        if ($this->_wwsCustomerId && $this->_wwsContactNumber) {
            if (intval($this->_wwsAddressNumber) > 0) {
                // Get entity_id from system-contact:
                $customer_query  = "SELECT entity_id FROM customer_entity";
                $customer_query .= " WHERE schrack_wws_customer_id LIKE '" . $this->_wwsCustomerId . "'";
                $customer_query .= " AND schrack_wws_contact_number = -1";

                $customerEntity_Id = $readConnection->fetchOne($customer_query);

                // Use entity_id from system-contact to get correct adress-recordset:
                $addressTypeQuery = "SELECT schrack_type FROM customer_address_entity WHERE parent_id = " . $customerEntity_Id;
                $addressTypeQuery .= " AND schrack_wws_address_number = " . $this->_wwsAddressNumber;

                $addressType = $readConnection->fetchOne($addressTypeQuery);

                $this->_wwsAddressType = (string) $addressType;
            } else {
                $this->_wwsAddressType = (string) Mage::getSingleton('checkout/session')->getQuote()->getSchrackAddressType();
            }

            if (!$this->_wwsAddressType) {
                $this->_wwsAddressType = "2";
            }
        } else {
            //Prospect/guest:
            $this->_wwsAddressType = "2";
        }

        // inpost -> getting inputed data, phone number
        if (Mage::getSingleton('customer/session')->getInpostPhoneNumber() != "") {
            $inpostPhoneNumber = Mage::getSingleton('customer/session')->getInpostPhoneNumber();
        } else {
            $inpostPhoneNumber = "";
        }

        $inpostContactPhoneInput = ($inpostPhoneNumber) ? $inpostPhoneNumber : "123456";

        $this->_wwsAddressContactPhone = '';
        $addressPhone = Mage::getModel('customer/customer')->load($sessionCustomerId)['schrack_mobile_phone'];
        #"SELECT schrack_address_phone FROM sales_flat_quote WHERE entity_id = " . Mage::getSingleton('checkout/session')->getQuote()->getId();
        #$addressPhone = $readConnection->fetchOne($addressPhoneQuery);
//        if ($addressPhone) {
            if(inpostContactPhoneInput) {
                $this->_wwsAddressContactPhone = $inpostContactPhoneInput;
            } else {
                $this->_wwsAddressContactPhone = $addressPhone;#str_replace(array(' ', '/', '-'), '', $addressPhone);
            }
//        }

        $customerStreet       = $this->_sendShippingAddress ? $this->_address['street'] : '';
        $customerStreetResult = $customerStreet;

        $customerCityExists = $this->_sendShippingAddress ? $this->_address['city'] : '';

        if ($customerCityExists) {
            if (is_array($customerStreet)) {
                if (isset($customerStreet[0]) && !empty($customerStreet[0])) {
                    $customerStreetResult = $customerStreet[0];
                } else {
                    $customerQuote = Mage::getSingleton('checkout/session')->getQuote();
                    $customerStreetResult = $customerQuote->getCustomerNote();
                }
            } else {
                // Must be string:
                if ($customerStreet == 'Array') {
                    // Fallback:
                    $customerQuote = Mage::getSingleton('checkout/session')->getQuote();
                    $customerStreetResult = $customerQuote->getCustomerNote();
                }
            }
        }

        $flagOrder = -1;
        if (Mage::registry('order_type') && Mage::registry('order_type') == 'cart_offer') {
            $realPaymentMethod = 0;
            if (    Mage::registry('real_user_type_offer_cart')
                 && Mage::registry('real_user_type_offer_cart') == 'employee' ) {
                $flagOrder = self::FLAG_CREATE_OFFER_FOR_EMPLOYEE;
            }
        } else {
            $realPaymentMethod = $this->_order['paymentMethod'];
        }

		$this->_soapArguments = array(
			'tt_order' => array(
				array(
					'xrow' => 1,
					'OrderNumber' => $this->_wwsOrderNumber,
					'Reference' => $this->_order['reference'],
					'WarehouseID' => $this->_order['warehouseId'],
					'PickupMethod' => $this->_order['pickupMethod'],
					'PaymentMethod' => $realPaymentMethod,
					'OrderedByUser' => $orderedByUser,
					'FlagOrder' => $flagOrder,
					'CustomerNumber' => $this->_wwsCustomerId,
					'CustContactNumber' => $this->_wwsContactNumber,
					'FlagCreateCust' => 0,
					'AddressNumber' => $this->_sendShippingAddress ? 0 : $this->_wwsAddressNumber,
					'AddressType' => $this->_wwsAddressType,
					'FlagShipAddress' => $this->_sendShippingAddress ? 1 : 0,
					'CustTitle' => '',    // Attention: this field is NOT used at this time (either in shop nor in WWS)
					'CustName1' => $custName1,
					'CustName2' => $this->_sendShippingAddress ? $this->_address['name2'] : '',
					'CustName3' => $this->_sendShippingAddress ? $this->_address['name3'] : '',
					'CustStr' => $customerStreetResult,
					'CustZip' => $this->_sendShippingAddress ? $this->_address['postcode'] : '',
					'CustCity' => $this->_sendShippingAddress ? $this->_address['city'] : '',
					'CustCtry' => $this->_sendShippingAddress ? $this->_address['country_id'] : '',
					'CustPhone' => $this->_wwsAddressContactPhone,
					'CustFax' => '',     // Attention: this field is NOT used at this time (either in shop nor in WWS)
					'CustEmail' => '',   // Attention: this field is NOT used at this time (either in shop nor in WWS)
					'CustomerDeliveryInfo' => '',
					'BackorderType' => '',
					'CustomerProjectInfo' => $this->_order['projectInfo'],
					'DeliveryDate' => null,
					'CustReference1' => '',
					'CustReference2' => '',
					'CustReference3' => '',
					'CustReference4' => '',
					'CustReference5' => '',
					'Memo' => $this->_memo,
				),
			),
		);
        $t = $this->_soapArguments['tt_order'][0]['CustPhone'];
        Mage::log("Phone : " . $t, null,"zeppelin.log");

		foreach ($this->_items as $itemNumber => $item) {
			$memo = array();
			if (isset($item['drumNumber']) && $item['drumNumber']) {
				$memo[] = 'DRUM='.$item['drumNumber'];
			}
			$this->_soapArguments['tt_pos_rows'][] = array(
				'Position' => $itemNumber + 1,
				'ItemID' => $item['sku'],
				'Qty' => $item['qty'],
				'Price' => $item['price'],
				'Annotation' => $item['itemDescription'],
				'ProducerItemID' => '',
				'PriceUnit' => '',
				'CustomerPosition' => '',
				'OfferNum' => $item['offerNum'],
				'OfferPos' => '',
				'DeliveryWeek' => '',
				'DeliveryYear' => '',
				'ItemDesc' => '',
				'CustReference1' => '',
				'CustReference2' => '',
				'CustReference3' => '',
				'CustReference4' => '',
				'CustReference5' => '',
				'Memo' => implode(';', $memo),
			);
		}
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		if (!$this->_isStatusOfOneRowValid('tt_wwsorder')) {
			return false;
		}

		$this->_checkWwsorderResponse();
		$this->_checkWwsposResponse();

		return true;
	}

	protected function _checkWwsorderResponse() {
		if (count($this->_soapResponse['tt_wwsorder']) > 1) {
			throw $this->_wwsException('More than 1 order present in WWS response.', self::EXCEPTION_WWS_FAILURE);
		}

		// Pflichtfelder im Response:
		$this->_checkReturnedFieldsOfOneRow('tt_wwsorder',
            array(
                'CustomerNumber',
                'OrderNumber',
                'OrderCreated',
                'AmountNet',
                'AmountVat',
                'AmountTot',
                'PaymentTerms',
                'ShipmentMode',
                'Memo',
                'xstatus',
            )
		);
	}

	protected function _checkWwsposResponse() {
		if (count($this->_soapResponse['tt_wwspos']) < count($this->_items)) {
			throw $this->_wwsException('Less items then requested in WWS response.', self::EXCEPTION_WWS_FAILURE);
		}

        // Pflichtfelder im Response:
		$this->_checkReturnedFieldsOfAllRows('tt_wwspos',
            array(
                'Position',
                'Price',
                'Surcharge',
                'AmountWithoutSurcharge',
                'AmountSurcharge',
                'AmountNet',
                'AmountVat',
                'AmountTot',
                'Qty',
                'BackorderQty',
                'Memo',
			)
		);
	}

	protected function _processResponse() {
		$this->_responseOrderHasBeenCreated = (bool)$this->_soapResponse['tt_wwsorder'][0]->OrderCreated;
		$this->_responseWwsCustomerId = $this->_soapResponse['tt_wwsorder'][0]->CustomerNumber;
		$this->_responseOrder = array(
			'wwsNumber' => $this->_soapResponse['tt_wwsorder'][0]->OrderNumber,
			'amountNet' => $this->_soapResponse['tt_wwsorder'][0]->AmountNet,
			'amountVat' => $this->_soapResponse['tt_wwsorder'][0]->AmountVat,
			'amountGross' => $this->_soapResponse['tt_wwsorder'][0]->AmountTot,
			'paymentTerms' => $this->_soapResponse['tt_wwsorder'][0]->PaymentTerms,
			'shipmentMode' => $this->_soapResponse['tt_wwsorder'][0]->ShipmentMode,
			'memo' =>  $this->_soapResponse['tt_wwsorder'][0]->Memo,
		);

		foreach ($this->_soapResponse['tt_wwspos'] as $wwsPos) {
			$this->_responseItems[] = array(
				'sku' => $wwsPos->ItemID,
				'qty' => $wwsPos->Qty,
				'backorderQty' => $wwsPos->BackorderQty,
				'surcharge' => $wwsPos->Surcharge,
				'amountNet' => $wwsPos->AmountNet,
				'amountSurchargeNet' => $wwsPos->AmountSurcharge,
				'amountNetExclSurcharge' => $wwsPos->AmountWithoutSurcharge,
				'amountVat' => $wwsPos->AmountVat,
				'amountGross' => $wwsPos->AmountTot,
				'memo' => $wwsPos->Memo,
			);
		}

		return true;
	}

	public function getWwsOrderNumber() {
		return $this->_responseOrder['wwsNumber'];
	}

	public function isNewOrder() {
		return $this->_responseOrderHasBeenCreated;
	}

	public function getWwsCustomerId() {
		return $this->_responseWwsCustomerId;
	}

	public function getOrder() {
		return $this->_responseOrder;
	}

	public function getItems() {
		return $this->_responseItems;
	}

	public function getMemo() {
		return $this->_responseOrder['memo'];
	}

}

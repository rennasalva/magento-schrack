<?php

/**
 * One page checkout processing model
 */
class Schracklive_SchrackCheckout_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage {
	/**
	 * Error message of "customer already exists"
	 *
	 * @var string
	 */
	private $_customerEmailExistsMessage = '';


        /**
         * Initialize quote state to be valid for one page checkout
         *
         * @return Schracklive_SchrackCheckout_Model_Type_Onepage
         */
        public function initCheckout() {
            $checkout = $this->getCheckout();
            $customerSession = $this->getCustomerSession();
            $geoipHelper = Mage::helper('geoip');
            if ( $customerSession->isLoggedIn() ) {
                if ( $geoipHelper->mayPerformCheckout() ) {
                    if ($customerSession->getCustomer()->isContact() || $customerSession->getCustomer()->isProspect()) {
                        $startStep = 'shipping';
                    } else {
                        $startStep = 'billing';
                    }
                } else {
                    $startStep = 'shipping';
                }
            } else {
                if ($geoipHelper->mayPerformCheckout()) {
                    $startStep = 'login';
                } else {
                    $startStep = 'address';
                }
            }

            if (is_array($checkout->getStepData())) {
                    foreach ($checkout->getStepData() as $step => $data) {
                            if ($step != $startStep) { /* SCHRACKLIVE end */
                                    $checkout->setStepData($step, 'allow', false);
                            }
                    }
            }

            $quoteSave = false;     //added by nagarro from magento 1.9
            $collectTotals = false;   //added by nagarro from magento 1.9

            /**
             * Reset multishipping flag before any manipulations with quote address
             * addAddress method for quote object related on this flag
             */
            if ($this->getQuote()->getIsMultiShipping()) {
                $this->getQuote()->setIsMultiShipping(false);
                $quoteSave = true;
            }   //added by nagarro from magento 1.9

            /**
             *  Reset customer balance
             */
            if ($this->getQuote()->getUseCustomerBalance()) {
                $this->getQuote()->setUseCustomerBalance(false);
                $quoteSave = true;
                $collectTotals = true;
            }   //added by nagarro from magento 1.9
            /**
             *  Reset reward points
             */
            if ($this->getQuote()->getUseRewardPoints()) {
                $this->getQuote()->setUseRewardPoints(false);
                $quoteSave = true;
                $collectTotals = true;
            }   //added by nagarro from magento 1.9

            if ($collectTotals) {
                $this->getQuote()->collectTotals();
            }   //added by nagarro from magento 1.9

            if ($quoteSave) {
                $this->getQuote()->save();
            }   //added by nagarro from magento 1.9

            /*
             * want to laod the correct customer information by assiging to address
             * instead of just loading from sales/quote_address
             */
            $customer = $customerSession->getCustomer();
            /* @var $customer Schracklive_SchrackCustomer_Model_Customer */
            if ($customer) {
                    $this->getQuote()->assignCustomer($customer);
                    /* SCHRACKLIVE start - setup billing address for WWS customers */
                    if ($customerSession->isLoggedIn()
                                    && ($customer->isContact() || $customer->isProspect())
                                    && $customer->getDefaultBilling()) {
                            $customerBillingAddress = $customer->getPrimaryBillingAddress();
                            if ($customerBillingAddress) {
                                    $billingAddress = $this->getQuote()->getBillingAddress();
                                    $billingAddress->importCustomerAddress($customerBillingAddress);
                                    $billingAddress->implodeStreetAddress();
                            }
                    }
                    /* SCHRACKLIVE end */
            }
            return $this;
        }


	/**
	 * Save billing address information to quote
	 * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
	 *
	 * @param   array $data
	 * @param   int $customerAddressId
	 * @return  Mage_Checkout_Model_Type_Onepage
	 */
	public function saveBilling($data, $customerAddressId)
	{
		if (empty($data)) {
			return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
		}

		$newCheckoutProcessParticipants = false;

		//  Get customer status durchlaeufer (guest) / interessent (oldFullProspect, oldLightProspect, newProspect)
		if (array_key_exists('customer_type', $data) &&	in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
			$this->getQuote()->setSchrackCustomertype($data['customer_type']);
			$this->getQuote()->collectTotals()->save();
			$newCheckoutProcessParticipants = true;
            $this->getQuote()->setSchrackAddressTypeNew(0);
            $this->getQuote()->setSchrackAddressType(2);
		}

        if (isset($data['phone_address_contact']) && $data['phone_address_contact'] != '') {
            $formattedAddressPhone = '+' . $data['phone_address_contact'];
            $this->getQuote()->setSchrackAddressPhone($formattedAddressPhone);
            $this->getQuote()->collectTotals()->save();
        }

		// Get customer type => customer_entity.schrack_customer_type: 'full-prospect' => 'Schrack Full Interessent'
		// Prospect is logged in, but has no wws-customer-id
		if($customerSession = $this->getCustomerSession()->getCustomer()->getSchrackCustomerType() == 'full-prospect') {
			$this->getQuote()->setSchrackCustomertype('oldFullProspect');
			$this->getQuote()->collectTotals()->save();
		}

		$address = $this->getQuote()->getBillingAddress();

		if (isset($data['middlename']) && $data['middlename'] != "") {
			$address->setData('middlename', $data['middlename']);
		}

		/* @var $addressForm Mage_Customer_Model_Form */
		$addressForm = Mage::getModel('customer/form');
		$addressForm->setFormCode('customer_address_edit')
		->setEntityType('customer_address')
		->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

		if (!empty($customerAddressId)) {
			$customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
			if ($customerAddress->getId()) {
				if ($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
					return array('error' => 1,
						'message' => Mage::helper('checkout')->__('Customer Address is not valid.')
					);
				}

				$address->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
				$addressForm->setEntity($address);
				$addressErrors  = $addressForm->validateData($address->getData());
				if ($addressErrors !== true) {
					return array('error' => 1, 'message' => $addressErrors);
				}
			}
		} else {
			$addressForm->setEntity($address);
			// emulate request object
			$addressData    = $addressForm->extractData($addressForm->prepareRequest($data));
			$addressErrors  = $addressForm->validateData($addressData);
			if ($addressErrors !== true) {
				return array('error' => 1, 'message' => $addressErrors);
			}
			$addressForm->compactData($addressData);
                        //unset billing address attributes which were not shown in form
                        foreach ($addressForm->getAttributes() as $attribute) {
                            if (!isset($data[$attribute->getAttributeCode()])) {
                                $address->setData($attribute->getAttributeCode(), NULL);
                            }
                        }    //added by nagarro from magento 1.9
                        $address->setCustomerAddressId(null);    //added by nagarro from magento 1.9
			// Additional form data, not fetched by extractData (as it fetches only attributes)
			if ($newCheckoutProcessParticipants == false) {
				$address->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
			}
		}

                // set email for newly created user
                if (!$address->getEmail() && $this->getQuote()->getCustomerEmail()) {
                    $address->setEmail($this->getQuote()->getCustomerEmail());
                }     //added by nagarro from magento 1.9

		// validate billing address
		if (($validateRes = $address->validate()) !== true) {
			return array('error' => 1, 'message' => $validateRes);
		}

		$address->implodeStreetAddress();

		if (true !== ($result = $this->_validateCustomerData($data))) {
			return $result;
		}

		if (!$this->getQuote()->getCustomerId() && self::METHOD_REGISTER == $this->getQuote()->getCheckoutMethod()) {
			if ($this->_customerEmailExists($address->getEmail(), Mage::app()->getWebsite()->getId())) {
				return array('error' => 1, 'message' => $this->_customerEmailExistsMessage);
			}
		}

		if (!$this->getQuote()->isVirtual()) {
			/**
			 * Billing address using otions
			 */
			$usingCase = isset($data['use_for_shipping']) ? (int)$data['use_for_shipping'] : 0;

			switch($usingCase) {
				case 0:
					$shipping = $this->getQuote()->getShippingAddress();
					$shipping->setSameAsBilling(0);
					break;
				case 1:
					$billing = clone $address;
					$billing->unsAddressId()->unsAddressType();
					$shipping = $this->getQuote()->getShippingAddress();
					$shippingMethod = $shipping->getShippingMethod();

                                        // Billing address properties that must be always copied to shipping address
                                        $requiredBillingAttributes = array('customer_address_id');     //added by nagarro from magento 1.9

                                        // don't reset original shipping data, if it was not changed by customer
                                        foreach ($shipping->getData() as $shippingKey => $shippingValue) {
                                            if (!is_null($shippingValue) && !is_null($billing->getData($shippingKey))
                                                && !isset($data[$shippingKey]) && !in_array($shippingKey, $requiredBillingAttributes)
                                            ) {
                                                $billing->unsetData($shippingKey);
                                            }
                                        }    //added by nagarro from magento 1.9
					$shipping->addData($billing->getData())
					->setSameAsBilling(1)
					->setSaveInAddressBook(0)
					->setShippingMethod($shippingMethod)
					->setCollectShippingRates(true);
					$this->getCheckout()->setStepData('shipping', 'complete', true);
					break;
			}
		}

        if (array_key_exists('customer_type', $data) &&	in_array($data['customer_type'], array('newProspect', 'guest'))) {
            if (isset($data['gender']) && $data['gender'] != "") {
                $genderId = $data['gender'];
                $genderArray = array(1 => $this->_helper->__('Male'), 2 => $this->_helper->__('Female'));
                $this->getQuote()->setCustomerPrefix($genderArray[$genderId]);
            }

            if (isset($data['person-firstname']) && $data['person-firstname'] != "") {
                $firstname = $data['person-firstname'];
                $this->getQuote()->setCustomerFirstname($firstname);
            }

            if (isset($data['person-lastname']) && $data['person-lastname'] != "") {
                $lastname = $data['person-lastname'];
                $this->getQuote()->setCustomerLastname($lastname);
            }
        }

		$this->getQuote()->collectTotals();
		$this->getQuote()->save();

        if (!$this->getQuote()->isVirtual() && $this->getCheckout()->getStepData('shipping', 'complete') == true) {
            //Recollect Shipping rates for shipping methods
            $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        }     //added by nagarro from magento 1.9

		$this->getCheckout()
		->setStepData('billing', 'allow', true)
		->setStepData('billing', 'complete', true)
		->setStepData('shipping', 'allow', true);

		return array();
	}


	/**
	 * Save checkout shipping address
	 *
	 * @param   array $data
	 * @param   int $customerAddressId
	 * @return  Schracklive_SchrackCheckout_Model_Type_Onepage
	 */
	public function saveShipping($data, $customerAddressId) {
		if (empty($data)) {
			return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
		}

		$maySaveInAddressBook = true;
        $schrackAdressType = '';

		//  Get customer status durchlaeufer (guest) / interessent (oldFullProspect, oldLightProspect, newProspect)
		if (is_array($data) && array_key_exists('customer_type', $data) &&	in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
			$this->getQuote()->setSchrackCustomertype($data['customer_type']);
			$this->getQuote()->collectTotals()->save();
			$maySaveInAddressBook = false;
            $schrackAdressType = "2";
		} else {
		    // Customer with WWS-ID:
            if ($data['customer_type'] == 'normal') {
                if (isset($data['new_address']) && $data['new_address'] == 'yes') {
                    $schrackAdressType = isset($data['schrack_address_type']) ? intval($data['schrack_address_type']) : 1;
                    $this->getQuote()->setSchrackAddressTypeNew(1);
                    $this->getQuote()->collectTotals()->save();
                } else {
                    $this->getQuote()->setSchrackAddressTypeNew(0);
                    $this->getQuote()->collectTotals()->save();

                    $resource        = Mage::getSingleton('core/resource');
                    $readConnection  = $resource->getConnection('core_read');

                    // Use entity_id from system-contact to get correct adress-recordset:
                    if (isset($data['old_address_id']) && intval($data['old_address_id']) > 0) {
                        $addressTypeQuery = "SELECT schrack_type FROM customer_address_entity WHERE entity_id = " . intval($data['old_address_id']);
                        $schrackAdressType = (string) $readConnection->fetchOne($addressTypeQuery);
                    }
                }

                $this->getQuote()->setSchrackAddressType($schrackAdressType);
                $this->getQuote()->collectTotals()->save();
            }
		}

        if (isset($data['phone_address_contact']) && $data['phone_address_contact'] != '' && $data['new_address'] == 'yes') {
            $formattedAddressPhone = '+' . $data['phone_address_contact'];
            $this->getQuote()->setSchrackAddressPhone($formattedAddressPhone);
            $this->getQuote()->collectTotals()->save();
        }

        if (isset($data['new_address']) && $data['new_address'] == 'no' && isset($data['old_address_id']) && intval($data['old_address_id']) > 0) {
            $existingAddressData  = Mage::getModel('customer/address')->load(intval($data['old_address_id']));
            $existingAddressPhone = $existingAddressData->getTelephone();
            $this->getQuote()->setSchrackAddressPhone($existingAddressPhone);
            $this->getQuote()->collectTotals()->save();
        }

		$address = $this->getQuote()->getShippingAddress();

		if (isset($data['middlename']) && $data['middlename'] != "") {
			$address->setData('middlename', $data['middlename']);
		}

		/* @var $addressForm Mage_Customer_Model_Form */
		$addressForm = Mage::getModel('customer/form');
		$addressForm->setFormCode('customer_address_edit')
				->setEntityType('customer_address')
				->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());

		if (!empty($customerAddressId)) {
			$customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
			if ($customerAddress->getId()) {
				/* SCHRACKLIVE start - use system contact */
				$systemContact = Mage::getModel('customer/customer')->load($this->getQuote()->getCustomerId())->getSystemContact();
				if ($customerAddress->getCustomerId() != $systemContact->getId()) {
					/* SCHRACKLIVE end */
					return array('error' => 1,
						'message' => Mage::helper('checkout')->__('Customer Address is not valid.')
					);
				}

				$address->importCustomerAddress($customerAddress)->setSaveInAddressBook(0);
				$addressForm->setEntity($address);
				$addressErrors = $addressForm->validateData($address->getData());
				if ($addressErrors !== true) {
					return array('error' => 1, 'message' => $addressErrors);
				}
			}
		} else {
			$addressForm->setEntity($address);
			// emulate request object
			$addressData = $addressForm->extractData($addressForm->prepareRequest($data));
			$addressErrors = $addressForm->validateData($addressData);
			if ($addressErrors !== true) {
				return array('error' => 1, 'message' => $addressErrors);
			}
			$addressForm->compactData($addressData);
            // unset shipping address attributes which were not shown in form
            foreach ($addressForm->getAttributes() as $attribute) {
                if (!isset($data[$attribute->getAttributeCode()])) {
                    $address->setData($attribute->getAttributeCode(), NULL);
                }
            }     //added by nagarro from magento 1.9

            $address->setCustomerAddressId(null);     //added by nagarro from magento 1.9
			// Additional form data, not fetched by extractData (as it fetches only attributes)
			if ($maySaveInAddressBook) {
				$address->setSaveInAddressBook(empty($data['save_in_address_book']) ? 0 : 1);
			}
			$address->setSameAsBilling(empty($data['same_as_billing']) ? 0 : 1);
		}

		$address->implodeStreetAddress();
		$address->setCollectShippingRates(true);

		if (($validateRes = $address->validate()) !== true) {
			return array('error' => 1, 'message' => $validateRes);
		}

		if ($this->getQuote()->getSchrackCustomertype() == 'oldLightProspect') {
			// If we deal with light prospect, assign all entered data to (dummy) billing address (billing address => base address):
// Mage::log($error, null, '/prospects/prospects.log');
/*
			$customerSession = $this->getCustomerSession();
			$customer = $this->getQuote()->getCustomer();
			$customerDefaultBillingAddressID = $customer->getSystemContact()->getDefaultBilling();
			$customerDefaultBillingAddress = Mage::getModel('customer/address')->load($customerDefaultBillingAddressID);
			$customerDefaultBillingAddress->setLastname($data['lastname']);
			$customerDefaultBillingAddress->setMiddlename($data['company']);
			$customerDefaultBillingAddress->setFirstname($data['firstname']);
			$customerDefaultBillingAddress->setStreet($data['street']);
			$customerDefaultBillingAddress->setPostcode($data['postcode']);
			$customerDefaultBillingAddress->setCity($data['city']);
			$customerDefaultBillingAddress->setCountryId($data['country_id']);
			$customerDefaultBillingAddress->save();
*/
		}

		// New full prospect = new prospect (no old billing address data available, so create new address data)
		if ($this->getQuote()->getSchrackCustomertype() == 'newProspect') {
			// This should not be saved from shop, because it should be done ny S4Y!! --> using MQ
		}

		$this->getQuote()->collectTotals()->save();

		$this->getCheckout()
				->setStepData('shipping', 'complete', true)
				->setStepData('shipping_method', 'allow', true);

		return array();
	}


	/*
	 * This method is never used for WWS customers.
	 * If it were it must be changed to work with system contact addresses
	 *
	 * @param   array $data
	 * @param   int $customerAddressId
	 * @return  Schracklive_SchrackCheckout_Model_Type_Onepage
	 */
	// public function saveBilling($data, $customerAddressId)

	/**
	 * Specify quote shipping method
	 *
	 * @param   string $shippingMethod
	 * @return  array
	 */
	public function saveShippingMethod($shippingMethod) {
// Mage::log($error, null, '/prospects/prospects.log');
		if (is_array($shippingMethod) && array_key_exists('type', $shippingMethod) && empty($shippingMethod['type'])) {
			return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
		}

		if (array_key_exists('customer_type', $shippingMethod) && !empty($shippingMethod['customer_type'])) {
			$this->saveCheckoutMethod(self::METHOD_GUEST);
			$this->getQuote()->setSchrackCustomertype($shippingMethod['customer_type']);
			$this->getQuote()->collectTotals()->save();
		}

		$rate = isset($shippingMethod['type'])
              ? $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod['type'])
              : null;
		if (!$rate) {
			//return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping rate.')); // Schracklive - use a different msg. for debugging
            $this->getQuote()->collectTotals()->save();
            return array();
		}
		$this->getQuote()->getShippingAddress()->setShippingMethod($shippingMethod['type']);
		//$this->getQuote()->collectTotals()     //commnted by nagarro from magento 1.9
		//		->save();                //commented by nagarro from magento 1.9

        if (array_key_exists('saveShippingMethodByDirectAjax', $shippingMethod)) {
            $this->getQuote()->collectTotals()->save();
        } else {
            $this->getCheckout()
                ->setStepData('shipping_method', 'complete', true)
                ->setStepData('payment', 'allow', true);
        }

		return array();
	}


	public function prepareShippingMethod() {
// Mage::log($error, null, '/prospects/prospects.log');
        $isPickup = $this->getQuote()->getIsPickup();
        $shippingMethodCode = '';
        if ( $isPickup ) {
            $id = Mage::helper('schrackcustomer')->getPickupWarehouseId(Mage::getSingleton('customer/session')->getCustomer());
            $helper = Mage::helper('schrackshipping/pickup');
            $shippingMethodCode = $helper->getShippingMethodFromWarehouseId($id);
        } else {
            $helper = Mage::helper('schrackshipping/delivery');
            $shippingMethodCode = $helper->getShippingMethod();
        }
		$this->getQuote()->getShippingAddress()->setShippingMethod($shippingMethodCode);
		$this->getQuote()->collectTotals()->save();
    }
    
	/**
	 * Specify quote payment method
	 *
	 * @param   array $data
	 * @return  array
	 */
	public function savePayment($data) {
// Mage::log($error, null, '/prospects/prospects.log');
		if (empty($data)) {
			return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
		}

		$quote = $this->getQuote();

		if ( in_array($quote->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect', 'guest')) ) {
			$quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
		} else {
			if ($quote->isVirtual()) {
				$quote->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
			} else {
				$quote->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
			}
		}

		// shipping totals may be affected by payment method
		if (!$quote->isVirtual() && $quote->getShippingAddress()) {
			$quote->getShippingAddress()->setCollectShippingRates(true);
		}

                $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;   //added by nagarro from magento 1.9

		/* SCHRACKLIVE start */
		if (isset($data['schrack_custom_order_number'])) {
            $data['schrack_custom_order_number'] = str_replace(';', '&#59;', $data['schrack_custom_order_number']);
            $data['schrack_custom_order_number'] = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\x7F\xE2\n\r]/','', $data['schrack_custom_order_number']);
		    $this->getQuote()->setSchrackCustomOrderNumber($data['schrack_custom_order_number']);
        }
		if (isset($data['schrack_custom_project_info'])) {
            $data['schrack_custom_project_info'] = str_replace(';', '&#59;', $data['schrack_custom_project_info']);
            $data['schrack_custom_project_info'] = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\x7F\xE2\n\r]/','', $data['schrack_custom_project_info']);
		    $this->getQuote()->setSchrackCustomProjectInfo($data['schrack_custom_project_info']);
        }
		/* SCHRACKLIVE end */

		$payment = $quote->getPayment();
		$payment->importData($data);

		$quote->save();

		$this->getCheckout()
				->setStepData('payment', 'complete', true)
				->setStepData('review', 'allow', true);

		return array();
	}

	/**
	 * Create order based on checkout type. Create customer if necessary.
	 *
	 * @return Mage_Checkout_Model_Type_Onepage
	 */
	public function saveOrder($data = array()) {
// Mage::log($error, null, '/prospects/prospects.log');

        if ($this->getQuote()->getSchrackAddressTypeNew() == 1) {
            Mage::register('schrack_address_type_from_checkout', $this->getQuote()->getSchrackAddressType());
        }

        if ($this->getQuote()->getSchrackAddressPhone()) {
            Mage::register('schrack_address_phone_from_checkout', $this->getQuote()->getSchrackAddressPhone());
        }

        $pseudoWwsIds = array(
        'at' => '999999',
        'ba' => '999999',
        'be' => '888888',
        'bg' => '999999',
        'ch' => '999994',
        'com' => '999991',
        'cz' => '888888',
        'de' => '999996',
        'hr' => '999999',
        'hu' => '888888',
        'nl' => '999993',
        'pl' => '999999',
        'ro' => '999999',
        'rs' => '999999',
        'ru' => '',
        'sa' => '',
        'si' => '999999',
        'sk' => '999999');

        $countryCode = Mage::getStoreConfig('schrack/general/country');
        $countrySpecificPseudoCustomerID = $pseudoWwsIds[$countryCode];

		if (!empty($data)) {
			$email                     = '';
			$vatIdentificationNumber   = '';
			$companyRegistrationNumber = '';
			$gender                    = '';
			$lastname                  = '';
			$firstname                 = '';
			$name1                     = '';
			$name2                     = '';
			$name3                     = '';
			$street1                   = '';
			$postcode                  = '';
			$city                      = '';
			$countryID                 = '';
			$homepage                  = '';
			$phone                     = '';
			$phone_company             = '';
			$fax                       = '';
			$newsletter                = '';
			$localVAT                  = '';

			if (array_key_exists('email', $data)) {
				$email = $data['email']; // --> Source: localStorage.newCheckoutProcessCompanyRegistrationNumber
			}
			if (array_key_exists('vat_number', $data)) {
				$vatIdentificationNumber = strtoupper(str_replace(' ', '', $data['vat_number'])); // --> Source: localStorage.newCheckoutProcessVATIdentificationNumber
			}
			if (array_key_exists('reg_number', $data)) {
				$companyRegistrationNumber = $data['reg_number']; // --> Source: localStorage.newCheckoutProcessCompanyRegistrationNumber
			}
			if (array_key_exists('gender', $data)) {
				$gender = $data['gender'];   // --> Source: localStorage.
			}
			if (array_key_exists('firstname', $data)) {
				$firstname = $data['firstname'];   // --> Source: localStorage.
			}
			if (array_key_exists('lastname', $data)) {
				$lastname = $data['lastname'];   // --> Source: localStorage.
			}
			if (array_key_exists('name1', $data)) {
				$name1 = $data['name1'];   // --> Source: localStorage.
			}
			if (array_key_exists('name2', $data)) {
				$name2 = $data['name2'];   // --> Source: localStorage.
			}
			if (array_key_exists('name3', $data)) {
				$name3 = $data['name3'];   // --> Source: localStorage.
			}

			if (array_key_exists('street1', $data)) {
				$street1 = $data['street1'];   // --> Source: localStorage.
                if (is_array($street1) && !empty($street1)) {
                    $street1 = $street1[0];
                }
			}
			if (array_key_exists('postcode', $data)) {
				$postcode = $data['postcode'];   // --> Source: localStorage.
			}
			if (array_key_exists('city', $data)) {
				$city = $data['city'];   // --> Source: localStorage.
			}
			if (array_key_exists('country_id', $data)) {
				$countryID = $data['country_id'];   // --> Source: localStorage.
			}
			if (array_key_exists('homepage', $data)) {
				$homepage = $data['homepage'];   // --> Source: localStorage.
			}
			if (array_key_exists('telephone', $data)) {
				$phone = '+' . $data['telephone'];   // --> Source: localStorage.
                $phone = str_replace('++', '+', $phone);
			}
			if (array_key_exists('telephone_company', $data)) {
				$phone_company = '+' . $data['telephone_company'];   // --> Source: localStorage.
                $phone_company = str_replace('++', '+', $phone_company);
			}
			if (array_key_exists('fax', $data)) {
				$fax = '+' . $data['fax'];   // --> Source: localStorage.
                $fax = str_replace('++', '+', $fax);
			}
			if (array_key_exists('newsletter', $data)) {
				$newsletter = $data['newsletter'];   // --> Source: localStorage.
			}
			if (array_key_exists('local_vat', $data)) {
				$localVAT = strtoupper(str_replace(' ', '', $data['local_vat']));   // --> Source: localStorage.
			}

			if (array_key_exists('payment_method', $data)) {
				$paymentMethodCode = $data['payment_method'];   // --> Source: localStorage.
			}
		}

        if ($phone_company == '+') $phone_company = '';
        if ($phone == '+') $phone = '';
        if ($fax == '+') $fax = '';


        $this->extraLog(__LINE__,__METHOD__,"this->getQuote()->getSchrackCustomertype() = " . $this->getQuote()->getSchrackCustomertype());
        $this->extraLog(__LINE__,__METHOD__,"email from UI = $email");

		// Override validation on prospect / guest:
		$isInteressent = false;
		$isGast = false;

		if ( in_array($this->getQuote()->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect')) ) {
			$isInteressent = true;
		}

		if ( in_array($this->getQuote()->getSchrackCustomertype(), array('guest')) ) {
			$isGast = true;
		}

		if ($isInteressent || $isGast) {
			$this->getQuote()->setCheckoutMethod(self::METHOD_GUEST)->save();
		} else {
			$this->validate(); // --> Checks, if some checkout method is really available
		}

        $this->extraLog(__LINE__,__METHOD__,"isInteressent = $isInteressent");
        $this->extraLog(__LINE__,__METHOD__,"isGast = $isGast");

		$isNewCustomer = false;
		if ($isInteressent || $isGast) {
			// DO nothing with our prospects / guests --> no automatic address creation!!
		} else {
			switch ($this->getCheckoutMethod()) {
				case self::METHOD_GUEST:
					$this->_prepareGuestQuote();
					break;
				case self::METHOD_REGISTER:
					$this->_prepareNewCustomerQuote();
					$isNewCustomer = true;
					break;
				default:
					$this->_prepareCustomerQuote();
					break;
			}
		}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///// ------- Mail Section for Prospects (start) ---------- ////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $customer = $this->getQuote()->getCustomer();
        $this->extraLog(__LINE__,__METHOD__,"Stored customer: " . (($customer && $customer->getId()) ? $customer->getEmail() : '(non)'));

        if (in_array($this->getQuote()->getSchrackCustomertype(), array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
            $this->extraLog(__LINE__,__METHOD__,"in if ( guest or prospect )");
            $mailText    = 'Mailtext';
            $mailSubject = 'Mailsubject';

            $possiblePaymentMethodCodes = array(
                'payunitycw_visa' => 'Visa',
                'payunitycw_mastercard' => 'MasterCard',
                'checkmo' => Mage::getStoreConfig('payment/checkmo/title'),
                'schrackpo' => Mage::getStoreConfig('payment/schrackpo/title'),
                'free' => Mage::getStoreConfig('payment/free/title'),
                'schrackcod' => Mage::getStoreConfig('payment/schrackcod/title'),
                'googlecheckout' => Mage::getStoreConfig('payment/googlecheckout/title'),
                'purchaseorder' => Mage::getStoreConfig('payment/purchaseorder/title'),
                'paypal_standard' => Mage::getStoreConfig('payment/paypal_standard/title'));
            $paymentMethod = $possiblePaymentMethodCodes[$paymentMethodCode];
//Mage::log($paymentMethod, null, '/prospects/prospects.log');
            $genderArray = array(1 => $this->_helper->__('Male'), 2 => $this->_helper->__('Female'));
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( guest or prospect )");
        }

        if ($this->getQuote()->getSchrackCustomertype() == 'oldLightProspect') {
            $this->extraLog(__LINE__,__METHOD__,"in if ( oldLightProspect )");
            // Send mail to customer support for Old Light Prospect:
            // Translation : Mage_Checkout.csv
            $mailSubject = $this->_helper->__('Former Light Prospect Order');

            if (intval($customer->getSchrackNewsletter()) == 1) {
                $schrackNewsletter = 'Newsletter: ' . $this->_helper->__('Yes') . '<br>';
            } else {
                $schrackNewsletter = '';
            }

            $reformattedVATIdentificationNumber = substr_replace($vatIdentificationNumber, ' ', 2, 0);

            $mailText  = $this->_helper->__('Former Light Prospect Order Headline') . '<br><br>';
            $mailText .= 'Mail-Template #01<br>';
            $mailText .= '<b>' . $this->_helper->__('WWS order number:') . '</b>' . ' ' . $this->getQuote()->getSchrackWwsOrderNumber() . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Personal Data') . '</b>:<br>';
            $mailText .= $schrackNewsletter;
            $mailText .= $this->_helper->__('Gender') . ': ' . $genderArray[$customer->getGender()] . '<br>';
            $mailText .= $this->_helper->__('First Name') . ': ' . $customer->getFirstname() . '<br>';
            $mailText .= $this->_helper->__('Last Name') . ': ' . $customer->getLastname() . '<br>';
            $mailText .= $this->_helper->__('Email') . ': ' . $customer->getEmail() . '<br>';
            $mailText .= $this->_helper->__('Phone') . ': ' . $customer->getSchrackTelephone() . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Company Information') . '</b>:<br>';
            $mailText .= $this->_helper->__('Companyname') . ': ' . $name1 . '<br>';
            if ($localVAT) {
                $mailText .= $this->_helper->__('VAT Identification Number Local Placeholder') . ': ' . $vatIdentificationNumber . '<br>';
            } else {
                $mailText .= $this->_helper->__('VAT Identification Number') . ': ' . $reformattedVATIdentificationNumber . '<br>';
            }
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone_company . '<br>';
            $mailText .= $this->_helper->__('Website') . ': ' . $homepage . '<br>';
            $mailText .= $this->_helper->__('Webshop Country') . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
            $mailText .= $this->_helper->__('Date') . ': ' . date('Y-m-d H:i:s') . '<br>';
            $mailText .= $this->_helper->__('Payment Method') . ': ' . $paymentMethod . '<br><br>';
            $mailText .= $this->_helper->__('billing address') . ' = ' . $this->_helper->__('shipping address') . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('billing address') . '</b>:<br>';
            if (is_array($street1) && !empty($street1)) {
                Mage::log('#20:', null, 'street_as_array.log');
                Mage::log($street1, null, 'street_as_array.log');
                $street1 = $street1[0];
            }
            $mailText .= $this->_helper->__('street') . ': ' . $street1 . '<br>';
            $mailText .= $this->_helper->__('Zip') . ': ' . $postcode . '<br>';
            $mailText .= $this->_helper->__('City') . ': ' . $city . '<br>';
            $mailText .= $this->_helper->__('Country') . ': ' . $countryID;

            Mage::log("\n" . 'mailText-Case #0001', null, 'street_as_array.log');

            // Send upgrade-message to S4Y ----
            $prospectMessageContent                                   = array();
            $prospectMessageContent['schrack_prospect_type']          = 2;  // LIGHT_UPGRADE
            $prospectMessageContent['prospect_source']                = 0;  // SHOP sends always 0 as source
            $prospectMessageContent['email']                          = $customer->getEmail();
            $prospectMessageContent['lastname']                       = $customer->getLastname();
            $prospectMessageContent['firstname']                      = $customer->getFirstname();
            if ($localVAT) {
                $prospectMessageContent['vat_local_number'] = $vatIdentificationNumber;
            } else {
                $prospectMessageContent['vat_identification_number'] = $vatIdentificationNumber;
            }

            if (strlen($companyRegistrationNumber) > 14) {
                $prospectMessageContent['company_registration_number'] = substr($companyRegistrationNumber, 0, 14);
            } else {
                $prospectMessageContent['company_registration_number'] = $companyRegistrationNumber;
            }
            if (strlen(Mage::getStoreConfig('schrack/customer/registration_advisor_redirect')) > 2) {
                $prospectMessageContent['schrack_advisor_principal_name'] = Mage::getStoreConfig('schrack/customer/registration_advisor_redirect');
            } else {
                $prospectMessageContent['schrack_advisor_principal_name'] = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
            }
            if (mb_strlen($name1, 'UTF-8') == 1) {
                $name1 = $name1 . 'NN';
            }
            if (mb_strlen($name1, 'UTF-8') > 30) {
                Mage::log($customer->getEmail() . ' -> Checkout: Company Name 1 -> ' . $name1 . ' -> Too many characters in name1 (#1)' , null, 'checkout_company_name.err.log');
                die($name1 . ' -> Too many characters in name1 (#1)');
            }
            if (mb_strlen($name2, 'UTF-8') > 30) {
                Mage::log($customer->getEmail() .' -> Checkout: Company Name 2 -> ' . $name2 . ' -> Too many characters in name2 (#1)' , null, 'checkout_company_name.err.log');
                die($name2 . ' -> Too many characters in name2 (#1)');
            }
            if (mb_strlen($name3, 'UTF-8') > 30) {
                Mage::log($customer->getEmail() . '-> Checkout: Company Name 3 -> ' . $name3 . ' -> Too many characters in name3 (#1)' , null, 'checkout_company_name.err.log');
                die($name3 . ' -> Too many characters in name3 (#1)');
            }
            $prospectMessageContent['name1']                          = $name1;
            $prospectMessageContent['name2']                          = $name2;
            $prospectMessageContent['name3']                          = $name3;
            $prospectMessageContent['street']                         = $street1;
            $prospectMessageContent['postcode']                       = $postcode;
            $prospectMessageContent['city']                           = $city;
            $prospectMessageContent['country_id']                     = str_replace('COM', '', $countryID);
            $prospectMessageContent['homepage']                       = $homepage;
            if ($phone_company == '+') $phone_company = '';
            if (strlen($phone_company) > 0 && strlen($phone_company) < 7) {
                if ($phone) {
                    $prospectMessageContent['telephone_company'] = $this->formatPhonenumberMQ($phone);
                }
            } else {
                $prospectMessageContent['telephone_company'] = $this->formatPhonenumberMQ($phone_company);
            }
            $prospectMessageContent['newsletter'] = $newsletter;
            Mage::getSingleton('core/session')->setUserModificationAction('prospect checkout data completion');

            $wwsCustomerId = $this->getQuote()->getSchrackWwsCustomerId();
            $realWwsCustomerId = true;
            if ($wwsCustomerId == '' || $wwsCustomerId == null || $wwsCustomerId == $countrySpecificPseudoCustomerID) {
                $realWwsCustomerId = false;
            }
            if ($realWwsCustomerId == true) {
                $err_msg  = date('Y-m-d H:i:s');
                $err_msg .= ': Old Light Prospect Order Checkout -> INAVLID_DATA: ';
                $err_msg .= $this->getQuote()->getSchrackWwsOrderNumber() . ' (= WWSOrderID) -- ' . $customer->getEmail();

                Mage::log($err_msg, null, '/prospects/prospect_err.log');
            } else {
                $prospect = Mage::getSingleton('crm/connector')->putProspect($prospectMessageContent);
                $prospectMessageContent['DEV-HINT'] = date('Y-m-d H:i:s') . ' LIGHT_PROSPECT_UPGRADE (Checkout)';
            }
            Mage::log($prospectMessageContent, null, '/prospects/prospects_reg.log');
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( oldLightProspect )");
        }

        if ($this->getQuote()->getSchrackCustomertype() == 'oldFullProspect') {
            $this->extraLog(__LINE__,__METHOD__,"in if ( oldFullProspect )");
            // Send mail to customer support for Old Full Prospect:
            $mailSubject = $this->_helper->__('Full Prospect Order');

            if (intval($customer->getSchrackNewsletter()) == 1) {
                $schrackNewsletter = 'Newsletter: ' . $this->_helper->__('Yes') . '<br>';
            } else {
                $schrackNewsletter = '';
            }

            $reformattedVATIdentificationNumber = substr_replace($customer->getAccount()->getVatIdentificationNumber(), ' ', 2, 0);

            if (stristr($phone_company, 'undefined')) {
                $phone_company = '';
            }

            if (stristr($homepage, 'undefined')) {
                $homepage = '';
            }

            $fetchedStreet = '';
            if (stristr($this->getQuote()->getBillingAddress()->getStreet()[0], 'undefined')) {

                $account = $customer->getAccount();
                $billingAddress = $account->getBillingAddress();
                $street = $billingAddress->getStreet();
                if (is_array($street)) $fetchedStreet = $street[0];
            } else {
                $fetchedStreet = $this->getQuote()->getBillingAddress()->getStreet()[0];
            }

            $fetchedPostcode = '';
            if ($this->getQuote()->getBillingAddress()->getPostcode() == 0
                || $this->getQuote()->getBillingAddress()->getPostcode() == '0') {
                $account = $customer->getAccount();
                $billingAddress = $account->getBillingAddress();
                $fetchedPostcode = $billingAddress->getPostcode();
            } else {
                $fetchedPostcode = $this->getQuote()->getBillingAddress()->getPostcode();
            }

            $mailText  = $this->_helper->__('Full Prospect Order Headline') . '<br><br>';
            $mailText .= 'Mail-Template #02<br>';
            $mailText .= '<b>' . $this->_helper->__('WWS order number:') . '</b>' .  ' ' . $this->getQuote()->getSchrackWwsOrderNumber() . '<br><br>';
            $mailText .= $schrackNewsletter;
            $mailText .= $this->_helper->__('Gender') . ': ' . $genderArray[$customer->getGender()] . '<br>';
            $mailText .= $this->_helper->__('First Name') . ': ' . $customer->getFirstname() . '<br>';
            $mailText .= $this->_helper->__('Last Name') . ': ' . $customer->getLastname() . '<br>';
            $mailText .= $this->_helper->__('Email') . ': ' . $customer->getEmail() . '<br>';
            $mailText .= $this->_helper->__('Phone') . ': ' . $customer->getSchrackTelephone() . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Company Information') . '</b>:<br>';
            $mailText .= $this->_helper->__('Companyname') . ': ' . $customer->getAccount()->getName1() . '<br>';
            $mailText .= $this->_helper->__('Companyname') . ' #2: ' . $customer->getAccount()->getName2() . '<br>';
            $mailText .= $this->_helper->__('Contact') . ': ' . $customer->getAccount()->getName3() . '<br>';
            $mailText .= $this->_helper->__('VAT Identification Number') . ': ' . $reformattedVATIdentificationNumber . '<br>';
            $mailText .= $this->_helper->__('VAT Identification Number Local Placeholder') . ': ' . $customer->getAccount()->getVatLocalNumber() . '<br>';
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone_company . '<br>';
            $mailText .= $this->_helper->__('Website') . ': ' . $homepage . '<br>';
            $mailText .= $this->_helper->__('Webshop Country') . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
            $mailText .= $this->_helper->__('Date') . ': ' . date('Y-m-d H:i:s') . '<br>';
            $mailText .= $this->_helper->__('Payment Method') . ': ' . $paymentMethod . '<br><br>';
            $mailText .= $this->_helper->__('billing address') . ' = ' . $this->_helper->__('shipping address') . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('billing address') . '</b>:<br>';

            if (stristr($fetchedStreet,'Array')) {
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');

                $queryCustomerData  = "SELECT customerdata FROM wws_insert_update_order_request";
                $queryCustomerData .= " WHERE wws_order_id LIKE '" . $this->getQuote()->getSchrackWwsOrderNumber() . "' AND response_fetched_successfully = 1";
                $queryCustomerData .= " AND customerdata IS NOT NULL";
                $queryCustomerData .= " ORDER BY request_datetime DESC LIMIT 1";

                $queryResult = $readConnection->query($queryCustomerData);

                $customerData = array();
                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $customerData = unserialize($recordset['customerdata']);
                    }
                }


                if ($customerData && isset($customerData['customer_street'])
                    && $customerData['customer_street'] != 'undefined'
                    && !stristr($customerData['customer_street'],'Array')) {
                    $fetchedStreet = $customerData['customer_street'];
                } else {
                    $customer = $this->getQuote()->getCustomer();
                    if (is_object($customer)) {
                        $account = $customer->getAccount();
                        if (is_object($account)) {
                            $billingAddress = $account->getBillingAddress();
                            $street = $billingAddress->getStreet();
                            if (is_array($street)) $fetchedStreet = $street[0];

                            if (is_array($fetchedStreet) || stristr($fetchedStreet,'Array')) {
                                Mage::log('#Onepage -> fetchedStreet still Array', null, 'street_as_array.log');
                            }
                        } else {
                            Mage::log('#Onepage -> no account found', null, 'street_as_array.log');
                        }
                    } else {
                        Mage::log('#Onepage -> no customer found', null, 'street_as_array.log');
                    }
                }
            }
            $mailText .= $this->_helper->__('street') . ': ' . $fetchedStreet . '<br>'; // getPrimaryBillingAddress?
            $mailText .= $this->_helper->__('Zip') . ': ' . $fetchedPostcode . '<br>'; // getPrimaryBillingAddress?
            $mailText .= $this->_helper->__('City') . ': ' . $this->getQuote()->getBillingAddress()->getCity() . '<br>';
            $mailText .= $this->_helper->__('Country') . ': ' . $this->getQuote()->getBillingAddress()->getCountry() . '<br>';

            Mage::log("\n" . 'mailText-Case #0002', null, 'street_as_array.log');
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( oldFullProspect )");
        }

        // Send mail to customer support for normal (non-special-route) guest order:
        if ($this->_helper->__('Normal Guest-Order Mail Headline')
            && $this->_helper->__('Normal Guest-Order Mail Subject')
            && $this->getQuote()->getSchrackCustomertype() == 'guest'
            && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 0) {
            $this->extraLog(__LINE__,__METHOD__,"in if ( guest && specialRouteForGuest )");

            $reformattedVATIdentificationNumber = substr_replace($vatIdentificationNumber, ' ', 2, 0);

            $mailSubject = $this->_helper->__('Normal Guest-Order Mail Subject');

            $mailText = $this->_helper->__('Normal Guest-Order Mail Headline');

            $genderArrayGender = $genderArray[$gender];

            if (
                $genderArrayGender == '' || stristr($genderArrayGender, 'undefined') ||
                $firstname == '' || stristr($firstname, 'undefined') ||
                $lastname == '' || stristr($lastname, 'undefined') ||
                $email == '' || stristr($email, 'undefined') || stristr($phone_company, 'undefined') ||
                $name1 == '' || stristr($name1, 'undefined') || stristr($phone, 'undefined') ||
                stristr($vatIdentificationNumber, 'undefined') ||
                stristr($reformattedVATIdentificationNumber, 'undefined') ||
                stristr($paymentMethod, 'undefined') ||
                $street1 == '' || stristr($street1, 'undefined') ||
                $postcode == '' || stristr($postcode, 'undefined') ||
                $city == '' || stristr($city, 'undefined') ||
                $countryID == '' || stristr($countryID, 'undefined')
            ) {
                $this->extraLog(__LINE__,__METHOD__,"in if ( something empty or undefined )");
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');

                $queryCustomerData  = "SELECT customerdata FROM wws_insert_update_order_request";
                $queryCustomerData .= " WHERE wws_order_id LIKE '" . $this->getQuote()->getSchrackWwsOrderNumber() . "' AND response_fetched_successfully = 1";
                $queryCustomerData .= " AND customerdata IS NOT NULL";
                $queryCustomerData .= " ORDER BY request_datetime DESC LIMIT 1";

                $queryResult = $readConnection->query($queryCustomerData);

                $customerData = array();
                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $customerData = unserialize($recordset['customerdata']);
                    }
                    //Mage::log($customerData, null, 'guest_checkout_data.log');
                    if ($genderArrayGender == '' || stristr($genderArrayGender, 'undefined')) {
                        $genderArrayGender = $customerData['gender'];
                        $genderArrayGender = str_replace('undefined', '', $genderArrayGender);
                    }

                    if ($firstname == '' || stristr($firstname, 'undefined')) {
                        $firstname = $customerData['firstname'];
                        $firstname = str_replace('undefined', '', $firstname);
                    }

                    if ($lastname == '' || stristr($lastname, 'undefined')) {
                        $lastname = $customerData['lastname'];
                        $lastname = str_replace('undefined', '', $lastname);
                    }

                    if ($email == '' || stristr($email, 'undefined')) {
                        $email = $customerData['email'];
                        $email = str_replace('undefined', '', $email);
                    }

                    if (stristr($phone, 'undefined')) {
                        $phone = $customerData['company_phone'];
                        $phone = str_replace('undefined', '', $phone);
                    }

                    if (stristr($vatIdentificationNumber, 'undefined')) {
                        $vatIdentificationNumber = $customerData['local_uid'];
                        $vatIdentificationNumber = str_replace('undefined', '', $vatIdentificationNumber);
                    }

                    if (stristr($reformattedVATIdentificationNumber, 'undefined')) {
                        $reformattedVATIdentificationNumber = $customerData['uid'];
                        $reformattedVATIdentificationNumber = str_replace('undefined', '', $reformattedVATIdentificationNumber);
                    }

                    if ($name1 == '' || stristr($name1, 'undefined')) {
                        $name1 = $customerData['customer_companyname1'];
                        $name1 = str_replace('undefined', '', $name1);
                    }

                    if (stristr($name2, 'undefined')) {
                        $name2 = $customerData['customer_companyname2'];
                        $name2 = str_replace('undefined', '', $name2);
                    }

                    if (stristr($name3, 'undefined')) {
                        $name3 = $customerData['customer_company_contactperson'];
                        $name3 = str_replace('undefined', '', $name3);
                    }

                    if ($street1 == '' || stristr($street1, 'undefined')) {
                        $street1 = $customerData['customer_street'];
                        $street1 = str_replace('undefined', '', $street1);
                        if (is_array($street1) && !empty($street1)) {
                            Mage::log('#55:', null, 'street_as_array.log');
                            Mage::log($street1, null, 'street_as_array.log');
                            $street1 = $street1[0];
                        }
                    }

                    if ($postcode == '' || stristr($postcode, 'undefined') || $postcode == 0 || $postcode == '0') {
                        $postcode = $customerData['customer_postcode'];
                        $postcode = str_replace('undefined', '', $postcode);
                    }

                    if ($city == '' || stristr($city, 'undefined')) {
                        $city = $customerData['customer_city'];
                        $city = str_replace('undefined', '', $city);
                    }

                    if ($countryID == '' || stristr($countryID, 'undefined')) {
                        $countryID = $customerData['customer_country'];
                        $countryID = str_replace('undefined', '', $countryID);
                    }
                }

                if (stristr($homepage, 'undefined')) {
                    $homepage = '';
                }
            } else {
                $this->extraLog(__LINE__,__METHOD__,"in else ( something empty or undefined )");
            }

            $mailText .= 'Mail-Template #03<br>';
            $mailText .= '<b>' . $this->_helper->__('WWS order number:') . '</b>' .  ' ' . $this->getQuote()->getSchrackWwsOrderNumber() . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Personal Data') . '</b>:<br>';
            $mailText .= $this->_helper->__('Gender') . ': ' . $genderArrayGender . '<br>';
            $mailText .= $this->_helper->__('First Name') . ': ' . $firstname . '<br>';
            $mailText .= $this->_helper->__('Last Name') . ': ' . $lastname . '<br>';
            $mailText .= $this->_helper->__('Email') . ': ' . $email . '<br>';
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Company Information') . '</b>:<br>';
            $mailText .= $this->_helper->__('Companyname') . ': ' . $name1 . '<br>';
            $mailText .= $this->_helper->__('Companyname') . ' #2: ' . $name2 . '<br>';
            $mailText .= $this->_helper->__('Contact') . ': ' . $name3 . '<br>';
            if ($localVAT) {
                $mailText .= $this->_helper->__('VAT Identification Number Local Placeholder') . ': ' . $vatIdentificationNumber . '<br>';
            } else {
                $mailText .= $this->_helper->__('VAT Identification Number') . ': ' . $reformattedVATIdentificationNumber . '<br>';
            }
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone_company . '<br>';
            $mailText .= $this->_helper->__('Email') . ': ' . $email . '<br>';
            $mailText .= $this->_helper->__('Website') . ': ' . $homepage . '<br>';
            $mailText .= $this->_helper->__('Webshop Country') . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
            $mailText .= $this->_helper->__('Date') . ': ' . date('Y-m-d H:i:s') . '<br>';
            $mailText .= $this->_helper->__('Payment Method') . ': ' . $paymentMethod . '<br><br>';
            $mailText .= $this->_helper->__('billing address') . ' = ' . $this->_helper->__('shipping address') . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('billing address') . '</b>:<br>';
            if (is_array($street1) && !empty($street1)) {
                Mage::log('#21:', null, 'street_as_array.log');
                Mage::log($street1, null, 'street_as_array.log');
                $street1 = $street1[0];
            }
            $mailText .= $this->_helper->__('street') . ': ' . $street1 . '<br>';
            $mailText .= $this->_helper->__('Zip') . ': ' . $postcode . '<br>';
            $mailText .= $this->_helper->__('City') . ': ' . $city . '<br>';
            $mailText .= $this->_helper->__('Country') . ': ' . $countryID . '<br>';

            Mage::log("\n" . 'mailText-Case #0003', null, 'street_as_array.log');

            $mail = new Zend_Mail('utf-8');
            $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
                ->setSubject($mailSubject)
                ->setBodyHtml($mailText);

            // Send mail schrack support employee(s):
            $checkoutEmailDestinationProspects = Mage::getStoreConfig('schrack/new_self_registration/checkoutEmailDestinationProspects');
            if ($checkoutEmailDestinationProspects) {
                if (stristr($checkoutEmailDestinationProspects, ';')) {
                    // Send mail to multiple recipients, if separated by semicolon:
                    $emailRecipients = explode(';', preg_replace('/\s+/', '', $checkoutEmailDestinationProspects));
                    foreach ($emailRecipients as $index => $emailRecipient) {
                        $mail->addTo($emailRecipient);
                    }
                } else {
                    $mail->addTo($checkoutEmailDestinationProspects);
                }
                Mage::log('Email #1 for Order = ' . $this->getQuote()->getSchrackWwsOrderNumber() . ' should be send on ' . date('Y-m-d H:i:s') . ' to ' . $email, null, '/prospects/prospect_mail_send.log');
                Mage::log($mailText, null, '/prospects/prospect_mail_send.log');
                if (stristr($mailText,': Array')) {
                    Mage::log('Error #1 -> ' . $mailText , null, 'street_as_array.log');
                    die('Street as Array');
                }
                $mail->send();
                Mage::log('Email send verified #1 on ' . date('Y-m-d H:i:s') . ' to ' . $email, null, '/prospects/prospect_mail_send.log');
            }
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( guest && specialRouteForGuest )");
        }

        if ($this->getQuote()->getSchrackCustomertype() == 'newProspect'
            || ($this->getQuote()->getSchrackCustomertype() == 'guest'
                && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 1)) {
            $this->extraLog(__LINE__,__METHOD__,"in if ( newProspect || (guest && specialRouteForGuest) )");

            $specialRuoteForGuestDescription = '';
            if (($this->getQuote()->getSchrackCustomertype() == 'guest'
                && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 1)) {
                $specialRuoteForGuestDescription = $this->_helper->__('Special Guest-Order');
                $mailText  = $this->_helper->__('Order Request Special Guest-Order Headline') . '<br><br>';
            } else {
                $mailText  = $this->_helper->__('Order Request New Full Prospect Headline') . '<br><br>';
            }

            // 1. Send mail to customer support for New Full Prospect:
            $mailSubject = $this->_helper->__('Order Request New Full Prospect');

            if (intval($newsletter) == 1) {
                $schrackNewsletter = 'Newsletter: ' . $this->_helper->__('Yes') . '<br>';
            } else {
                $schrackNewsletter = '';
            }

            $reformattedVATIdentificationNumber = substr_replace($vatIdentificationNumber, ' ', 2, 0);

            $genderArrayGender = $genderArray[$gender];

            if (
                $genderArrayGender == '' || stristr($genderArrayGender, 'undefined') ||
                $firstname == '' || stristr($firstname, 'undefined') ||
                $lastname == '' || stristr($lastname, 'undefined') ||
                $email == '' || stristr($email, 'undefined') || stristr($phone_company, 'undefined') ||
                $name1 == '' || stristr($name1, 'undefined') || stristr($phone, 'undefined') ||
                stristr($vatIdentificationNumber, 'undefined') ||
                stristr($reformattedVATIdentificationNumber, 'undefined') ||
                stristr($paymentMethod, 'undefined') ||
                $street1 == '' || stristr($street1, 'undefined') ||
                $postcode == '' || stristr($postcode, 'undefined') ||
                $city == '' || stristr($city, 'undefined') ||
                $countryID == '' || stristr($countryID, 'undefined')
            ) {
                $this->extraLog(__LINE__,__METHOD__,"in if ( something empty or undefined )");
                $resource = Mage::getSingleton('core/resource');
                $readConnection = $resource->getConnection('core_read');

                $queryCustomerData  = "SELECT customerdata FROM wws_insert_update_order_request";
                $queryCustomerData .= " WHERE wws_order_id LIKE '" . $this->getQuote()->getSchrackWwsOrderNumber() . "' AND response_fetched_successfully = 1";
                $queryCustomerData .= " AND customerdata IS NOT NULL";
                $queryCustomerData .= " ORDER BY request_datetime DESC LIMIT 1";

                $queryResult = $readConnection->query($queryCustomerData);

                $customerData = array();
                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $customerData = unserialize($recordset['customerdata']);
                    }
                    //Mage::log($customerData, null, 'guest_checkout_data.log');
                    if ($genderArrayGender == '' || stristr($genderArrayGender, 'undefined')) {
                        $genderArrayGender = $customerData['gender'];
                    }

                    if ($firstname == '' || stristr($firstname, 'undefined')) {
                        $firstname = $customerData['firstname'];
                    }

                    if ($lastname == '' || stristr($lastname, 'undefined')) {
                        $lastname = $customerData['lastname'];
                    }

                    if ($email == '' || stristr($email, 'undefined')) {
                        $email = $customerData['email'];
                    }

                    if (stristr($phone, 'undefined')) {
                        $phone = $customerData['company_phone'];
                    }

                    if (stristr($vatIdentificationNumber, 'undefined')) {
                        $vatIdentificationNumber = $customerData['local_uid'];
                    }

                    if (stristr($reformattedVATIdentificationNumber, 'undefined')) {
                        $reformattedVATIdentificationNumber = $customerData['uid'];
                    }

                    if ($name1 == '' || stristr($name1, 'undefined')) {
                        $name1 = $customerData['customer_companyname1'];
                    }

                    if (stristr($name2, 'undefined')) {
                        $name2 = $customerData['customer_companyname2'];
                    }

                    if (stristr($name3, 'undefined')) {
                        $name3 = $customerData['customer_company_contactperson'];
                    }

                    if ($street1 == '' || stristr($street1, 'undefined')) {
                        $street1 = $customerData['customer_street'];
                        if (is_array($street1) && !empty($street1)) {
                            Mage::log('#77:', null, 'street_as_array.log');
                            Mage::log($street1, null, 'street_as_array.log');
                            $street1 = $street1[0];
                        }
                    }

                    if ($postcode == '' || stristr($postcode, 'undefined') || $postcode == 0 || $postcode == '0') {
                        $postcode = $customerData['customer_postcode'];
                    }

                    if ($city == '' || stristr($city, 'undefined')) {
                        $city = $customerData['customer_city'];
                    }

                    if ($countryID == '' || stristr($countryID, 'undefined')) {
                        $countryID = $customerData['customer_country'];
                    }
                }

                if (stristr($homepage, 'undefined')) {
                    $homepage = '';
                }
            } else {
                $this->extraLog(__LINE__,__METHOD__,"in else ( something empty or undefined )");
            }

            if ($specialRuoteForGuestDescription) $mailText .= '<b>' . $specialRuoteForGuestDescription . '</b>:<br>';
            $mailText .= 'Mail-Template #04<br>';
            $mailText .= '<b>' . $this->_helper->__('WWS order number:') . '</b>' .  ' ' . $this->getQuote()->getSchrackWwsOrderNumber() . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Personal Data') . '</b>:<br>';
            $mailText .= $schrackNewsletter;
            if ($genderArrayGender == 'undefined') $genderArrayGender = '';
            $mailText .= $this->_helper->__('Gender') . ': ' . $genderArrayGender . '<br>';
            $mailText .= $this->_helper->__('First Name') . ': ' . $firstname . '<br>';
            $mailText .= $this->_helper->__('Last Name') . ': ' . $lastname . '<br>';
            $mailText .= $this->_helper->__('Email') . ': ' . $email . '<br>';
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('Company Information') . '</b>:<br>';
            $mailText .= $this->_helper->__('Companyname') . ': ' . $name1 . '<br>';
            $mailText .= $this->_helper->__('Companyname') . ' #2: ' . $name2 . '<br>';
            $mailText .= $this->_helper->__('Contact') . ': ' . $name3 . '<br>';
            if ($localVAT) {
                $mailText .= $this->_helper->__('VAT Identification Number Local Placeholder') . ': ' . $vatIdentificationNumber . '<br>';
            } else {
                $mailText .= $this->_helper->__('VAT Identification Number') . ': ' . $reformattedVATIdentificationNumber . '<br>';
            }
            if ($phone_company == 'undefined' || $phone_company == '+undefined') $phone_company = '';
            $mailText .= $this->_helper->__('Phone') . ': ' . $phone_company . '<br>';
            $mailText .= $this->_helper->__('Website') . ': ' . $homepage . '<br>';
            $mailText .= $this->_helper->__('Webshop Country') . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
            $mailText .= $this->_helper->__('Date') . ': ' . date('Y-m-d H:i:s') . '<br>';
            $mailText .= $this->_helper->__('Payment Method') . ': ' . $paymentMethod . '<br><br>';
            $mailText .= $this->_helper->__('billing address') . ' = ' . $this->_helper->__('shipping address') . '<br><br>';
            $mailText .= '<b>' . $this->_helper->__('billing address') . '</b>:<br>';
            if (is_array($street1) && !empty($street1)) {
                Mage::log('#22:', null, 'street_as_array.log');
                Mage::log($street1, null, 'street_as_array.log');
                $street1 = $street1[0];
            }
            $mailText .= $this->_helper->__('street') . ': ' . $street1 . '<br>';
            if ($postcode == 0 || $postcode == '0') {
                if (!isset($customerData['customer_postcode'])) {
                    $resource = Mage::getSingleton('core/resource');
                    $readConnection = $resource->getConnection('core_read');

                    $queryCustomerData  = "SELECT customerdata FROM wws_insert_update_order_request";
                    $queryCustomerData .= " WHERE wws_order_id LIKE '" . $this->getQuote()->getSchrackWwsOrderNumber() . "' AND response_fetched_successfully = 1";
                    $queryCustomerData .= " AND customerdata IS NOT NULL";
                    $queryCustomerData .= " ORDER BY request_datetime DESC LIMIT 1";

                    Mage::log($queryCustomerData, null, 'checkout_data.log');

                    $queryResult = $readConnection->query($queryCustomerData);

                    $customerData = array();
                    if ($queryResult->rowCount() > 0) {
                        foreach ($queryResult as $recordset) {
                            $customerData = unserialize($recordset['customerdata']);
                        }
                    }
                }

                if(array_key_exists('customer_postcode', $customerData) && !empty($customerData['customer_postcode'])) {
                        // Do nothing -> the good case
                } else {
                    Mage::log('#22-1: (Postcode = 0) ' . $email , null, 'street_as_array.log');
                    Mage::log($street1 , null, 'street_as_array.log');
                    die('Postcode cannot be 0');
                }
            }
            $mailText .= $this->_helper->__('Zip') . ': ' . $postcode . '<br>';
            $mailText .= $this->_helper->__('City') . ': ' . $city . '<br>';
            $mailText .= $this->_helper->__('Country') . ': ' . $countryID . '<br>';

            Mage::log("\n" . 'mailText-Case #0004', null, 'street_as_array.log');

            if ($this->getQuote()->getSchrackCustomertype() == 'newProspect') {
                $this->extraLog(__LINE__,__METHOD__,"in if ( newProspect )");
                // 2. Send message to S4Y (create new full prospect) ---> not for the SPECIAL GUEST!!!
                $prospectMessageContent                                   = array();
                $prospectMessageContent['schrack_prospect_type']          = 1;
                $prospectMessageContent['prospect_source']                = 0;  // SHOP sends always 0 as source
                $prospectMessageContent['company_prefix']                 = '';
                $prospectMessageContent['gender']                         = $gender;
                $prospectMessageContent['lastname']                       = $lastname;
                $prospectMessageContent['firstname']                      = $firstname;
                if ($email == '' || $email == 'undefined') {
                    Mage::log(date('Y-m-d H:i:s') . ': Self Registration Checkout -> INAVLID_EMAIL: ' . $email, null, '/prospects/prospect_err.log');
                    Mage::throwException(date('Y-m-d H:i:s') . ': Self Registration Checkout -> INAVLID_EMAIL: ' . $email);
                }
                $prospectMessageContent['email']                          = $email;
                $prospectMessageContent['wws_branch_id']                  = Mage::getStoreConfig('schrack/general/branch');
                $prospectMessageContent['sales_area']                     = Mage::getStoreConfig('schrack/general/branch');
                if ($localVAT) {
                    $prospectMessageContent['vat_local_number'] = $vatIdentificationNumber;
                } else {
                    $prospectMessageContent['vat_identification_number'] = $vatIdentificationNumber;
                }

                if ($companyRegistrationNumber == 'undefined') $companyRegistrationNumber = '';
                if (strlen($companyRegistrationNumber) > 14) {
                    $prospectMessageContent['company_registration_number'] = substr($companyRegistrationNumber, 0, 14);
                } else {
                    $prospectMessageContent['company_registration_number'] = $companyRegistrationNumber;
                }

                if ($phone == '+') $phone = '';
                $prospectMessageContent['schrack_telephone']              = $this->formatPhonenumberMQ($phone);
                if ($fax == '+') $fax = '';
                $prospectMessageContent['schrack_fax']                    = $this->formatPhonenumberMQ($fax);
                if (strlen(Mage::getStoreConfig('schrack/customer/registration_advisor_redirect')) > 2) {
                    $prospectMessageContent['schrack_advisor_principal_name'] = Mage::getStoreConfig('schrack/customer/registration_advisor_redirect');
                } else {
                    $prospectMessageContent['schrack_advisor_principal_name'] = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
                }
                $prospectMessageContent['currency_code']                  = Mage::app()->getStore()->getBaseCurrencyCode();
                $prospectMessageContent['shop_language']                  = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(), 0 , 2));
                // Fix for Saudi Arabia:
                if (stristr($prospectMessageContent['shop_language'], 'AR')) $prospectMessageContent['shop_language'] = 'EN';
                if (mb_strlen($name1, 'UTF-8') == 1) {
                    $name1 = $name1 . 'NN';
                }
                if (mb_strlen($name1, 'UTF-8') == 1) {
                    $name1 = $name1 . 'NN';
                }
                if (mb_strlen($name1, 'UTF-8') > 30) {
                    Mage::log($customer->getEmail() . ' -> Checkout: Company Name 1 -> ' . $name1 . ' -> Too many characters in name1 (#2)' , null, 'checkout_company_name.err.log');
                    die($name1 . ' -> Too many characters in name1 (#2)');
                }
                if (mb_strlen($name2, 'UTF-8') > 30) {
                    Mage::log($customer->getEmail() . ' -> Checkout: Company Name 2 -> ' . $name2 . ' -> Too many characters in name2 (#2)' , null, 'checkout_company_name.err.log');
                    die($name2 . ' -> Too many characters in name2 (#2)');
                }
                if (mb_strlen($name3, 'UTF-8') > 30) {
                    Mage::log($customer->getEmail() . ' -> Checkout: Company Name 3 -> ' . $name3 . ' -> Too many characters in name3 (#2)' , null, 'checkout_company_name.err.log');
                    die($name3 . ' -> Too many characters in name3 (#2)');
                }
                $prospectMessageContent['name1']                          = $name1;
                $prospectMessageContent['name2']                          = $name2;
                $prospectMessageContent['name3']                          = $name3;
                $prospectMessageContent['street']                         = $street1;
                $prospectMessageContent['postcode']                       = $postcode;
                $prospectMessageContent['city']                           = $city;
                $prospectMessageContent['country_id']                     = str_replace('COM', '', $countryID);
                $prospectMessageContent['homepage']                       = $homepage;
                if ($phone_company == '+') $phone_company = '';
                if (strlen($phone_company) > 0 && strlen($phone_company) < 7) {
                    if ($phone) {
                        $prospectMessageContent['telephone_company'] = $this->formatPhonenumberMQ($phone);
                    }
                } else {
                    $prospectMessageContent['telephone_company'] = $this->formatPhonenumberMQ($phone_company);
                }
                $prospectMessageContent['newsletter'] = $newsletter;

                $this->extraLog(__LINE__,__METHOD__,"before sending prospect message");

                $wwsCustomerId = $this->getQuote()->getSchrackWwsCustomerId();
                $realWwsCustomerId = true;
                if ($wwsCustomerId == '' || $wwsCustomerId == null || $wwsCustomerId == $countrySpecificPseudoCustomerID) {
                    $realWwsCustomerId = false;
                }
                if ($realWwsCustomerId == true) {
                    $err_msg  = date('Y-m-d H:i:s');
                    $err_msg .= ': New Prospect Order Checkout -> INAVLID_DATA: ';
                    $err_msg .= $this->getQuote()->getSchrackWwsOrderNumber() . ' (= WWSOrderID) -- ' . $email;

                    Mage::log($err_msg, null, '/prospects/prospect_err.log');
                } else {
                    $prospect = Mage::getSingleton('crm/connector')->putProspect($prospectMessageContent);
                    $this->extraLog(__LINE__,__METHOD__,"after sending prospect message");

                    $prospectMessageContent['DEV-HINT'] = date('Y-m-d H:i:s') . ' NEW_PROSPECT (Checkout)';
                }
                Mage::log($prospectMessageContent, null, '/prospects/prospects_reg.log');
            } else {
                $this->extraLog(__LINE__,__METHOD__,"in else ( newProspect )");
            }

            if ($this->getQuote()->getSchrackCustomertype() == 'guest') {
                $this->extraLog(__LINE__,__METHOD__,"in if ( guest )");
                if ($email == '' || $email == 'undefined') {
                    $this->extraLog(__LINE__,__METHOD__,"in if ( no email )");
                    Mage::log(date('Y-m-d H:i:s') . ': Guest Order Checkout -> INAVLID_EMAIL: ' . $email, null, '/prospects/prospect_err.log');
                    // Send warning Mail to Developer:
                    $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                    $mailText = 'COUNTRY = ' . $country . ' >>>  WWS-Order-Number = ' . $this->getQuote()->getSchrackWwsOrderNumber() . ' Guest Order Checkout';
                    if (Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails')) {
                        $zendMail = new Zend_Mail('utf-8');
                        $zendMail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
                            ->setSubject('Guest Order Checkout -> INAVLID_EMAIL -> ' . $this->getQuote()->getSchrackWwsOrderNumber())
                            ->setBodyHtml($mailText)
                            ->addTo(Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'))
                            ->send();
                    }
                    $this->extraLog(__LINE__,__METHOD__,"Exception: Guest Order Checkout -> INAVLID_EMAIL");
                    Mage::throwException(date('Y-m-d H:i:s') . ': Guest Order Checkout -> INAVLID_EMAIL: ' . $email);
                } else {
                    $this->extraLog(__LINE__,__METHOD__,"in else ( no email )");
                }
            }
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( newProspect || (guest && specialRouteForGuest) )");
        }

        if ( in_array($this->getQuote()->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect'))
            || ($this->getQuote()->getSchrackCustomertype() == 'guest'
                && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 1) ) {
            $this->extraLog(__LINE__,__METHOD__,"in if ( prospect || (guest && specialRouteForGuest) )");

            $specialRuoteForGuestDescription = '';
            if (($this->getQuote()->getSchrackCustomertype() == 'guest'
                && intval(Mage::getStoreConfig('schrack/new_self_registration/specialRouteForGuest')) == 1)) {
                $specialRuoteForGuestDescription = $this->_helper->__('Special Guest-Order');
            }

            // 1. Send mail to customer support:
            $mail = new Zend_Mail('utf-8');
            $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
                ->setSubject($mailSubject . $specialRuoteForGuestDescription)
                ->setBodyHtml($mailText);

            // Send mail schrack support employee(s):
            $checkoutEmailDestinationProspects = Mage::getStoreConfig('schrack/new_self_registration/checkoutEmailDestinationProspects');
            if ($checkoutEmailDestinationProspects) {
                $this->extraLog(__LINE__,__METHOD__,"in if ( checkoutEmailDestinationProspects )");
                if (stristr($checkoutEmailDestinationProspects, ';')) {
                    $this->extraLog(__LINE__,__METHOD__,"in if ( multiple checkoutEmailDestinationProspects )");
                    // Send mail to multiple recipients, if seperated by semicolon:
                    $emailRecipients = explode(';', preg_replace('/\s+/', '', $checkoutEmailDestinationProspects));
                    foreach ($emailRecipients as $index => $emailRecipient) {
                        $mail->addTo($emailRecipient);
                    }
                } else {
                    $mail->addTo($checkoutEmailDestinationProspects);
                }

                if ($email == '' || stristr($email, 'undefined')) {
                    if ($customerData && isset($customerData['email']) && $customerData['email'] != 'undefined') {
                        $email = $customerData['email'];
                    } else {
                        $customer = $this->getQuote()->getCustomer();
                        if (is_object($customer) && $customer->getEmail()) {
                            $email = $customer->getEmail();
                        }
                    }
                }

                Mage::log('Email #2 for Order = ' . $this->getQuote()->getSchrackWwsOrderNumber() . ' should be send on ' . date('Y-m-d H:i:s') . ' to ' . $email, null, '/prospects/prospect_mail_send.log');
                Mage::log($mailText, null, '/prospects/prospect_mail_send.log');
                if (stristr($mailText,': Array')) {
                    Mage::log('Error #2 -> ' . $mailText , null, 'street_as_array.log');
                    die('Street as Array');
                }
                $mail->send();
                Mage::log('Email send verified #2 on ' . date('Y-m-d H:i:s') . ' to ' . $email, null, '/prospects/prospect_mail_send.log');
            } else {
                $this->extraLog(__LINE__,__METHOD__,"in else ( checkoutEmailDestinationProspects )");
            }

            // Get customer email from existing data, or from freshly entered (form):
            if (is_object($customer) && $customer->getEmail()) {
                $receiverEmail = $customer->getEmail();
            } else {
                $receiverEmail = $email;
            }

            /// --------
            // $emails = array_values((array)$email);

            // DEVELOPER-EMAIL:
            // Check and re-map all eMails to change the recipient in certain cases:
            if (preg_match('/testuser[0-9]{0,3}_.{2}@schrack.com$/', $receiverEmail)) {
                $receiverEmail = Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails');
            }

            // 2. Send transactional mail to customer (all cases the same email content):
            if (is_object($customer) && $customer->getEmail()) {
                $this->extraLog(__LINE__,__METHOD__,"in if ( stored customer exists )");
                $storeId       = $customer->getStoreId();
                $receiverName  = $customer->getName();
                $transactionalMailVars = array('customer' => $customer, 'orderNumber' => $this->getQuote()->getSchrackWwsOrderNumber(), 'back_url' => '');
                Mage::log(date('Y-m-d H:i:s') . ': notifyOrderProspectEmail: mail prepare sent to EMAIL-ADDRESS = <<' . $receiverEmail . '>> -> ' . $this->getQuote()->getSchrackWwsOrderNumber(), null, '/prospects/prospects.log');
            } else {
                $this->extraLog(__LINE__,__METHOD__,"in else ( stored customer exists )");
                $customer      = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getWebsite()->getId());
                $storeId       = 1;
                $receiverName  = $genderArray[$gender] . ' ' . $firstname . ' ' . $lastname;
                $customer->setFirstname($genderArray[$gender] . ' ' . $firstname);
                $customer->setLastname($lastname);
                $transactionalMailVars = array('customer' => $customer, 'orderNumber' => $this->getQuote()->getSchrackWwsOrderNumber(), 'back_url' => '');
                Mage::log(date('Y-m-d H:i:s') . ': notifyOrderProspectEmail: mail prepare sent to EMAIL-ADDRESS = <<' . $receiverEmail . '>> (no customer object available) -> ' . $this->getQuote()->getSchrackWwsOrderNumber(), null, '/prospects/prospects.log');
            }

            $xmlPath = 'schrack/customer/notifyOrderProspectEmailId';
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
            $singleMailApi->setMagentoTransactionalTemplateVariables($transactionalMailVars);
            $singleMailApi->addToEmailAddress($receiverEmail,$receiverName);
            $singleMailApi->setFromEmail('general');
            $singleMailApi->createAndSendMail();

            Mage::log(date('Y-m-d H:i:s') . ': notifyOrderProspectEmail: mail successfully sent to EMAIL-ADDRESS = <<' . $receiverEmail . '>> -> ' . $this->getQuote()->getSchrackWwsOrderNumber(), null, '/prospects/prospects.log');
        } else {
            $this->extraLog(__LINE__,__METHOD__,"in else ( prospect || (guest && specialRouteForGuest) )");
        }

        if ($this->getQuote()->getSchrackCustomertype() == 'guest') {
            // Mail will be send from WWS (direct AB from WWS):
        }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///// ------- Mail Section for Prospects (end) ---------- ////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		/* SCHRACKLIVE/mk@plan2.net start */
		$payment = $this->getQuote()->getPayment(); // method sometimes is lost and throws an error in getOrderPlaceRedirectUrl()
		$redirectUrl = $payment->getMethod() ? $payment->getOrderPlaceRedirectUrl() : '';

        $this->extraLog(__LINE__,__METHOD__,"payment->getMethod() = " . $payment->getMethod());
        $this->extraLog(__LINE__,__METHOD__,"redirectUrl = $redirectUrl");

        $this->extraLog(__LINE__,__METHOD__,"before potential ship_order request");
		Mage::dispatchEvent('schrack_checkout_type_onepage_save_order_before', array('checkout' => $this->getCheckout(), 'quote' => $this->getQuote(), 'redirectUrl' => $redirectUrl));
        $this->extraLog(__LINE__,__METHOD__,"after potential ship_order request");

        // Notify Kurt Potzmann in all productive countries, where order was placed by some kind of test user:
        if ( Mage::getStoreConfig('schrack/general/platform') == 'LIVE' ) {
            $customerEmail = $this->getQuote()->getCustomer()->getEmail();
            $atPos = strpos($customerEmail, '@');
            $customerEmailDomain = substr($customerEmail, $atPos + 1);
            if ($customerEmailDomain == 'twaroch.at' || $customerEmailDomain == 'testversand.at') {
                $orderNumber = $this->getQuote()->getSchrackWwsOrderNumber();
                $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                $msg = "Test-Auftrag, Auftragsnummer: " . $orderNumber . " / LAND = " . $country . " - Bitte im WWS löschen!";
                $to = 'helpdesk@schrack.com';
                $mail = new Zend_Mail('utf-8');
                $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
                    ->setSubject($msg)
                    ->setBodyHtml($msg)
                    ->addTo($to)
                    ->send();
                Mage::log("Mail to $to sent: $msg", null, 'testorders.log');
            }
        }

		if ($this->getCheckout()->getRedirectUrl()) {
            $this->extraLog(__LINE__,__METHOD__,"return;");
			return $this; // don't save order (assume redirect goes to an error page)
		}
		/* SCHRACKLIVE end */

		$service = Mage::getModel('sales/service_quote', $this->getQuote());
		$service->submitAll();

		if ($isNewCustomer) {
			try {
				$this->_involveNewCustomer();
			} catch (Exception $e) {
                $this->extraLog(__LINE__,__METHOD__,"Exception: " . $e->getMessage());
				Mage::logException($e);
			}
		}

		$this->_checkoutSession->setLastQuoteId($this->getQuote()->getId())
				->setLastSuccessQuoteId($this->getQuote()->getId())
				->clearHelperData();

		$order = $service->getOrder();

        Mage::dispatchEvent('schrack_checkout_type_onepage_save_order_preafter', array('order' => $order, 'quote' => $this->getQuote()));

		if ($order) {
            $this->extraLog(__LINE__,__METHOD__,"order number  = " . $order->getSchrackWwsOrderNumber());
			Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order' => $order, 'quote' => $this->getQuote()));

			/**
			 * a flag to set that there will be redirect to third party after confirmation
			 * eg: paypal standard ipn
			 */
			$redirectUrl = $this->getQuote()->getPayment()->getOrderPlaceRedirectUrl();
			/**
			 * we only want to send to customer about new order when there is no redirect to third party
			 */
			if (!$redirectUrl && $order->getCanSendNewEmailFlag()) {
				try {
					if ( !in_array($this->getQuote()->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect', 'guest')) ) {
						$order->sendNewOrderEmail();
					}
				} catch (Exception $e) {
                    $this->extraLog(__LINE__,__METHOD__,"Exception: " . $e->getMessage());
					Mage::logException($e);
				}
			}

			// add order information to the session
			$this->_checkoutSession->setLastOrderId($order->getId())
					->setRedirectUrl($redirectUrl)
					->setLastRealOrderId($order->getIncrementId());

			// as well a billing agreement can be created
			$agreement = $order->getPayment()->getBillingAgreement();
			if ($agreement) {
				$this->_checkoutSession->setLastBillingAgreementId($agreement->getId());
			}
		}

		// add recurring profiles information to the session
		$profiles = $service->getRecurringPaymentProfiles();
		if ($profiles) {
			$ids = array();
			foreach ($profiles as $profile) {
				$ids[] = $profile->getId();
			}
			$this->_checkoutSession->setLastRecurringProfileIds($ids);
			// TODO: send recurring profile emails
		}

		Mage::dispatchEvent(
				'checkout_submit_all_after', array('order' => $order, 'quote' => $this->getQuote(), 'recurring_profiles' => $profiles)
		);

        $this->extraLog(__LINE__,__METHOD__,"end of method");
		return $this;
	}
    
    /**
	 * Save checkout shipping address
	 *
	 * @param   array $data
	 * @return  Schracklive_SchrackCheckout_Model_Type_Onepage
	 */
	public function saveAddress($data) {
		if (empty($data)) {
			return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
    }

	public function formatPhonenumberMQ ($phonenumber) {
        $phoneFormattedNumber = '';

		if ($phonenumber) {
            $phoneFormattedNumber = str_replace(array(' ', '-', '/'), '', $phonenumber);
		} else {
			$phoneFormattedNumber = '';
		}

		return $phoneFormattedNumber;
	}

	private $doExtraLog;
    private function extraLog ( $line, $method, $text ) {
	    if ( ! isset($this->doExtraLog) ) {
	        $this->doExtraLog = (Mage::getStoreConfig('schrack/checkout/extralog') == '1');
        }
        if ( $this->doExtraLog ) {
	        $line = sprintf('%04d', $line);
            $session = Mage::getSingleton('core/session');
            $SID = $session->getEncryptedSessionId(); //current session id
	        Mage::log("$SID | $method : $line   $text",null,'checkout_extra.log');
        }
    }

}

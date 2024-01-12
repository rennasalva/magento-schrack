<?php

require_once('app/code/core/Mage/Checkout/controllers/OnepageController.php');
require_once('app/code/local/Orcamultimedia/Sapoci/controllers/OnepageController.php');

class Schracklive_SchrackCheckout_OnepageController extends Orcamultimedia_Sapoci_OnepageController {

    public function indexAction(){
        $custSession = Mage::getSingleton('customer/session');
        if ( $custSession->isLoggedIn() ) {
            $customer = $custSession->getCustomer();
            // Check if customer may perform checkout or see prices !! Exception RU Price on request, but still may perform checkout
            if ( !Mage::helper('schrack/acl')->mayCheckout($customer) || !Mage::helper('geoip')->maySeePrices()) {
                if ($customer->getSchrackAclRole() != 'list_price_customer') {
                    http_response_code(401);
                    exit('Not authorized');
                }
            }
            $customerDB = Mage::getModel('customer/customer')->load($customer->getId());
            $dbEmail = $customerDB->getEmail();
            if (   $customer->getEmail() !== $dbEmail
                || (substr($dbEmail,0,8) === 'inactive' && substr($dbEmail,-17) === '@live.schrack.com')
                /* || substr(Mage::helper('wws')->getWwsCustomerId($customer),0,4) === 'TYP=' */ ) {
                Mage::log('Invalid account ' . $customerDB->getEmail() . '/' . Mage::helper('wws')->getWwsCustomerId($customer) . ' tried to checkout and was logged off.');
                Mage::getSingleton('customer/session')->setCustomer(Mage::getModel('customer/customer'))->setId(null);
                Mage::getSingleton('core/session')->addError($this->__("An error occured with your account while trying to order. Please contact your advisor. You are logged-off now."));
                return $this->_redirect('/');
            }
            // Try to find out, if a wws-order-id was previously saved to order in connection with credit card payment (customweb-extension).
            // In case of previously initiated credit card payment, just delete the wws_order_id at the order, for get a new one:
            $schrack_wws_order_number = Mage::getModel('checkout/cart')->getQuote()->getData('schrack_wws_order_number'); // 350000923

            // Do we have a previously created order recordset from current quote?:
            $orderId = Mage::getModel('sales/order')->load($this->getOnepage()
                                                                ->getQuote()->getId(), 'quote_id')
                                                    ->getId();
            if ($orderId > 0) {
                $order = $this->_getOrder();
                if ($order && intval($schrack_wws_order_number) > 0) {

                    // Check, if this was originally an credit card payment:
                    $isCCPayment = stristr($order->getPayment()->getMethodInstance()->getCode(), 'payunitycw');
                    $isPayPalPayment = stristr($order->getPayment()->getMethodInstance()->getCode(), 'paypal');
                    if ($isCCPayment || $isPayPalPayment) {
                        $quote = Mage::getModel('checkout/cart')->getQuote();
                        // Deleting wws order number:
                        $quote->setSchrackWwsOrderNumber('');
                        $quote->save();

                        // Creating new empty quote:
                        $newQuote = Mage::getModel('sales/quote')->assignCustomer(Mage::getSingleton('customer/session')->getCustomer())
                                                                 ->setStoreId(Mage::app()->getStore()->getId());

                        // Cloning old data from old quote to new quote:
                        $newQuote->merge($quote);
                        $newQuote->collectTotals()
                                 ->save();

                        // Set old quote inactive:
                        $quote->setIsActive(0);
                        $quote->save();

                        // Assign new qoute to current qoute for the current customer:
                        Mage::getSingleton('customer/session')->setQuoteId($newQuote->getId());
                    }
                }
            }
        }
        parent::indexAction();
    }


	/**
	 * Get payment method step html
	 *
	 * @return string
	 */
	protected function _getPaymentMethodsHtml() {
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_paymentmethod');
		$layout->generateXml();
		$layout->generateBlocks();

		/* START Schracklive */
		$messages = $this->getOnepage()->getCheckout()->getMessages(true);
		$beforeItemsBlock = $this->getLayout()->getBlock('checkout.onepage.payment.before');
		$beforeItemsBlock->append($beforeItemsBlock->getMessagesBlock()->setMessages($messages));
		/* END Schracklive */

		$output = $layout->getOutput();
		return $output;
	}

	/**
	 * Get order review step html
	 *
	 * @see savePaymentAction()
	 * @return string
	 */
	protected function _getReviewHtml() {
		/* START Schracklive */
		$this->loadLayout('checkout_onepage_review'); // mk@plan2.net: moved from _savePaymentAction()

		$messages = $this->getOnepage()->getCheckout()->getMessages(true);
		$beforeItemsBlock = $this->getLayout()->getBlock('checkout.onepage.review.info.items.before');
		$beforeItemsBlock->append($beforeItemsBlock->getMessagesBlock()->setMessages($messages));
		/* END Schracklive */

		return $this->getLayout()->getBlock('root')->toHtml();
	}


    /**
     * save checkout billing address
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
// $postData = $this->getRequest()->getPost('billing', array());
// $data = $this->_filterPostData($postData);
            $data = $this->getRequest()->getPost('billing', array());
            Mage::log(__FILE__ . ':' . __LINE__,null,'count_address_modifications.log');
            $dataTemp = array();
            foreach($data as $key => $value) {
                if (is_array($value) && $key == 'street') {
                    if (stristr($value[0], 'undefined')) {
                        Mage::log('Value from Key = ' . $key . ' is "undefined"', null, 'undefined_save_billing.log');
                        return;
                    } else {
                        $value[0] = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value[0]);
                        $dataTemp[$key] = $value[0];
                    }
                } else {
                    if (is_string($value)) {
                        if (stristr($value, 'undefined')) {
                            Mage::log('Value from Key = ' . $key . ' is "undefined"', null, 'undefined_save_billing.log');
                            return;
                        } else {
                            $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                            $dataTemp[$key] = $value;
                        }
                    }

                    if (is_array($value)) {
                        if (isset($value[0])) {
                            if (stristr($value[0], 'undefined')) {
                                Mage::log('Value from Key = ' . $key . ' is "undefined"', null, 'undefined_save_billing.log');
                                return;
                            } else {
                                $value[0] = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value[0]);
                                $dataTemp[$key] = $value[0];
                            }
                        }
                    }
                }
            }

            $data = $dataTemp;

            // Identification of user from frontend (not reliable, but a first entry point for the entire process):
            if (array_key_exists('customer_type', $data) && $data['customer_type'] != 'normal') {
                if (in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
                    $this->getOnepage()->getQuote()->setSchrackCustomertype($data['customer_type']);
                    $this->getOnepage()->getQuote()->save();
                }
            } else {
                $this->getOnepage()->getQuote()->setSchrackCustomertype('normal');
                $this->getOnepage()->getQuote()->save();
            }

            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            if (isset($data['company']) && $data['company'] && !$customerAddressId) {
                $data['middlename'] = $data['company'];
            }
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }

            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                /* check quote for virtual */
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $pw = $data['password'];
            if ( is_string($pw) && trim($pw) > '' ) {
                Mage::helper('schrackcustomer')->rememberPasswordHash($data['email'],$pw);
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        if ($this->getRequest()->isPost()) {
            $ajaxPostRequest = $this->getRequest()->getPost();
            if ($ajaxPostRequest && isset($ajaxPostRequest['saveShippingMethodByDirectAjax']) && $ajaxPostRequest['saveShippingMethodByDirectAjax']== true) {
                $data = array();
                $data['customer_type']                  = $ajaxPostRequest['shipping_method_customer_type'];
                $data['type']                           = $ajaxPostRequest['shipping_method_type'];
                $data['saveShippingMethodByDirectAjax'] = true;
                $dataTemp = array();
                foreach($data as $key => $value) {
                    $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                    $dataTemp[$key] = $value;
                }
                $data = array();
                $data = $dataTemp;

                // DESTINATION: app/code/local/Schracklive/SchrackCheckout/Model/Type/Onepage.php:
                $result = $this->getOnepage()->saveShippingMethod($data);

                return 'true';
            } else {
                $data = $this->getRequest()->getPost('shipping_method', 'TRY');

                if (array_key_exists('customer_type', $data) && $data['customer_type'] != 'oldLightProspect' && !array_key_exists('type', $data)) {
                    foreach(Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingRatesCollection() as $rate)
                    {
                       $availableRates[] = $rate->getCode();

                    }
                    $data['type'] = $availableRates[0];
                }


                // app/code/local/Schracklive/SchrackCheckout/Model/Type/Onepage.php:
                $result = $this->getOnepage()->saveShippingMethod($data);

                // $result will contain error data if shipping method is empty
                if (!isset($result['error'])) {
                    if (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                        $result['goto_section'] = 'shipping_method';
                        $result['update_section'] = array(
                            'name' => 'shipping-method',
                            'html' => $this->_getShippingMethodsHtml()
                        );

                        $result['allow_sections'] = array('shipping');
                        $result['duplicateBillingInfo'] = 'true';
                    } else {
                        $result['goto_section'] = 'shipping';
                    }
                }
                $this->_prepareDataJSON($result);
            }
        }
    }

    public function saveShippingAction() {
        $this->getOnepage()->prepareShippingMethod();

        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            if ( $data['new_address'] == 'yes' ) {
                Mage::log(__FILE__ . ':' . __LINE__,null,'count_address_modifications.log');
            }

            $dataTemp = array();
            if (Mage::helper('ids')->isIdsSession()) {
                $idsAddressData = array();
                $street = '';

                Mage::log($data, null, "ids.log");
                if (isset($data['new_address']) && $data['new_address'] == 'yes') {
                    if (isset($data['street'])) {
                        if (is_array($data['street'])) {
                            $street = $data['street'][0];
                        }
                    }
                    if ($street) {
                        $idsAddressData['street'] = $street;
                    }
                    if ($data['postcode']) {
                        $idsAddressData['postcode'] = $data['postcode'];
                    }
                    if ($data['postcode']) {
                        $idsAddressData['city'] = $data['street'];
                    }
                    if (Mage::helper('ids')->getCountry($data['country_id'])) {
                        $idsAddressData['country'] = Mage::helper('ids')->getCountry($data['country_id']);
                    }
                    if ($data['street']) {
                        $idsAddressData['contact_person'] = $data['street'];
                    }
                    if ($data['phone_address_contact']) {
                        $idsAddressData['contact_phone'] = $data['phone_address_contact'];
                    }
                    if ($data['lastname']) {
                        $idsAddressData['company_name'] = $data['lastname'];
                    }
                    if ($data['company']) {
                        $idsAddressData['company_name2'] = $data['company'];
                    }
                }

                if (isset($data['new_address']) && $data['new_address'] == 'no') {
                    if (isset($data['old_address_id']) && !empty($data['old_address_id'])) {
                        $addressData = Mage::getModel('customer/address')->load($data['old_address_id']);
                        $streetRaw = $street = $addressData->getStreet();
                        if (is_array($streetRaw)) {
                            $street = $streetRaw[0];
                        }
                        if ($street) {
                            $idsAddressData['street'] = $street;
                        }
                        if ($addressData->getPostcode()) {
                            $idsAddressData['postcode'] = $addressData->getPostcode();
                        }
                        if ($addressData->getCity()) {
                            $idsAddressData['city'] = $addressData->getCity();
                        }
                        if ($addressData->getCountry()) {
                            $idsAddressData['country'] = $addressData->getCountry();
                        }
                        if ($addressData->getName3()) {
                            $idsAddressData['contact_person'] = $addressData->getName3();
                        }
                        if ($this->getOnepage()->getQuote()->getSchrackAddressPhone()) {
                            $idsAddressData['contact_phone']  = $this->getOnepage()->getQuote()->getSchrackAddressPhone();
                        }
                        if ($addressData->getLastname()) {
                            $idsAddressData['company_name'] = $addressData->getLastname();
                        }
                        if ($addressData->getCompany()) {
                            $idsAddressData['company_name2'] = $addressData->getCompany();
                        }
                    }
                }
                Mage::log($idsAddressData, null, "ids.log");

                // Writes IDS datd to IDS table:
                if (is_array($idsAddressData) && !empty($idsAddressData)) {
                    $arrStructure = array();
                    $session      = Mage::getSingleton('customer/session');
                    $email        = $session->getCustomer()->getEmail();
                    $arrStructure['DeliveryPlaceInfo'] = $idsAddressData;

                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $query  = "UPDATE schrack_ids_data SET delivery_address = '" . serialize($arrStructure) . "'";
                    $query .= " WHERE email LIKE '" . $email . "' and active = 1";
                    $writeConnection->query($query);
                }

                if (isset($data['shipping_method'])) {
                    if ($data['shipping_method'] == 'pickup') {
                        $shippingMode = 'Abholung';
                    } else if ($data['shipping_method'] == 'delivery') {
                        $shippingMode = 'Lieferung';
                    } else if ($data['shipping_method'] == 'container') {
                        $shippingMode = 'Container';
                    } else if ($data['shipping_method'] == 'inpost') {
                        $shippingMode = 'Inpost';
                    }
                    $email = $session->getCustomer()->getEmail();
                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $query  = "UPDATE schrack_ids_data SET selected_shipping = '" . $shippingMode . "'";
                    $query .= " WHERE email LIKE '" . $email . "' and active = 1";
                    $writeConnection->query($query);
                }
            }

            foreach($data as $key => $value) {
                if (is_array($value) && $key == 'street') {
                    if (stristr($value[0], 'undefined')) {
                        Mage::log('Value from Key = ' . $key . ' is "undefined"', null, 'undefined_save_billing.log');
                        return;
                    } else {
                        $value[0] = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value[0]);
                        $dataTemp[$key] = $value;
                    }
                } else {
                    if (stristr($value, 'undefined')) {
                        Mage::log('Value from Key = ' . $key . ' is "undefined"', null, 'undefined_save_shipping.log');
                        return;
                    } else {
                        $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                        $dataTemp[$key] = $value;
                    }
                }
            }
            $data = array();
            $data = $dataTemp;

            Mage::unregister('pickup_location');
            if (isset($data['pickup_location']) && stristr($data['pickup_location'],'pickup')) {
                Mage::register('pickup_location', $data['pickup_location']);
            } else {
                Mage::register('pickup_location', '');
            }

            // Identification of user from frontend (not reliable, but a first entry point for the entire process):
            if (array_key_exists('customer_type', $data) && $data['customer_type'] != 'normal') {
                if (in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
                    $this->getOnepage()->getQuote()->setSchrackCustomertype($data['customer_type']);
                    $this->getOnepage()->getQuote()->save();

                    if ($data['shipping_method'] == 'pickup' && $data['lastname'] == '') {
                        $data = $this->getOnepage()->getQuote()->getBillingAddress()->getData();
                    }
                }
            } else {
                $this->getOnepage()->getQuote()->setSchrackCustomertype('normal');
                $this->getOnepage()->getQuote()->save();
            }

            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            if (isset($data['company']) && $data['company'] && !$customerAddressId) {
                $data['middlename'] = $data['company'];
            }

            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
            $quoteShippingAddr = $this->getOnepage()->getQuote()->getShippingAddress();

            $quoteShippingAddr->setSchrackIsCustomAddr(empty($customerAddressId) ? 1 : 0);

            $quoteShippingAddr->save();

            $this->_saveAddress($result);
            if (!isset($result['error'])) {
                if ( Mage::helper('geoip')->mayPerformCheckout() ) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } else {
                    $result['goto_section'] = 'review';
                    $result['update_section'] = array(
                        'name' => 'review',
                        'html' => $this->_getReviewHtml()
                    );
                }
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

            $loggedInCheck =  Mage::getSingleton('customer/session')->isLoggedIn();
            $inpostWarehouseId = Mage::getStoreConfig('carriers/schrackinpost/id');
            $containerWarehouseId = Mage::getStoreConfig('carriers/schrackcontainer/id');
            $schrackWarehouse ='';

            $quote = $this->getOnepage()->getQuote();
            $params = $this->getRequest()->getParams();
            $paramsId = '';

            // forwarding inpost to Inserupdateorder.php...
            $inpostPhone = Mage::getSingleton('customer/session');

            // inpost -> getting inputed data, phone number
            $inpost_input = $this->getRequest()->getPost();
            # guest order inpost id
            $inpost_store_id = $inpost_input['inpost_store_id'] ? $inpost_input['inpost_store_id'] : "";

            if(isset($inpost_input['inpost_phone'])) {
                #$inpost_country_code = $inpost_input['inpost_country_code'];
                $inpost_prefix = $inpost_input['inpost_prefix'];
                $inpost_phone = $inpost_input['inpost_phone'];
                $inpost_phone_number = $inpost_prefix . $inpost_phone;

                // session
                $inpostPhone->setInpostPhoneNumber($inpost_phone_number);

                Mage::log("Country code second line : " . print_r($inpost_input, true) . " loggin Check: " . $loggedInCheck, null, "input.log");
            }

            Mage::log("Params: " . json_encode($params[phone_address_contact]), null,"output.log");

            if (isset($params['container_id']) && $params['container_id'] > '') {
                $paramsId = $params['container_id'];
                $schrackWarehouse = 'schrackcontainer_warehouse' . $containerWarehouseId;
                $quote->setSchrackWwsContainerId($paramsId);
                Mage::log("container: ", null,"output.log");
            }

            if ((isset($params['inpost_id']) && $params['inpost_id'] > '') || isset($inpost_store_id)) {
                $paramsId = $loggedInCheck ? $params['inpost_id'] : $inpost_store_id;
                $schrackWarehouse = 'schrackinpost_warehouse' . $inpostWarehouseId;
                $quote->setSchrackWwsInpostId($paramsId);
                #Mage::log("Inpost Id: " . $paramsId . " post -> " . $inpost_store_id, null,"inpost_id.log");
            }


            if ( $paramsId && $paramsId > '' && $paramsId !== 'undefined' ) {
                $address = $quote->getShippingAddress();
                $address->setShippingMethod($schrackWarehouse);
                $quote->save();
            }

        }
    }
    
    /**
     * Refreshes the previous step
     * Loads the block corresponding to the current step and sets it
     * in to the response body
     *
     * This function is called from the reloadProgessBlock
     * function from the javascript
     *
     * @return string|null
     */
    public function progressAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $this->loadLayout(false);
        $this->renderLayout();
    }
	/**
	 * Save payment ajax action
	 *
	 * Sets either redirect or a JSON response
	 */
	public function savePaymentAction() {
		if ($this->_expireAjax()) {
			return;
		}

        $vatIdentificationNumber = '';
        $boolVatLocalNumber      = 0;
        $vatLocalNumber          = '';

		try {
            $customerIsLoggedIn = false;
            $customerType = '';

			if (!$this->getRequest()->isPost()) {
				$this->_ajaxRedirectResponse();
				return;
			}

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());

            $schrackWwsOrderId = $this->getOnepage()->getQuote()->getSchrackWwsOrderNumber();
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            if ($schrackWwsOrderId) {
                // Prevents double ship_order commands to WWS:
                $query  = "SELECT * FROM wws_ship_order_request";
                $query .= " WHERE wws_order_id LIKE '" . $schrackWwsOrderId . "'";

                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    // Reset the customers quote, concerning WWS-Order-ID (this will cause a new fetch of WWS-Order-ID to current quote):
                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $query  = "UPDATE sales_flat_quote SET schrack_wws_order_number = '' WHERE schrack_wws_order_number LIKE '" . $schrackWwsOrderId . "'";
                    $writeConnection->query($query);
                    //$result['error'] = date('Y-m-d H:i:s') . ' ERROR (savePaymentAction) -> Already sent Ship_Order to WWS -> WWS-Order: ' . $schrackWwsOrderId;

                    // If SQL-Request found a previously ship_order, just fire some error:
                    Mage::log(date('Y-m-d H:i:s') . ' ERROR (savePaymentAction) -> Already sent Ship_Order to WWS -> WWS-Order: ' . $schrackWwsOrderId, null, '/payment/shipment_err.log');
                    throw new Exception('WWS-Order: ' . $schrackWwsOrderId . ' : ' . date('Y-m-d H:i:s') . ': ERROR (savePaymentAction) -> Already sent Ship_Order to WWS');
                    return;
                }
            }

            $dataTemp = array();
            foreach($data as $key => $value) {
                $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                $dataTemp[$key] = $value;
            }
            $data = array();
            $data = $dataTemp;

            $shippingRateCode = $this->getOnepage()->getQuote()->getShippingAddress()->getShippingMethod();
            Mage::unregister('pickup_location');
            if (stristr($shippingRateCode, 'pickup')) {
                Mage::register('pickup_location', $shippingRateCode);
            } else {
                Mage::register('pickup_location', '');
            }

            // Set street (billing + shipping)
            $street1 = '';
            if (array_key_exists('street1', $data)) {
                $street1 = $data['street1'];   // --> Source: localStorage.
            }
            if ($street1) {
                $shippingAddress = $this->getOnepage()->getQuote()->getShippingAddress();
                $shippingAddress->setStreet($street1);
                $shippingAddress->collectTotals()->save();

                if (in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
                    $billingAddress = $this->getOnepage()->getQuote()->getBillingAddress();
                    $billingAddress->setStreet($street1);
                    $billingAddress->collectTotals()->save();
                }

                $this->getOnepage()->getQuote()->setCustomerNote($street1);
                $this->getOnepage()->getQuote()->save();
            }

            // Identification of user from frontend (not reliable, but a first entry point for the entire process):
            if (array_key_exists('customer_type', $data) && $data['customer_type'] != 'normal') {
                if (in_array($data['customer_type'], array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
                    $this->getOnepage()->getQuote()->setSchrackCustomertype($data['customer_type']);
                    $this->getOnepage()->getQuote()->save();

                    if (array_key_exists('vat_number', $data)) {
                        // $data['vat_number'] may be local or normal UID:
                        $vatIdentificationNumber = $data['vat_number']; // --> Source: localStorage.newCheckoutProcessVATIdentificationNumber
                    }

                    if (array_key_exists('local_vat', $data)) {
                        $boolVatLocalNumber = $data['local_vat']; // --> Source: localStorage.newCheckoutLocalVAT
                    }

                    // Special case: former registered prospect:
                    if (in_array($data['customer_type'], array('oldFullProspect'))) {
                        $quoteCustomer = $this->getOnepage()->getQuote()->getCustomer();
                        if (is_object($quoteCustomer)) {
                            $account = Mage::getModel('account/account')->load($quoteCustomer->getSchrackAccountId(), 'account_id');
                            $vatIdentificationNumber = $account->getVatIdentificationNumber();
                            if ($account->getVatLocalNumber()) {
                                $boolVatLocalNumber = 1;
                                $vatLocalNumber = $account->getVatLocalNumber();
                            }
                        }
                    }

                    if ($vatIdentificationNumber) {
                        if ($vatLocalNumber == '') {
                            $vatLocalNumber = $vatIdentificationNumber;
                        }
                        // Removing whitespaces:
                        $vatIdentificationNumber = str_replace(' ', '', $vatIdentificationNumber);

                        // Set UID to quote for later use in insert-update-order (tt_order -> memo):
                        $schrackWwwsOrderMemo = $this->getOnepage()->getQuote()->getSchrackWwsOrderMemo();
                        if ($schrackWwwsOrderMemo) {
                            if (!stristr($schrackWwwsOrderMemo, 'UID=' . $vatIdentificationNumber)) {
                                // Check, if customer changed UID while proceeding order & fill cart:
                                if (stristr($schrackWwwsOrderMemo, 'UID=')) {
                                    // Replace formerly entered UID, with latest one:
                                    // More Parameter-assignments exisiting:
                                    if (stristr($schrackWwwsOrderMemo, ';')) {
                                        $parametersKeyValue = explode(';', $schrackWwwsOrderMemo);
                                        if (is_array($parametersKeyValue)) {
                                            foreach ($parametersKeyValue as $index => $valuesPair) {
                                                $valuesPair = trim($valuesPair);
                                                if (stristr($valuesPair, 'UID=')) {
                                                    $newSchrackWwwsOrderMemo = str_replace($valuesPair, 'UID=' . $vatIdentificationNumber, $schrackWwwsOrderMemo);
                                                    $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo($newSchrackWwwsOrderMemo);
                                                    $this->getOnepage()->getQuote()->save();
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        // UID is only parameter key (simply replace complete old content with new content):
                                        $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo('UID=' . $vatIdentificationNumber);
                                        $this->getOnepage()->getQuote()->save();
                                    }
                                } else {
                                    // Append UID as memo information:
                                    $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo($schrackWwwsOrderMemo . ';UID=' . $vatIdentificationNumber);
                                    $this->getOnepage()->getQuote()->save();
                                }
                            }
                        } else {
                            // Insert new order memo information:
                            $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo('UID=' . $vatIdentificationNumber);
                            $this->getOnepage()->getQuote()->save();
                        }
                    }

                    // VAT Local Number transmit seperately:
                    if ($boolVatLocalNumber == 1 && $vatLocalNumber) {
                        // Removing whitespaces:
                        $vatLocalNumber = str_replace(' ', '', $vatLocalNumber);

                        // Set UID to quote for later use in insert-update-order (tt_order -> memo):
                        $schrackWwwsOrderMemo = $this->getOnepage()->getQuote()->getSchrackWwsOrderMemo();
                        if ($schrackWwwsOrderMemo) {
                            if (!stristr($schrackWwwsOrderMemo, 'TAXID=' . $vatLocalNumber)) {
                                // Check, if customer changed UID while proceeding order & fill cart:
                                if (stristr($schrackWwwsOrderMemo, 'TAXID=')) {
                                    // Replace formerly entered UID, with latest one:
                                    // More Parameter-assignments exisiting:
                                    if (stristr($schrackWwwsOrderMemo, ';')) {
                                        $parametersKeyValue = explode(';', $schrackWwwsOrderMemo);
                                        if (is_array($parametersKeyValue)) {
                                            foreach ($parametersKeyValue as $index => $valuesPair) {
                                                $valuesPair = trim($valuesPair);
                                                if (stristr($valuesPair, 'TAXID=')) {
                                                    $newSchrackWwwsOrderMemo = str_replace($valuesPair, 'TAXID=' . $vatLocalNumber, $schrackWwwsOrderMemo);
                                                    $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo($newSchrackWwwsOrderMemo);
                                                    $this->getOnepage()->getQuote()->save();
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        // UID is only parameter key (simply replace complete old content with new content):
                                        $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo('TAXID=' . $vatLocalNumber);
                                        $this->getOnepage()->getQuote()->save();
                                    }
                                } else {
                                    // Append UID as memo information:
                                    $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo($schrackWwwsOrderMemo . ';TAXID=' . $vatLocalNumber);
                                    $this->getOnepage()->getQuote()->save();
                                }
                            }
                        } else {
                            // Insert new order memo information:
                            $this->getOnepage()->getQuote()->setSchrackWwsOrderMemo('TAXID=' . $vatLocalNumber);
                            $this->getOnepage()->getQuote()->save();
                        }
                    }
                }
            } else {
                $this->getOnepage()->getQuote()->setSchrackCustomertype('normal');
                $this->getOnepage()->getQuote()->save();
            }

            // DESTINATION: app/code/local/Schracklive/SchrackCheckout/Model/Type/Onepage.php:
            $result = $this->getOnepage()->savePayment($data);
            
            $loggedInCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
            if (isset($loggedInCustomer)) {
                $customerIsLoggedIn = true;
                $onepage = $this->getOnepage();
                $checkout = $onepage->getCheckout();
                $checkout->setLoggedInCustomer($loggedInCustomer);
            }

            if ($loggedInCustomer == false) {
                if ($this->getOnepage()->getQuote()->getCustomer() && $this->getOnepage()->getQuote()->getCustomer()->getId() > 0) {
                    $customerType = $this->getOnepage()->getQuote()->getCustomer()->getSchrackCustomerType();
                }
            }

            $billingAddress = $this->getOnepage()->getQuote()->getBillingAddress();

            if (!$billingAddress->getCity()) {
                $billingAddress = $this->getOnepage()->getQuote()->getBillingAddress();
                if ($this->getOnepage()->getQuote()->getShippingAddress()) {
                    $shippingAddressCity = $this->getOnepage()->getQuote()->getShippingAddress()->getCity();
                    //Mage::log($shippingAddressCity, null, 'test.log');
                    $billingAddress->setCity($shippingAddressCity);
                    $billingAddress->collectTotals()->save();;
                }
            }

			// get section and redirect data
			$redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();

			if (empty($result['error']) && !$redirectUrl) {

				// Schracklive/mk@plan2.net: added event and another fetching of the redirect url
				// DESTINATION: /app/code/local/Schracklive/Wws/Model/Checkout/Observer.php -> fillInOrderDetailsFromWws($observer)

				Mage::dispatchEvent('schrack_checkout_controller_onepage_save_payment', array('onepage' => $this->getOnepage()));

				$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();

				$this->getOnepage()->getCheckout()->setRedirectUrl('');

				if (!$redirectUrl) {
					// $this->loadLayout('checkout_onepage_review');	// mk@plan2.net: removed to _getReviewHtml()
					$result['goto_section'] = 'review';
					$result['update_section'] = array(
						'name' => 'review',
						'html' => $this->_getReviewHtml()
					);
				}
			}
			if ($redirectUrl) {
				$result['redirect'] = $redirectUrl;
			}
		} catch (Mage_Payment_Exception $e) {
			if ($e->getFields()) {
				$result['fields'] = $e->getFields();
			}
			$result['error'] = $e->getMessage();
		} catch (Schracklive_Schrack_Exception $e) {
			if ($e->mustBeLogged()) {
				Mage::logException($e);
			}
			$result['success'] = false;
			$result['error'] = $e->getMessage(); // the version of saveOrderAction() sets this to true, but this doesn't work here

			$this->_updateJsonResultFromCheckoutSession($result);
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
		} catch (Exception $e) {
			Mage::logException($e);

            $quoteCustomerType = $this->getOnepage()->getQuote()->getSchrackCustomertype();

			// Catching explicit in case new registration process:
			if ( (($customerType == 'full-prospect' || $customerType == 'light-prospect') && in_array($quoteCustomerType, array('oldFullProspect', 'oldLightProspect')))
			      || ($customerIsLoggedIn == false && in_array($quoteCustomerType, array('newProspect', 'guest'))) ) {
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
			} else {
                // "Unable to set Payment Method." is just a simple phrase (in different cases of exception) for output to the user
                $result['error'] = $this->__('Unable to set Payment Method.');
            }
		}
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}


	/**
	 * Create order action
	 */
	public function saveOrderAction() {

        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*');
            return;
        } // Nagarro : Added
        if ($this->_expireAjax()) {
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        // Check, if PayPal Orders will be transerred in comparison to database -> last successful insert_update2_order:
        // Payment methods should be consistent between Shop and WWS:
        $payment = $this->getOnepage()->getQuote()->getPayment();
        $shopSavedPaymentMethodInQuote = $payment->getMethod();
        $schrackWwsOrderId = $this->getOnepage()->getQuote()->getSchrackWwsOrderNumber();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $query  = "SELECT payment_method_definition FROM wws_insert_update_order_request";
        $query .= " WHERE wws_order_id LIKE '" . $schrackWwsOrderId . "' AND response_fetched_successfully = 1";
        $query .= " ORDER BY request_datetime DESC LIMIT 1";

        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            // Previous paypal data found:
            foreach ($queryResult as $recordset) {
                if ($recordset['payment_method_definition'] != $shopSavedPaymentMethodInQuote) {
                    Mage::log(date('Y-m-d H:i:s') . ': Checkout ERROR -> Payment Method Mismatch [SHOP (sales_flat_quote) = ' . $shopSavedPaymentMethodInQuote . ' ---> WWS (insert_update_order) = ' . $recordset['payment_method_definition'] . ']  WWS-Order: ' . $schrackWwsOrderId, null, '/payment/paypal_err.log');
                    throw new Exception(date('Y-m-d H:i:s') . ': ERROR -> Payment Method Mismatch');
                    return;
                }
            }
        }

        if ( $this->_isAreadyPending() ) {
            // ###
            sleep(10); // wait 10 secs
            $this->getResponse()->setBody('{"success":true,"error":false}'); // then return OK without doing something
        }

        // Prevents double ship_order commands to WWS:
        $query  = "SELECT * FROM wws_ship_order_request";
        $query .= " WHERE wws_order_id LIKE '" . $schrackWwsOrderId . "'";

        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            // Reset the customers quote, concerning WWS-Order-ID (this will cause a new fetch of WWS-Order-ID to current quote):
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $query  = "UPDATE sales_flat_quote SET schrack_wws_order_number = '' WHERE schrack_wws_order_number LIKE '" . $schrackWwsOrderId . "'";
            $writeConnection->query($query);

            // If SQL-Request found a previously ship_order, just fire some error:            
            Mage::log(date('Y-m-d H:i:s') . ' ERROR (saveOrderAction) -> Already sent Ship_Order to WWS -> WWS-Order: ' . $schrackWwsOrderId, null, '/payment/shipment_err.log');
            throw new Exception('WWS-Order: ' . $schrackWwsOrderId . ' : ' . date('Y-m-d H:i:s') . ': ERROR (saveOrderAction) -> Already sent Ship_Order to WWS');
            return;
        }

        $orderPostData = $this->getRequest()->getPost('order', array());
        $quoteCustomerType = $this->getOnepage()->getQuote()->getSchrackCustomertype();

        // get email from raw input to avoid replacement of '+' with ' ' (see: https://bugs.php.net/bug.php?id=39078)
        // raw data content is like ...r%5D=1&order%5Bemail%5D=a+s@testversand.at&order%5Bfirs...
        $rawPostData = file_get_contents("php://input");
        $p = strpos($rawPostData,'order%5Bemail%5D=');
        if ( $p !== false ) {
            $p += 17;
            $q = strpos($rawPostData,'&order%5B',$p);
            if ( $q !== false ) {
                $email = substr($rawPostData, $p, $q - $p);
                if ( $email ) {
                    $orderPostData['email'] = $email;
                }
            }
        }

        // Set "guest" as checkout-method for new registration process: guest = non-registering-user
        if (in_array($quoteCustomerType, array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
            $this->getOnepage()->saveCheckoutMethod(Schracklive_SchrackCheckout_Model_Type_Onepage::METHOD_GUEST);
            $paymentPostData = $this->getRequest()->getPost('payment', array());
            if ($paymentPostData && isset($paymentPostData['method'])) {
                $orderPostData['payment_method'] = $paymentPostData['method'];
            }
        }

        $result = array();
        $geoipHelper = Mage::helper('geoip');
        if ($geoipHelper->mayPerformCheckout()) {
            $isOldCustomerWithUserTermsAlreadyConfirmed = false;
            $customerUserTermsConfirmed                 = false;
            $isOldCustomerWithDSGVOAlreadyConfirmed     = false;
            $customerDSGVOConfirmed                     = true;

            try {
                // $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
                $requiredAgreements = array("1");

                // Regelwerk, in welchem Fall, welche Checkboxen angezeigt werden sollen:
                // Bei Bestandskunden, die die DSGVO bereits bestätigt haben, soll nur die AGB angezeigt werden:
                if (in_array($quoteCustomerType, array('newProspect', 'guest'))) {
                    $isOldCustomerWithDSGVOAlreadyConfirmed = false;
                }
                if (in_array($quoteCustomerType, array('oldFullProspect', 'oldLightProspect'))) {
                    $isOldCustomerWithDSGVOAlreadyConfirmed = true;
                }
                $custSession = Mage::getSingleton('customer/session');
                if ( $custSession->isLoggedIn() ) {
                    $customer = $custSession->getCustomer();
                    if ($customer && $customer->getSchrackConfirmedDsgvo()) {
                        $isOldCustomerWithDSGVOAlreadyConfirmed = true;
                        $customerDSGVOConfirmed = true;
                    } else {
                        $isOldCustomerWithDSGVOAlreadyConfirmed = false;
                        $customerDSGVOConfirmed = false;
                    }

                    if ($customer && $customer->getSchrackLastTermsConfirmed() == 1) {
                        $isOldCustomerWithUserTermsAlreadyConfirmed = true;
                        $customerUserTermsConfirmed = true;
                    } else {
                        $isOldCustomerWithUserTermsAlreadyConfirmed = false;
                        $customerUserTermsConfirmed = false;
                    }
                }

                if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDataProtection')) == 1
                    && $isOldCustomerWithDSGVOAlreadyConfirmed == false) $requiredAgreements = array("1", "2");

                if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDSGVO')) == 1
                    && $isOldCustomerWithDSGVOAlreadyConfirmed == false) $requiredAgreements = array("1", "3");

                if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxUserTerms')) == 1
                    && $isOldCustomerWithUserTermsAlreadyConfirmed == false) $requiredAgreements = array("1", "4");

                if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDataProtection')) == 1
                    && intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDSGVO')) == 1
                    && $isOldCustomerWithDSGVOAlreadyConfirmed == false) $requiredAgreements = array("1", "2", "3");

                if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDataProtection')) == 1
                    && intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxUserTerms')) == 1
                    && $isOldCustomerWithDSGVOAlreadyConfirmed == false
                    && $isOldCustomerWithUserTermsAlreadyConfirmed == false) $requiredAgreements = array("1", "2", "4");

                if ($requiredAgreements) {
                    $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                    $diff = array_diff($requiredAgreements, $postedAgreements);
                    if ($diff) {
                        $result['success'] = false;
                        $result['error'] = true;
                        $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }

                    if ($quoteCustomerType == '') $quoteCustomerType = 'normal';
                    $rightsInformationNoticeRegistration = 'Checkout : ' . $quoteCustomerType;

                    $confirmationAGBCheckboxText = $this->__("Checkout Terms and Conditions Complete");

                    $email            = addslashes($this->getOnepage()->getQuote()->getCustomerEmail());
                    $confirmationText = $this->__("DSGVO Schrack Confirm Text");

                    if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDataProtection')) == 1 && $isOldCustomerWithDSGVOAlreadyConfirmed == false) {
                        $confirmationDataProtectionCheckboxText  = $this->__("Schrack DataProtection Checkbox Confirm Text");
                        $confirmationDataProtectionCheckboxValue = 1;
                    } else {
                        $confirmationDataProtectionCheckboxText  = '';
                        $confirmationDataProtectionCheckboxValue = 0;
                    }

                    if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDSGVO')) == 1 && $isOldCustomerWithDSGVOAlreadyConfirmed == false) {
                        $confirmationDSGVOCheckboxText  = $this->__("Schrack DSGVO Checkbox Confirm Text");
                        $confirmationDSGVOCheckboxValue = 1;
                    }else {
                        $confirmationDSGVOCheckboxText  = '';
                        $confirmationDSGVOCheckboxValue = 0;
                    }

                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                    $query  = "INSERT INTO customer_dsgvo SET email = '" . $email . "',";

                    if (intval(Mage::getStoreConfig('schrack/dsgvo/activateCheckoutCheckboxDSGVO')) == 1) {
                        $query .= " schrack_confirmed_dsgvo = " . $confirmationDSGVOCheckboxValue . ",";
                        $query .= " schrack_confirmed_dsgvo_confirm_text = '" . addslashes($confirmationText) . "',";
                        $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDSGVOCheckboxText) . "',";
                    } else {
                        // PHU-2021-05-27 - Change: now DSGVO was merged into general data protection declaration confirmation:
                        $query .= " schrack_confirmed_dsgvo = " . $confirmationDataProtectionCheckboxValue . ",";
                        $query .= " schrack_confirmed_dsgvo_confirm_text = '" . 'n.a.' . "',";
                        $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
                    }
                    // AGB always = 1, because this is must have only in Checkout:
                    $query .= " schrack_confirmed_agb = 1,";
                    $query .= " schrack_confirmed_agb_confirm_checkboxtext = '" . addslashes($confirmationAGBCheckboxText) . "',";
                    if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDataProtection')) == 1) {
                        $query .= " schrack_confirmed_dataprotection = " . $confirmationDataProtectionCheckboxValue . ",";
                    } else {
                        $query .= " schrack_confirmed_dataprotection = 0,";
                    }
                    $query .= " schrack_confirmed_dataprotection_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
                    $query .= " schrack_confirmed_rightsinformation_notice = '" . $rightsInformationNoticeRegistration . "',";
                    $query .= " schrack_confirmed_rightsinformation_date = '" . date('Y-m-d H:i:s') . "'";

                    // Normal und old Prospects should have been confirmed already by layer confirmation:
                    if ($quoteCustomerType == 'normal' || $quoteCustomerType == 'oldFullProspect' || $quoteCustomerType == 'oldLightProspect') {
                        // Catch very special case : should be confirmed, but not yet confirmed anyway:
                        if ($customer && $customerDSGVOConfirmed == false) {
                            // Writes new entry into DSGVO table (maybe doublette, but necessary):
                            $writeConnection->query($query);

                            // Also updates customer table:
                            $queryUpdateCustomer = "UPDATE customer_entity SET schrack_confirmed_dsgvo = 1 WHERE email LIKE '" . $email . "'";
                            $writeConnection->query($queryUpdateCustomer);

                        }
                        if ($customer && $customerUserTermsConfirmed == false) {
                            $this->setCustomerUserTermsConfirmed($email);
                        }
                        // Normal case: DO nothing -> no database entry!!
                    } else {
                        // Only newProspect (FULL) and Guest:
                        $writeConnection->query($query);

                        // User Terms : Full Prospect Registration and Guest in checkout:
                        $this->setCustomerUserTermsConfirmed($email);
                    }
                }
            } catch (Exception $e) {
                $result = $this->_handleSaveOrderException($e, $this->__('There was an error processing your order. Please contact us or try again later.') . ' [$4df242]');
                // @todo send a general failure email
            }

            try {
                $data = $this->getRequest()->getPost('payment', array());
                if ($data) {
                    $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                        | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                        | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                        | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                        | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                    $this->getOnepage()->getQuote()->getPayment()->importData($data);
                }
            } catch (Mage_Core_Exception $e) {
                $result = $this->_handleSaveOrderException($e, $e->getMessage());
                $this->_updateJsonResultFromCheckoutSession($result);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            } catch (Exception $e) {
                $result = $this->_handleSaveOrderException($e, $this->__('There was an error processing your order. Please contact us or try again later.') . ' [$a0e4e2]');
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            }

            try {

                $this->getOnepage()->saveOrder($orderPostData);

                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                $this->getOnepage()->getCheckout()->setRedirectUrl('');
                /**
                 * when there is redirect to third party, we don't want to save order yet.
                 * we will save the order in return action.
                 */
                if ($redirectUrl) {
                    $result['redirect'] = $redirectUrl;
                }

                $result['success'] = true;
                $result['error'] = false;
            } catch (Mage_Payment_Model_Info_Exception $e) {
                $result = $this->_handleSaveOrderException($e, $e->getMessage());
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            } catch (Mage_Core_Exception $e) {
                $result = $this->_handleSaveOrderException($e, $e->getMessage());
                $this->_updateJsonResultFromCheckoutSession($result);
            } catch (Exception $e) {
                Mage::logException($e);
                $result = $this->_handleSaveOrderException($e, $this->__('There was an error processing your order. Please contact us or try again later.') . ' [$c63568]');
            }

            $this->getOnepage()->getQuote()->save();
            try {
                // $session = Mage::getSingleton('core/session'); PayUnitiy remove action
                // $session->setQuoteIdForPupay($this->getOnepage()->getQuote()->getId());
                // Mage::log('Onepage setting quote id for pupay to: ' . $this->getOnepage()->getQuote()->getId(), null, 'pupay.log'); PayUnitiy remove action
            } catch (Exception $e) {
                // Mage::log('Exception while trying to set quote for pupay: ' . $e->getMessage(), null, 'pupay.log'); PayUnitiy remove action
            }
        } else { // must not perform checkout
            try {
                $this->_sendOnepageNocheckoutEmails();
                $result['success'] = true;
                $result['error'] = false;
                $msg = $this->__('Your request has been sent.');
                Mage::getSingleton('core/session')->addSuccess($msg);
                $this->getOnepage()->getQuote()
                    ->setIsActive(0)
                    ->save();
            } catch (Exception $e) {
                $result['success'] = false;
                $result['error'] = true;
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }


    private function setCustomerUserTermsConfirmed($email) {
        if ($email) {
            $check1 = false;
            $check2 = false;
            $requestIpAddressRemote = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            $requestIpAddress = ((isset($_SERVER['X_FORWARDED_FOR']) && $_SERVER['X_FORWARDED_FOR']) ? '/' . $_SERVER['X_FORWARDED_FOR'] : '');

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            // Getting current version:
            $query = "SELECT * FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $termsId                   = $recordset['entity_id'];
                    $termsVersion              = $recordset['version'];
                    $currentVersionContentHash = $recordset['content_hash'];
                }
            }

            $sessionCustomer = Mage::getSingleton('customer/session')->getCustomer();
            if ($sessionCustomer) {
                $sessionCustomerId = $sessionCustomer->getId();
                if ($sessionCustomerId) {
                    $customerIdQuery = " customer_id = " . $sessionCustomerId . ",";
                } else {
                    $customerIdQuery = '';
                }
            } else {
                $customerIdQuery = '';
            }

            $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
            $query .= " WHERE user_email LIKE '" . $email . "'";
            $query .= " AND terms_id = " . $termsId;
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() === 0) {
                // Insert new entry -> schrack_terms_of_use_confirmation (history table)!!
                $query  = "INSERT INTO schrack_terms_of_use_confirmation SET user_email = '" . $email . "',";
                $query .= $customerIdQuery;
                $query .= " terms_id = " . $termsId . ",";
                $query .= " terms_version = '" . $termsVersion . "',";
                $query .= " client_terms_content_hash = '" . $currentVersionContentHash . "',";
                $query .= " client_ip = '" . $requestIpAddress . "',";
                $query .= " client_ip_remote = '" . $requestIpAddressRemote . "',";
                $query .= " client_type = 'webshop',";
                $query .= " confirmed_at = '" . date("Y-m-d H:i:s") . "'";
                $writeConnection->query($query);
            }

            if ($sessionCustomer && $sessionCustomerId) {
                $query = "UPDATE customer_entity SET schrack_last_terms_confirmed = 1 WHERE email LIKE '" . $email . "'";
                $writeConnection->query($query);

                Mage::log($email . ' -> Set user-term state = 1 : from Checkout', null, "terms_of_use_state.log");

                // First checkpoint:
                $query = "SELECT * FROM customer_entity WHERE schrack_last_terms_confirmed = 1 AND email LIKE '" . $email . "'";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    $check1 = true;
                }
            } else {
                $check1 = true;
            }

            // Second Check:
            $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
            $query .= " WHERE user_email LIKE '" . $email . "' ORDER BY terms_id DESC LIMIT 1";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $currentVersionConfirmdeContentHash = $recordset['client_terms_content_hash'];
                }
                if ($currentVersionContentHash == $currentVersionConfirmdeContentHash) {
                    $check2 = true;
                } else {
                    $hashes = $currentVersionContentHash . " - " . $currentVersionConfirmdeContentHash;
                    Mage::log("Check 2 (checkout) Version Mismatch #2 => " . $hashes, null, "terms_of_use.err.log");
                }
            }

            if ($check1 && $check2) {
                return 'okay';
            } else {
                Mage::log("Checks failed (checkout) : check1 = " . $check1 . ' / check2 = ' . $check2, null, "terms_of_use.err.log");
            }
        } else {
            Mage::log("Check (checkout) no email received #2", null, "terms_of_use.err.log");
            return 'no email received';
        }
    }


    private function _sendOnepageNocheckoutEmails()
    {
        Mage::log('_sendOnepageNocheckoutEmails called', null, 'onepage_nocheckoutemails.log');
        $custSession = Mage::getSingleton('customer/session');
        $coreSession = Mage::getSingleton('core/session');
        if ( $custSession->isLoggedIn() ) {
            Mage::log('_sendOnepageNocheckoutEmails customer is logged in', null, 'onepage_nocheckoutemails.log');
            $customer = $custSession->getCustomer();
            $coreSession->setOnepageName($customer->getName());
            $coreSession->setOnepageEmail($customer->getEmail());
            $coreSession->setOnepagePhone($customer->getPhone());
            // $coreSession->setOnepageHomepage(); gibts ned
        }
        $receiverId = $coreSession->getOnepageReceiver();
        if ( isset($receiverId) ) {
            Mage::log('_sendOnepageNocheckoutEmails receiverId from core session: ' . $receiverId, null, 'onepage_nocheckoutemails.log');
            $receiver = Mage::getModel('schracksales/requestreceiver')->load($receiverId);
            $toAddress = $receiver->getEmail();
            Mage::log('_sendOnepageNocheckoutEmails receiverId from core session -> toAddress: ' . $toAddress, null, 'onepage_nocheckoutemails.log');
        }
        if ( ! isset($toAddress) ) {
            $toAddress = Mage::getStoreConfig('checkout/onepage/requestReceiver');
            Mage::log('_sendOnepageNocheckoutEmails receiverId from config: ' . $toAddress, null, 'onepage_nocheckoutemails.log');
        }


        try {
            Mage::log('_sendOnepageNocheckoutEmails sending to distributor ' . $toAddress, null, 'onepage_nocheckoutemails.log');
            $this->_sendOnepageNocheckoutEmail('checkout/onepage/email/noprices.phtml', $toAddress); // to distributor
        } catch (Exception $e) {
            Mage::log('request receiver email not sent: ' . $e->getMessage(), null, 'xian.log');
            Mage::logException($e);
            throw $e;
        }

        $toCustomerAddress = $coreSession->getOnepageEmail();
        try {
            Mage::log('_sendOnepageNocheckoutEmails sending to customer ' . $toCustomerAddress, null, 'onepage_nocheckoutemails.log');
            $this->_sendOnepageNocheckoutEmail('checkout/onepage/email/noprices_customer.phtml', $toCustomerAddress); // to customer
        } catch (Exception $e) {
            Mage::log('request receiver email not sent: ' . $e->getMessage(), null, 'xian.log');
            Mage::logException($e);
            throw $e;
        }
    }

    private function _isAreadyPending () {
	    $ts = time();
	    $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
	    $fname = '/tmp/ship_order_'.$country.'.lock';
	    if ( ! file_exists($fname) ) {
	        $res = file_put_contents($fname,"");
        }

	    $fp = fopen($fname, "r+");

	    $cnt = 0;
	    while ( ! flock($fp, LOCK_EX) && $cnt < 100 ) {
	        sleep(1);
	        ++$cnt;
        }
        if ( $cnt >= 100 ) {
	        fclose($fp);
	        Mage::log("Giving up flock after 100 seconds...",null,'evil_ship_order.log');
	        return false;
        }

	    $ip = $this->_getUserIP();
	    Mage::log("$ip => $ts",null,'evil_ship_order.log');

        $data = array();
        while (($buffer = fgets($fp,256)) !== false) {
            $data[] = str_replace(PHP_EOL,"",$buffer);
        }

        $newData = array();
        foreach ( $data as $row ) {
            $rowar = explode(',',$row);
            if ( !isset($rowar[0]) || !isset($rowar[1]) ) {
                continue; // SNH
            }
            $otherIp = $rowar[0];
            $otherTs = intval($rowar[1]);
            if ( $ts - $otherTs < 60 ) {                                    // ignore and remove entries older than 1 minute
                $newData[] = $row;                                          // keep younger ones
                if ( $ip == $otherIp && $ts - $otherTs < 4 ) {              // if same ip and time difference between 0..3 seconds
                    Mage::log("TWICE: $ip !",null,'evil_ship_order.log');   // write log  // write log
                    flock($fp, LOCK_UN);                          // leave file data as is
                    fclose($fp);
                    return true;
                } else if ( $ip == $otherIp ) {
                    Mage::log("difference between timestamps >= 4 secs: $ts, $otherTs",null,'evil_ship_order.log');
                }
            }
        }
        $newData[] = "$ip,$ts";

        ftruncate($fp, 0);
        foreach ( $newData as $row ) {
            fwrite($fp,$row . PHP_EOL);
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

	    return false;
    }

    private function _getUserIP() {
        if( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')>0) {
                $addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($addr[0]);
            } else {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function _sendOnepageNocheckoutEmail($template, $toAddress) {
        /* @var $block Mage_Checkout_Block_Onepage_Review_Info */
        $block = Mage::getBlockSingleton('checkout/onepage_review_info');
        $block->setTemplate($template);
        $quote = $this->getOnepage()->getQuote();
        $html = $block->toHtml();
        Mage::log($html, null, 'xian.log');
        $mailHelper = Mage::helper('wws/mailer');
        if (isset($toAddress)) {
            Mage::log("_sendOnepageNocheckoutEmail sends mail to " . $toAddress, null, 'xian.log');
            $args = array('subject' => $this->__('Request'),
                'to' => $toAddress,
                'bcc' => $toAddress,
                'body' => $html,
                'templateVars' => array()
            );
            $csvContent = $this->_getCsvContent($quote);
            $csvAttachment = new Zend_Mime_Part($csvContent);
            $csvAttachment->type = Zend_Mime::TYPE_OCTETSTREAM;
            $csvAttachment->filename = "articles.csv";
            $csvAttachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $csvAttachment->encoding = Zend_Mime::ENCODING_BASE64;
            $mailHelper->send($args, array($csvAttachment));
        } else {
            throw new Exception('no address for checkout request email given (template ' . $template . ')');
        }
    }

    private function _getCsvContent($quote) {
        $text = implode(';', array_map(function($word) { return '"' . $word . '"'; }, array($this->__('SKU'), $this->__('Quantity'), $this->__('Qty Unit')))) . "\n";

        foreach ($quote->getItemsCollection() as $item) {
            $text .= implode(';', array_map(function($word) { return '"' . $word . '"'; }, array($item->getSku(), $item->getQty(), $item->getSchrackProductQtyunit()))) . "\n";

        }
        return $text;
    }
    
	protected function _handleSaveOrderException(Exception $e, $message) {
		Mage::logException($e);
		$this->getOnepage()->getQuote()->save();
		$result = array();
		$result['success'] = false;
		$result['error'] = true;
		$result['error_messages'] = $message;
		return $result;
	}
        
    /**
	 * Save payment ajax action
	 *
	 * Sets either redirect or a JSON response
	 */
	public function saveAddressAction() {
		if ($this->_expireAjax()) {
			return;
		}
		try {
			if (!$this->getRequest()->isPost()) {
				$this->_ajaxRedirectResponse();
				return;
			}

            $result = array();
            $this->getOnepage()->savePayment(array("method" => 'checkmo'));

            $this->_saveAddress($result);
        } catch (Exception $e) {
            $result = $this->_handleSaveAddressException($e, $this->__('There was an error processing your order. Please contact us or try again later.'));
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    private function _saveAddress(&$result) {
        $address = $this->getRequest()->getPost('address');
        $name = $address['name'];
        $email = $address['email'];
        $phone = $address['phone'];
        $homepage = $address['homepage'];
        $country = $address['country'];
        $receiver = isset($address['receiver']) ? $address['receiver'] : null;
        if (isset($name) && isset($email) && isset($phone)) {
            $result['success'] = true;
            $result['error'] = false;
            $result['goto_section'] = 'review';
            $result['update_section'] = array(
                'name' => 'review',
                'html' => $this->_getReviewHtml()
            );
            $session = Mage::getSingleton('core/session');
            $session->setOnepageName($name);
            $session->setOnepageEmail($email);
            $session->setOnepagePhone($phone);
            $session->setOnepageHomepage($homepage);
            $session->setOnepageCountry($country);
            if ( isset($receiver) ) {
                Mage::log('_saveAddress setting receiver to ' . $receiver, null, 'onepage_nocheckoutemails.log');
                $session->setOnepageReceiver($receiver);
            } else {
                Mage::log('_saveAddress NOT setting receiver', null, 'onepage_nocheckoutemails.log');
            }
        }
    }

	protected function _handleSaveAddressException(Exception $e, $message) {
		Mage::logException($e);
		$result = array();
		$result['success'] = false;
		$result['error'] = true;
		$result['error_messages'] = $message;
		return $result;
	}

	protected function _updateJsonResultFromCheckoutSession(&$result) {
		if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
			$result['goto_section'] = $gotoSection;
			$this->getOnepage()->getCheckout()->setGotoSection(null);
		}

		if ($updateSection = $this->getOnepage()->getCheckout()->getUpdateSection()) {
			if (isset($this->_sectionUpdateFunctions[$updateSection])) {
				$updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
				$result['update_section'] = array(
					'name' => $updateSection,
					'html' => $this->$updateSectionFunction()
				);
			}
			$this->getOnepage()->getCheckout()->setUpdateSection(null);
		}
	}

	public function errorAction() {
		$this->loadLayout();
		$this->_initLayoutMessages('checkout/session');
		$this->renderLayout();
	}

	protected function _redirect($path, $arguments=array()) {
		$this->_passQuoteMessagesToSession();
		return parent::_redirect($path, $arguments);
	}

	protected function _redirectUrl($url) {
		$this->_passQuoteMessagesToSession();
		return parent::_redirectUrl($url);
	}

	protected function _ajaxRedirectResponse() {
		$this->_passQuoteMessagesToSession();
		return parent::_ajaxRedirectResponse();
	}

	protected function _passQuoteMessagesToSession() {
		$checkout = Mage::getSingleton('checkout/session');
		foreach ($this->getOnepage()->getQuote()->getMessages() as $message) {
			if ($message) {
				$checkout->addMessage($message);
			}
		}
	}

    public function checkCustomerAlreadyAnsweredResearchAction() {
        $email           = $this->getOnepage()->getQuote()->getCustomerEmail();
        $resource        = Mage::getSingleton('core/resource');
        $readConnection  = $resource->getConnection('core_read');
        $result          = array('msg' => 'done');
        $researchresult  = '';

        // Get information from database, if customer already answered research question:
        $query = "SELECT result FROM customer_research WHERE block LIKE 'geis' AND email LIKE '". $email ."'";
        $queryResult = $readConnection->fetchAll($query);

        if (is_array($queryResult) && !empty($queryResult)) {
            foreach ($queryResult as $index => $recordset) {
                $researchresult = $recordset['result'];
            }
        }

        if ($researchresult) {
            unset($result);
            $result = array('errormsg' => 'customer already answered research');
        }

        echo json_encode($result);
    }

	public function saveResearchResultAction() {
        $customerType = $this->getRequest()->getPost('customer_type');
        $answer       = $this->getRequest()->getPost('reseachAnswer');
        $email        = $this->getOnepage()->getQuote()->getCustomerEmail();
        $arrResult    = array('errormsg' => 'error case');
        if ($customerType == '') $customerType = $this->getOnepage()->getQuote()->getSchrackCustomerType();
        if ($customerType == '') $customerType = 'normal';

        if (!in_array($customerType, array('normal', 'guest', 'prospect-user', 'full-prospect', 'light-prospect', 'login-user'))) {
            echo json_encode($arrResult);
        } else {
            if ($answer == 'research_agree') $answerFromCustomer = 'agree';
            if ($answer == 'research_deny') $answerFromCustomer = 'deny';
            $researchresult= '';

            $resource        = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $readConnection  = $resource->getConnection('core_read');

            $query = "SELECT result FROM customer_research WHERE block LIKE 'geis' AND email LIKE '". $email ."'";
            $queryResult = $readConnection->fetchAll($query);

            if (is_array($queryResult) && !empty($queryResult)) {
                foreach ($queryResult as $index => $recordset) {
                    $researchresult = $recordset['result'];
                }
            }

            if (!$researchresult) {
                // Insert answer from customer into database yes/no
                $query = "INSERT INTO customer_research SET block = 'geis', email = '" . $email . "', result = '" . $answerFromCustomer . "', create_timestamp = '" . date('Y-m-d H:i:s') . "', customer_type = '" . $customerType . "', infomail = 1";
                $writeConnection->query($query);

                // Write info mail:
                $mail = new Zend_Mail('utf-8');
                $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
                    ->setSubject('Geis Project Response')
                    ->setBodyHtml($email . ' -> ' . $answerFromCustomer);
                $mail->addTo(Mage::getStoreConfig('schrack/research/geis/infomailrecipient'));
                $mail->send();

                unset($arrResult);
                $arrResult = array('msg' => 'done');
            }

            echo json_encode($arrResult);
        }
	}

}

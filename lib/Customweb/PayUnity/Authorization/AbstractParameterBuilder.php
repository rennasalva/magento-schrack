<?php

/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

//require_once 'Customweb/PayUnity/AbstractParameterBuilder.php';
//require_once 'Customweb/Filter/Input/String.php';
//require_once 'Customweb/Core/Util/Rand.php';

abstract class Customweb_PayUnity_Authorization_AbstractParameterBuilder extends Customweb_PayUnity_AbstractParameterBuilder {

	/**
	 * @param array $formData
	 * @return array
	 */
	public function buildAuthorizationParameters(array $formData = array()){
		return array_merge($this->getAuthenticationParameters(), $this->getAdditionalParameters(), $this->getTestModeParameters(),
				$this->getBasicPaymentParameters(), $this->getCustomerParameters(), $this->getBillingAddressParameters(),
				$this->getShippingAddressParameters(), $this->getCartParameters(), $this->getTokenizationParameters(), $this->getRecurringParameters(),
				$this->getPaymentMethod()->getAuthorizationParameters($this->getTransaction(), $formData), $this->getIdParameters());
	}

	/**
	 * @param array $formData
	 * @return array
	 */
	public function buildAliasAuthorizationParameters(array $formData = array()){
		return array_merge($this->getAuthenticationParameters(), $this->getAdditionalParameters(), $this->getTestModeParameters(),
				$this->getBasicPaymentParameters(), $this->getCustomerParameters(), $this->getBillingAddressParameters(),
				$this->getShippingAddressParameters(), $this->getCartParameters(),
				$this->getPaymentMethod()->getAuthorizationParameters($this->getTransaction(), $formData), $this->getAsynchronousPaymentParameters(),
				$this->getIdParameters());
	}

	/**
	 * @return array
	 */
	public function buildStatusParameters(){
		return $this->getAuthenticationParameters();
	}
	
	public function getIdParameters() {
		return array(
			'merchantTransactionId' => $this->getPaymentMethod()->getMerchantTransactionId($this->getTransaction()),
			'descriptor' => $this->getPaymentMethod()->getDescriptor($this->getTransaction()),
		);
	}

	/**
	 * @return array
	 */
	public function buildStatusParametersMerchantId(){
		return array_merge($this->getAuthenticationParameters(),
				array(
					'merchantTransactionId' => $this->getPaymentMethod()->getMerchantTransactionId($this->getTransaction())
				));
	}

	/**
	 * @return array
	 */
	protected function getBasicPaymentParameters(){
		$parameters = array();
        //####################################################### SCHRACK_CUSTOM
        //---------------------------------------------------- get  WWS order id
        $wws_order_id = $this->getOrderContext()->getSchrackWWSNumber();
        //----------------------------------------------------------------------
        $floatWwsAmountNet                = 0;
        $floatWwsAmountTax                = 0;
        $floatWwsAmountTot                = 0;
        $floatWwsAmountNetPlusTaxAddition = 0;
        //----------------------------------------------------------------------
        $wwsOrderData = $this->getWWSOrderData();
        $hasCashDiscount = $wwsOrderData['has_discount'];
        //----------------------------------------------------------------------
        if ($wwsOrderData && is_array($wwsOrderData) && !empty($wwsOrderData)) {
            $floatWwsAmountNet = floatval($wwsOrderData['amount_net']);
            $floatWwsAmountTax = floatval($wwsOrderData['amount_tax']);
            $floatWwsAmountTot = floatval($wwsOrderData['amount_tot']);
        }
        //----------------------------------------------------------------------
        $floatWwsAmountNetPlusTaxAddition = ($floatWwsAmountNet + $floatWwsAmountTax);
        $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        //----------------------------------------------------------------------
        if ($country == 'CZ') {
            $floatWwsAmountNetPlusTaxAddition = round($floatWwsAmountNetPlusTaxAddition);
        }
        //----------------------------------------------------------------------
        if ($floatWwsAmountNet > 0 && $floatWwsAmountTot > 0) {
            if ( ( number_format($floatWwsAmountNetPlusTaxAddition, 2, '.', '') == number_format($floatWwsAmountTot, 2, '.', '') || $hasCashDiscount == 1 ) || $country == 'CZ' ) {
                //--------------------------------------------------------------
                //The amount has to always have to decimal places, according to
                // documentation, this is also true for currencies with
                // 3 decimals like JOD, otherwise the transaction is declined.
                //--------------------------------------------------------------
                /**
                 *
                 * !!! ATTENTION !!! ATTENTION !!! ATTENTION !!! ATTENTION !!!
                 * Bugfix_9443
                 * send WWS value as amount instead of
                 * (wrong from a wws perspective seen) default order value
                 *
                 * Further fix will be found in
                 * app/code/core/Mage/Sales/Model/Quote/Payment.php
                 * app/code/core/Mage/Sales/Model/Convert/Quote.php
                 *
                 * !!! ATTENTION !!! ATTENTION !!! ATTENTION !!! ATTENTION !!!
                ***************************************************************/
                $parameters['amount']                   = number_format($this->getOrderContext()->getOrderAmountInDecimals(), 2, '.', '');
//                $parameters['amount']                   = number_format($floatWwsAmountTot, 2, '.', '');
                $parameters['currency']					= $this->getOrderContext()->getCurrencyCode();
                $parameters['paymentType']				= $this->getPaymentMethod()->getPaymentTypeCode($this->getTransactionContext()->getCapturingMode());
                $parameters['merchantTransactionId']	= $this->getPaymentMethod()->getMerchantTransactionId($this->getTransaction());
                //--------------------------------------------------------------
                Mage::log('(getBasicPaymentParameters #okay-01) WWS-Order-Id = ' . $wws_order_id . ' -> Nettobetrag = ' . $floatWwsAmountNet . ' -> Steuer = ' . $floatWwsAmountTax . ' -> Gesamtbetrag = ' . $floatWwsAmountTot . ' -> Currency-Code = ' . $this->getOrderContext()->getCurrencyCode() . ' -> hasCashDiscount = ' . $hasCashDiscount, null, '/payment/payunity.log');
                // $this->sendDevMessageMail($wws_order_id, ' PayUnity SUCCESS ' . ' >>> wwsAmountNet = ' . $floatWwsAmountNet . ' wwsAmountTax = ' . $floatWwsAmountTax . ' wwsAmountTot = ' . $floatWwsAmountTot . ' wwsAmountNetPlusTaxAddition = ' . $floatWwsAmountNetPlusTaxAddition);
            } else {
                $this->sendDevMessageMail($wws_order_id,' >>> (getBasicPaymentParameters #1) Amount Mismatch + Tax = Tot : WWS-Order-Id ' . $wws_order_id . ' -> hasCashDiscount = ' . $hasCashDiscount);
                //--------------------------------------------------------------
                $logMessage = '(getBasicPaymentParameters #1) Amount Mismatch: ' .
                               'Net + Tax = Tot >>> ' .
                               'AmountNet = ' . $floatWwsAmountNet .
                               '  AmountTax = ' . $floatWwsAmountTax .
                               ' -> AmountTot = ' . $floatWwsAmountTot .
                               ' Pre-CALCULATION = ' . $floatWwsAmountNetPlusTaxAddition .
                               ' intval_#1=' . intval($floatWwsAmountNetPlusTaxAddition * 100) .
                               ' -> compare to -> intval#2=' . intval($floatWwsAmountTot * 100) .
                               '  : WWS-Order-Id ' . $wws_order_id .
                               ' -> hasCashDiscount = ' . $hasCashDiscount;
                //--------------------------------------------------------------
                Mage::log($logMessage, null, '/payment/payunity_err.log');
            }
        } else {
            $this->sendDevMessageMail($wws_order_id,' >>> (getBasicPaymentParameters #2) Amount Lower Or Equal Zero : WWS-Order-Id ' . $wws_order_id . ' -> hasCashDiscount = ' . $hasCashDiscount);
            Mage::log('(getBasicPaymentParameters #2) Amount Lower Or Equal Zero : WWS-Order-Id ' . $wws_order_id. ' -> hasCashDiscount = ' . $hasCashDiscount, null, '/payment/payunity_err.log');
        }
        //----------------------------------------------------------------------
        $parameters['merchantInvoiceId'] = $this->getOrderContext()->getSchrackWWSNumber();
        //--------------------------------------------------------------- RETURN
        //############################################# SCHRACK_CUSTOM ***END***
        return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getCustomerParameters(){
		$paymentCustomerContext = $this->getPaymentCustomerContext()->getMap();
		$parameters = array();
		if ($this->getOrderContext()->getCustomerId()) {
			$parameters['customer.merchantCustomerId'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getCustomerId(), 48)->filter();
		}
		else {
			$parameters['customer.merchantCustomerId'] = Customweb_Core_Util_Rand::getRandomString(48, '');
		}

		$parameters['customer.givenName'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getBillingAddress()->getFirstName(), 48)->filter();
		$parameters['customer.surname'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getBillingAddress()->getLastName(), 48)->filter();
		if ($this->getOrderContext()->getBillingAddress()->getGender() == 'male') {
			$parameters['customer.sex'] = 'M';
		}
		else if ($this->getOrderContext()->getBillingAddress()->getGender() == 'female') {
			$parameters['customer.sex'] = 'F';
		}
		if ($this->getOrderContext()->getBillingAddress()->getDateOfBirth()) {
			$parameters['customer.birthDate'] = $this->getOrderContext()->getBillingAddress()->getDateOfBirth()->format('Y-m-d');
		}
		if ($this->getOrderContext()->getBillingAddress()->getPhoneNumber()) {
			$parameters['customer.phone'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getBillingAddress()->getPhoneNumber(), 25)->filter();
		}
		if ($this->getOrderContext()->getBillingAddress()->getMobilePhoneNumber()) {
			$parameters['customer.mobile'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getBillingAddress()->getMobilePhoneNumber(),
					25)->filter();
		}
		$parameters['customer.email'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getCustomerEMailAddress(), 128)->filter();
		if ($this->getOrderContext()->getBillingAddress()->getCompanyName()) {
			$parameters['customer.companyName'] = Customweb_Filter_Input_String::_($this->getOrderContext()->getBillingAddress()->getCompanyName(), 40)->filter();
		}
		$request = $this->getContainer()->getBean('Customweb_Core_Http_IRequest');
		try {
			$parameters['customer.ip'] = $request->getRemoteAddress();
		}
		catch (Exception $e) {
		}
		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getBillingAddressParameters(){
		$parameters = array();
		$billing = $this->getOrderContext()->getBillingAddress();

		$parameters['billing.street1'] = Customweb_Filter_Input_String::_(trim($billing->getStreet()), 50)->filter();
		$parameters['billing.city'] = Customweb_Filter_Input_String::_(trim($billing->getCity()), 30)->filter();
		if ($this->getPaymentMethod()->existsPaymentMethodConfigurationValue('send_postal_state') && $this->getPaymentMethod()->getPaymentMethodConfigurationValue('send_postal_state') == 'yes') {
    		$state = trim($billing->getState());
    		if (!empty($state)) {
    			if (strlen($state) < 2) {
    				if (is_int($state)) {
    					$state = '0' . $state;
    				}
    				else {
    					$state = ' ' . $state;
    				}
    			}
    			$parameters['billing.state'] = Customweb_Filter_Input_String::_($state, 50)->filter();
    		}
		}
		$parameters['billing.postcode'] = Customweb_Filter_Input_String::_(trim($billing->getPostCode()), 10)->filter();
		$parameters['billing.country'] = $billing->getCountryIsoCode();

		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getShippingAddressParameters(){
		$parameters = array();
		$shipping = $this->getOrderContext()->getShippingAddress();

		$parameters['shipping.givenName'] = Customweb_Filter_Input_String::_(trim($shipping->getFirstName()), 48)->filter();
		$parameters['shipping.surname'] = Customweb_Filter_Input_String::_(trim($shipping->getLastName()), 48)->filter();
		$parameters['shipping.street1'] = Customweb_Filter_Input_String::_(trim($shipping->getStreet()), 50)->filter();
		$parameters['shipping.city'] = Customweb_Filter_Input_String::_(trim($shipping->getCity()), 30)->filter();
		if ($this->getPaymentMethod()->existsPaymentMethodConfigurationValue('send_postal_state') && $this->getPaymentMethod()->getPaymentMethodConfigurationValue('send_postal_state') == 'yes') {
    		$state = trim($shipping->getState());
    		if (!empty($state)) {
    			if (strlen($state) < 2) {
    				if (is_int($state)) {
    					$state = '0' . $state;
    				}
    				else {
    					$state = ' ' . $state;
    				}
    			}
    			$parameters['shipping.state'] = Customweb_Filter_Input_String::_($state, 50)->filter();
    		}
		}
		$parameters['shipping.postcode'] = Customweb_Filter_Input_String::_(trim($shipping->getPostCode()), 10)->filter();
		$parameters['shipping.country'] = $shipping->getCountryIsoCode();

		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getCartParameters(){
        //####################################################### SCHRACK_CUSTOM
        // complete return parameter customsiation to work with wws ?????
        //----------------------------------------------------------------------
        $parameters = array();
        $wws_order_id = $this->getOrderContext()->getSchrackWWSNumber();
        //----------------------------------------------------------------------
        $floatWwsAmountNet                = 0;
        $floatWwsAmountTax                = 0;
        $floatWwsAmountTot                = 0;
        $floatWwsAmountNetPlusTaxAddition = 0;
        //----------------------------------------------------------------------
        $wwsOrderData = $this->getWWSOrderData();
        $hasCashDiscount = $wwsOrderData['has_discount'];
        //----------------------------------------------------------------------
        if ($wwsOrderData && is_array($wwsOrderData) && !empty($wwsOrderData)) {
            $strWwsAmountNet = number_format(floatval($wwsOrderData['amount_net']), 2, '.', '');
            $strWwsAmountTax = number_format(floatval($wwsOrderData['amount_tax']), 2, '.', '');
            $strWwsAmountTot = number_format(floatval($wwsOrderData['amount_tot']), 2, '.', '');
            $floatWwsAmountNet = floatval($wwsOrderData['amount_net']);
            $floatWwsAmountTax = floatval($wwsOrderData['amount_tax']);
            $floatWwsAmountTot = floatval($wwsOrderData['amount_tot']);
        }
        //----------------------------------------------------------------------
        $floatWwsAmountNetPlusTaxAddition = $floatWwsAmountNet + $floatWwsAmountTax;
        $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        //----------------------------------------------------------------------
        if ($country == 'CZ') {
            $floatWwsAmountNetPlusTaxAddition = round($floatWwsAmountNetPlusTaxAddition);
        }
        //----------------------------------------------------------------------
        if ($floatWwsAmountNet > 0 && $floatWwsAmountTot > 0) {
            if ( ( number_format($floatWwsAmountNetPlusTaxAddition, 2, '.', '') == number_format($floatWwsAmountTot, 2, '.', '') || $hasCashDiscount == 1 ) || $country == 'CZ' ) {
                $mageTranslation = Mage::getModel('core/translate')
                    ->setLocale(Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')))
                    ->init('frontend', true);
                //--------------------------------------------------------------
                $translatedTotal = $mageTranslation->translate(array('Total Amount'));
                $translatedNetto = $mageTranslation->translate(array('NetAmount'));
                $translatedTax   = $mageTranslation->translate(array('Tax Amount'));
                //--------------------------------------------------------------
                $parameters['cart.items[0].name']     = $translatedTotal;
                $parameters['cart.items[0].price']    = $strWwsAmountTot;
                $parameters['cart.items[0].currency'] = $this->getOrderContext()->getCurrencyCode();
                $parameters['cart.items[0].tax']      = 0.00;
                $parameters['cart.items[1].name']     = $translatedNetto;
                $parameters['cart.items[1].price']    = $strWwsAmountNet;
                $parameters['cart.items[1].currency'] = $this->getOrderContext()->getCurrencyCode();
                $parameters['cart.items[1].tax']      = 0.00;
                $parameters['cart.items[2].name']     = $translatedTax;
                $parameters['cart.items[2].price']    = $strWwsAmountTax;
                $parameters['cart.items[2].currency'] = $this->getOrderContext()->getCurrencyCode();
                $parameters['cart.items[2].tax']      = 0.00;
                //--------------------------------------------------------------
                $logMessage = ''.
                    '(getCartParameters) WWS-Order-Id = ' . $wws_order_id .
                    ' -> Nettobetrag = ' . $floatWwsAmountNet .
                    ' -> Steuer = ' . $floatWwsAmountTax .
                    ' -> Gesamtbetrag = ' . $floatWwsAmountTot .
                    ' -> Currency-Code = ' . $this->getOrderContext()->getCurrencyCode() .
                    ' -> hasCashDiscount = ' . $hasCashDiscount;
                //--------------------------------------------------------------
                Mage::log($logMessage, null, '/payment/payunity.log');
                // $this->sendDevMessageMail($wws_order_id, ' PayUnity SUCCESS (Cart-Calculation) ' . ' >>> wwsAmountNet = ' . $floatWwsAmountNet . ' wwsAmountTax = ' . $floatWwsAmountTax . ' wwsAmountTot = ' . $floatWwsAmountTot . ' wwsAmountNetPlusTaxAddition = ' . $floatWwsAmountNetPlusTaxAddition);
            } else {
                $this->sendDevMessageMail($wws_order_id,' >>> (getCartParameters #1) Amount Mismatch + Tax = Tot : WWS-Order-Id ' . $wws_order_id . ' -> hasCashDiscount = ' . $hasCashDiscount);
                $logMessage = ''.
                    '(getCartParameters #1) Amount Mismatch: Net + Tax = Tot >>> ' .
                    'AmountNet = ' . $floatWwsAmountNet .
                    '  AmountTax = ' . $floatWwsAmountTax .
                    ' -> AmountTot = ' . $floatWwsAmountTot .
                    ' Pre-CALCULATION = ' . $floatWwsAmountNetPlusTaxAddition .
                    ' intval_#1=' . intval($floatWwsAmountNetPlusTaxAddition * 100) .
                    ' -> compare to -> intval#2=' . intval($floatWwsAmountTot * 100) .
                    '  : WWS-Order-Id ' . $wws_order_id .
                    ' -> hasCashDiscount = ' . $hasCashDiscount;
                Mage::log($logMessage, null, '/payment/payunity_err.log');
            }
        } else {
            $this->sendDevMessageMail($wws_order_id,' >>> (getCartParameters #2)  Amount Lower Or Equal Zero : WWS-Order-Id ' . $wws_order_id . ' -> hasCashDiscount = ' . $hasCashDiscount);
            Mage::log('(getCartParameters #2) Amount Lower Or Equal Zero : WWS-Order-Id ' . $wws_order_id . ' -> hasCashDiscount = ' . $hasCashDiscount, null, '/payment/payunity_err.log');
        }
        //----------------------------------------------------------------------
        return $parameters;
        //############################################# SCHRACK_CUSTOM ***END***
	}

	/**
	 * @return array
	 */
	protected function getTokenizationParameters(){
		$parameters = array();
		if ($this->getTransactionContext()->getAlias() == 'new' && !in_array('AliasManager', $this->getPaymentMethod()->getNotSupportedFeatures())) {
			$parameters['createRegistration'] = 'true';
		}
		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getRecurringParameters(){
		$parameters = array();
		if ($this->getTransactionContext()->createRecurringAlias()) {
			$parameters['recurringType'] = 'INITIAL';
			$parameters['createRegistration'] = 'true';
		}
		return $parameters;
	}

	/**
	 * @return array
	 */
	protected function getAsynchronousPaymentParameters(){
		$parameters = array();
		$parameters['shopperResultUrl'] = (string) $this->getContainer()->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl('process', 'async',
				array(
					'cw_transaction_id' => $this->getTransaction()->getExternalTransactionId()
				));
		return $parameters;
	}

    //########################################################### SCHRACK_CUSTOM
    // EXTRA METHODS FOR WWS INTEROPERABILITY + EXTRA MAIL SENDING
    //--------------------------------------------------------------------------
    protected function getWWSOrderData () {
        $responseData = array();

        $wws_order_id = $this->getOrderContext()->getSchrackWWSNumber();

        if ($wws_order_id) {
            $query  = "SELECT amount_net, amount_tax, amount_tot, has_discount FROM wws_insert_update_order_response";
            $query .= " WHERE wws_order_id LIKE '" . $wws_order_id . "' ORDER BY response_datetime DESC LIMIT 1";

            $resource = Mage::getSingleton('core/resource');
            $readConnection  = $resource->getConnection('core_read');

            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $responseData['amount_net']   = round(floatval($recordset['amount_net']), 2);
                    $responseData['amount_tax']   = round(floatval($recordset['amount_tax']), 2);
                    $responseData['amount_tot']   = round(floatval($recordset['amount_tot']), 2);
                    $responseData['has_discount'] = $recordset['has_discount'];
                }
            }
        }

        return $responseData;
    }


    private function sendDevMessageMail($wws_order_id, $messageErrorText = '') {
        // Send E-Mail to developer
        $mail = new Zend_Mail('utf-8');
        $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        $mailSubject = 'CreditCard Payment AbstractParameterBuilder Issue >>> COUNTRY = ' . $country . '  >>> WWS-Order-ID: ' . $wws_order_id;
        $mailTextErrorText = $mailSubject . $messageErrorText;
        try {
            $mail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
                ->setSubject($mailSubject)
                ->setBodyHtml($mailTextErrorText)
                ->addTo( Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails') )
                ->send();
        } catch (Exception $ex) {
            Mage::log($mailTextErrorText . ' Mail Transfer Failed: ' . Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'), null, '/payment/payunity_err.log');
            Mage::log($ex, null, '/payment/payunity_err.log');
        }
    }
    //################################################# SCHRACK_CUSTOM ***END***

}

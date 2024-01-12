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
 *
 * @category	Customweb
 * @package		Customweb_PayUnityCw
 *
 */

class Customweb_PayUnityCw_ProcessController extends Customweb_PayUnityCw_Controller_Action
{
	public function processAction()
	{
		$container = Mage::helper('PayUnityCw')->createContainer();
		$packages = array(
			0 => 'Customweb_PayUnity',
 			1 => 'Customweb_Payment_Authorization',
 		);
		$adapter = Mage::getModel('payunitycw/endpointAdapter');

		$dispatcher = new Customweb_Payment_Endpoint_Dispatcher($adapter, $container, $packages);
		$response = $dispatcher->invokeControllerAction(Customweb_Core_Http_ContextRequest::getInstance(), 'process', 'index');
		$wrapper = new Customweb_Core_Http_Response($response);
		$wrapper->send();
		die();
	}

	public function getHiddenFieldsAction()
	{
		$transaction = $this->getTransactionFromSession();
		$javaScriptObjectString = $transaction->getOrder()->getPayment()->getMethodInstance()->generateHiddenFormParameters($transaction);

		echo $javaScriptObjectString;
		exit;
	}

	public function ajaxAction()
	{
		$transaction = $this->getTransactionFromSession();
		$javaScriptObjectString = $transaction->getOrder()->getPayment()->getMethodInstance()->generateJavascriptForAjax($transaction);

		echo $javaScriptObjectString;
		exit;
	}

	/**
	 * This action is needed for hidden and server authorization.
	 */
	public function dummyAction()
	{
		if ($this->getRequest()->isXmlHttpRequest()) {
			$jsonObject = array();
			$jsonObject['success'] = true;
			echo json_encode($jsonObject);
			return;
		}

		$this->loadLayout();

		$this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');

		$this->getLayout()->getBlock('content')->append(
			$this->getLayout()->createBlock('payunitycw/dummy')
		);

		$this->renderLayout();
	}

	private function getActivPaymentMethods($code)
	{
		$payments = Mage::getSingleton('payment/config')->getActiveMethods();
		foreach ($payments as $paymentCode => $paymentModel) {
			if($code == $paymentCode && $paymentModel instanceof Customweb_PayUnityCw_Model_Method){
				return $paymentModel;
			}
		}
		return null;
	}

	public function getVisibleFieldsAction()
	{
		$payment = $this->getActivPaymentMethods($_REQUEST['payment_method']);
		if($payment != null){
			$html = $payment->generateVisibleFormFields($_REQUEST);
			$javascript = $payment->generateFormJavaScript($_REQUEST);
		}
		else{
			$this->getHelper()->log("PayUnityCw : ProcessController::getVisibleFieldsAction() Could not find payment method '" . $_REQUEST['payment_method']);
			$html = Mage::helper("PayUnityCw")->__("Technical issue: This payment methods is not available at the moment.");
		}

		$result = array(
			'html' => $html,
			'js' => $javascript
		);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}

	public function failAction()
	{
		$transaction = $this->getTransaction();
		$transaction->getOrder()->getPayment()->getMethodInstance()->fail($transaction, $_REQUEST);

		$redirectUrl = $this->getHelper()->getFailUrl($transaction);

		session_write_close();

		header_remove('Set-Cookie');
		header('Location: ' . $redirectUrl);
		die();
	}

	public function ppRedirectAction()
	{
		try{
			$transaction = $this->getTransactionFromRequest();
			$transaction->getOrder()->getPayment()->getMethodInstance()->redirectToPaymentPage($transaction, $_REQUEST);
		}
		catch(Exception $e){
			$this->loadLayout();
			$this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
			$this->getLayout()->getBlock('content')->append(
					$this->getLayout()->createBlock('payunitycw/expired')
					);
			$this->renderLayout();
		}
	}

	public function authorizeAction()
	{
		$transaction = $this->getTransactionFromSession();
		$response = $transaction->getOrder()->getPayment()->getMethodInstance()->processServerAuthorization($transaction, $_REQUEST);
		$transaction->save();
		$wrapper = new Customweb_Core_Http_Response($response);
		$wrapper->send();
		die();
	}

    public function successAction()
    {
        $transaction = $this->getTransaction();
        $redirectUrl = $this->getHelper()->waitForNotification($transaction);

        //####################################################### SCHRACK_CUSTOM
        // Finalize order by sending LA3 to WWS (SOAP request -> ship_order)
        // improved logging
        //----------------------------------------------------------------------
        $session     = Mage::getSingleton('checkout/session');
        $order       = Mage::getModel('sales/order')->load($transaction->getData('order_id'));
        $lastQuoteId = $order->getQuoteId();
        //----------------------------------------------------------------------
        if ($order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING ) {
            // Fetch data from database, and check, if sent "ship_order" before:
            $alreadySentShipOrder = false;
            //------------------------------------------------------------------
            $resource = Mage::getSingleton('core/resource');
            $readConnection  = $resource->getConnection('core_read');
            $query  = "SELECT wws_order_id FROM wws_ship_order_request";
            $query .=  " WHERE wws_order_id LIKE '" . $order->getData('schrack_wws_order_number') . "'";
            $query .=  " AND flag_order = 1 AND ship_flag_true = 1";
            //------------------------------------------------------------------
            if ($order->getData('schrack_wws_order_number')) {
                $queryResult = $readConnection->query($query);
                if ($queryResult->rowCount() > 0) {
                    $alreadySentShipOrder = true;
                }
            }
            //------------------------------------------------------------------
            if ($alreadySentShipOrder == false) {
                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
                //--------------------------------------------------------------
                Mage::helper('wws/request')->finalizeWwsOrder($quote, $customer, array('paymentInfo' => $transaction->getData('transaction_external_id'), 'memo' => 'SHIP=TRUE'), 'payunity-payment');
                //------------- Set success quote id to session for success page
                $session->setLastSuccessQuoteId($lastQuoteId);
                $session->setQuoteId($lastQuoteId);
                //--------------------------------------- Collect Logging data's
                $logData = array();
                $logData['action']                   = 'ProcessController::successAction()';
                $logData['time']                     = date('Y-m-d H:i:s');
                $logData['created_on']               = $transaction->getData('created_on');
                $logData['updated_on']               = $transaction->getData('updated_on');
                $logData['customer_id']              = $transaction->getData('customer_id');
                $logData['magento_order_id']         = $transaction->getData('order_id');
                $logData['schrack_wws_customer_id']  = $order->getData('schrack_wws_customer_id');
                $logData['schrack_wws_order_number'] = $order->getData('schrack_wws_order_number');
                $logData['ip-address']               = $order->getData('x_forwarded_for');
                $logData['payment_id']               = $transaction->getData('payment_id');
                $logData['transaction_id']           = $transaction->getData('transaction_id');
                $logData['order_payment_id']         = $transaction->getData('order_payment_id');
                $logData['payment_method']           = $transaction->getData('payment_method');
                $logData['transaction_external_id']  = $transaction->getData('transaction_external_id');
                $logData['authorization_amount']     = $transaction->getData('authorization_amount');
                $logData['authorization_status']     = $transaction->getData('authorization_status');
                //--------------------------------------- pretify logging data's
                $logDataFlat = "";
                foreach ($logData as $key => $value) {
                    $logDataFlat .= "[". $key . "] => ". $value . "\n";
                }
                //----------------------------------------- write logging data's
                Mage::log("\n" . $logDataFlat, null, '/payment/payments-success.log');
                //------------- send developer notification mail in case of fail
                $wwsOrderNumber = $order->getData('schrack_wws_order_number');
                $stockStatusAvailable = Mage::helper('wws/request')->stockLockDisabled($wwsOrderNumber);
                if ($wwsOrderNumber && $stockStatusAvailable == false) {
                    $countryCode = Mage::getStoreConfig('schrack/general/country');
                    $message  = "ship_order must be manually executed in country = " . strtoupper($countryCode);
                    $message .= " for Order = " . $wwsOrderNumber;
                    Mage::helper('schrack/email')->sendDeveloperMail('CC Payment authorized on locked stock in country',$message);
                }
            }
        }
        //############################################# SCHRACK_CUSTOM ***END***

        header('Location: ' . $redirectUrl);
        exit;
    }
	public function motoFailAction()
	{
		$transaction = $this->getTransaction();
		header_remove('Set-Cookie');
		header('Location: ' . $transaction->getTransactionObject()->getTransactionContext()->getRealBackendFailedUrl() . '?cstrxid=' . $_REQUEST['cstrxid']);
		exit;
	}

	public function motoSuccessAction()
	{
		$transaction = $this->getTransaction();
		header_remove('Set-Cookie');
		header('Location: ' . $transaction->getTransactionObject()->getTransactionContext()->getRealBackendSuccessUrl() . '?cstrxid=' . $_REQUEST['cstrxid']);
		exit;
	}

	public function motoCancelAction()
	{
		$transaction = $this->getTransaction();
		header_remove('Set-Cookie');
		header('Location: ' . $transaction->getTransactionObject()->getTransactionContext()->getRealBackendCancelUrl() . '?cstrxid=' . $_REQUEST['cstrxid']);
		exit;
	}

	public function preloadOnepageAction()
	{
		$layout = Mage::getModel('core/layout');
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_paymentmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$paymentMethodsHtml = $layout->getOutput();

		$layout = Mage::getModel('core/layout');
		$update = $layout->getUpdate();
		$update->load('checkout_onepage_shippingmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$shippingMethodsHtml = $layout->getOutput();

		$result = array();
		$result['update_section'] = array(
			array(
				'name' => 'payment-method',
				'html' => $paymentMethodsHtml
			),
			array(
				'name' => 'shipping-method',
				'html' => $shippingMethodsHtml
			)
		);
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}
}
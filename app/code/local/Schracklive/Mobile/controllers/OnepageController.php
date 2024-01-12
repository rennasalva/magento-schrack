<?php

require_once('app/code/local/Schracklive/SchrackCheckout/controllers/OnepageController.php');

class Schracklive_Mobile_OnepageController extends Schracklive_SchrackCheckout_OnepageController {

	const DELIVERY_SHIPPING_METHOD = 'freeshipping_freeshipping'; // hardcoded

	protected $_redirectSet = false;

    protected function _construct(){
       parent::_construct();
       Mage::getDesign()->setArea('frontend') //Area (frontend|adminhtml)
        ->setPackageName('schrack') //Name of Package
        ->setTheme('iphone');// Name of theme
    }

	public function preDispatch() {
		parent::preDispatch();
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if (!$customer || !$customer->getId()) {
			Mage::getSingleton('checkout/session')->addError($this->__('You are not logged in.'));
		}
		return $this;
	}

	public function indexAction() {
		parent::indexAction();

		if ($this->_redirectSet) {
			$quote = $this->getOnepage()->getQuote();
			if (!$quote->hasItems()) {
				Mage::getSingleton('checkout/session')->addNotice($this->__('You have no items in your shopping cart.'));
			}
			return;
		}

		$customerSession = Mage::getSingleton('customer/session');
		$customer = $customerSession->getCustomer();

		$quote = $this->getOnepage()->getQuote();

		if ($this->getRequest()->getParam('deliveryType') == 1) {
			$shippingMethod = $this->_getPickupShippingMethodForCustomer($customer);
	        $quote->setIsPickup(true);
		} else {
			$shippingMethod = Mage::helper('schrackshipping/delivery')->getShippingMethod();
	        $quote->setIsPickup(false);
		}
		if (!$shippingMethod) {
			Mage::getSingleton('checkout/session')->addError($this->__('Could not determine delivery method.'));
			$this->_redirect('checkout/onepage/error');
			return;
		}

		$this->_setupQuote($quote, $customer, $shippingMethod);
		$this->_setupCheckout(Mage::getSingleton('checkout/session'), $customerSession->getLoggedInCustomer());
	}

	public function saveShippingAction() {
		if ($this->_expireAjax()) {
			return;
		}
		if ($this->getRequest()->isPost()) {
			$data = $this->getRequest()->getPost('shipping', array());
			$customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
			$result = $this->getOnepage()->saveShipping($data, $customerAddressId);

			if (!isset($result['error'])) {
				// Schracklive: skip shipping method for the app
				$result['goto_section'] = 'payment';
				$result['update_section'] = array(
					'name' => 'payment-method',
					'html' => $this->_getPaymentMethodsHtml()
				);
			}
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		}
	}

	protected function _getPickupShippingMethodForCustomer($customer) {
		$shippingMethod = null;
		$warehouseId = $customer->getSchrackPickup();
		if (!$warehouseId) {
			$warehouseId = Mage::helper('schrackcustomer')->getDefaultWarehouseIdForCustomer($customer);
		}
		if ($warehouseId) {
			$shippingMethod = Mage::helper('schrackshipping/pickup')->getShippingMethodFromWarehouseId($warehouseId);
		}
		return $shippingMethod;
	}

	protected function _redirectUrl($url) {
		$baseUrl = Mage::getModel('core/url')->getBaseUrl();
		$baseUrlLen = strlen($baseUrl);
		if (substr($url, 0, $baseUrlLen) == $baseUrl) {
			$urlPath = substr($url, $baseUrlLen);
			// @todo remove duplication
			if (preg_match('|^checkout/onepage/?|', $urlPath)) {
				$url = str_replace('checkout/onepage', 'mobile/onepage', $url);
			} elseif (!preg_match('|^mobile/onepage/?|', $urlPath)) {
				$url = $baseUrl.'checkout/onepage/error';
			}
		} else {
			$url = $baseUrl.'checkout/onepage/error';
		}
		$this->_redirectSet = true;
		return parent::_redirectUrl($url);
	}

	protected function _redirect($path, $arguments=array()) {
		// @todo remove duplication
		if (preg_match('|^checkout/onepage(/.*)?$|', $path, $matches)) {
			$path = 'mobile/onepage'.($matches[1] ? $matches[1] : '');
		} elseif (!preg_match('|^mobile/onepage/?|', $path)) {
			$path = 'checkout/onepage/error';
		}
		$this->_redirectSet = true;
		return parent::_redirect($path, $arguments);
	}

	protected function _redirectSuccess($defaultUrl) {
		Mage::getSingleton('checkout/session')->addError($this->__('Our bad. You should not have been sent there.'));
		$this->_redirectSet = true;
		return parent::_redirect('checkout/onepage/error', $arguments);
	}

	protected function _redirectError($defaultUrl) {
		$this->_redirectSet = true;
		return parent::_redirect('checkout/onepage/error', $arguments);
	}

	protected function _redirectReferer($defaultUrl=null) {
		Mage::getSingleton('checkout/session')->addError($this->__('Our bad. You should not have got here.'));
		$this->_redirectSet = true;
		return parent::_redirect('checkout/onepage/error', $arguments);
	}

	protected function _setupQuote($quote, $customer, $shippingMethod) {
		$b_address = $quote->getBillingAddress();
		$customerAddress = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
		$b_address->importCustomerAddress($customerAddress);
		$b_address->implodeStreetAddress();

		$s_address = $quote->getShippingAddress();
		$customerAddress = Mage::getModel('customer/address')->load($customer->getDefaultShipping());
		$s_address->importCustomerAddress($customerAddress);
		$s_address->implodeStreetAddress();

		$quote->getShippingAddress()->setShippingMethod($shippingMethod);

		$quote->collectTotals()->save();
	}

	protected function _setupCheckout($checkoutSession, $loggedInCustomer=null) {
		$checkoutSession->setStepData('billing', 'complete', true)
				->setStepData('shipping', 'allow', true)
				->setStepData('shipping', 'complete', true)
				->setStepData('shipping_method', 'allow', true)
				->setStepData('shipping_method', 'complete', true)
				->setStepData('payment', 'allow', true);

		$checkoutSession->setCheckoutState('shipping');
		$checkoutSession->setLoggedInCustomer($loggedInCustomer);
	}

}

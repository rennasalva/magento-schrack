<?php

require_once 'Mage/Customer/controllers/AccountController.php';
require_once Mage::getModuleDir('controllers', 'MageDeveloper_TYPO3connect') . DS . 'AccountController.php';

class Schracklive_SchrackCustomer_AccountController extends MageDeveloper_TYPO3connect_AccountController {

    const LOG_ACCEPT_OFFER = true;
    const LOG_ACCEPT_OFFER_FILE = 'accept_offer.log';
    protected $disableAjaxCheck           = false; // DEFAULT = false

/**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        // a brute-force protection here would be nice
        //$session = $this->_getSession();

        if(false && isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL'])) {
            // This is the case of successful login via SAP OCI
        } else {
            parent::preDispatch();
        }

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = $this->getRequest()->getActionName();
        $pattern = '/^(create|login|logoutSuccess|forgotpassword|forgotpasswordpost|confirm|confirmation|changeforgotten|checkuid|checkemaildoublette|checkcommonemail)/i';

        if ( ! preg_match($pattern, $action) ) {
            if ( $action == 'documentsDetailView' ) {
                // pass query string to login screen for Google tracing
                $currentUrl = Mage::helper('core/url')->getCurrentUrl();
                list($referrerUrl,$queryString) = explode('?',$currentUrl);
                if ( $queryString ) {
                    $queryString = str_replace('amp;', '', $queryString);
                    $loginUrl = Mage::getBaseUrl() . Mage_Customer_Helper_Data::ROUTE_ACCOUNT_LOGIN
                        . '/referer/' . base64_encode($referrerUrl) . '/?' . $queryString;
                    $this->_getSession()->authenticate($this, $loginUrl);
                } else {
                    $this->_getSession()->authenticate($this);
                }
            } else {
                $this->_getSession()->authenticate($this);
            }
        } else {
            $actionLC = strtolower($action);
            if ( $actionLC == 'checkuid' ) {
                $this->getRequest()->setActionName('checkuid');
            } else if ( $actionLC == 'checkemaildoublette' ) {
                $this->getRequest()->setActionName('checkemaildoublette');
            } else if ( $actionLC == 'checkcommonemail' ) {
                $this->getRequest()->setActionName('checkcommonemail');
            } else {
                $this->_getSession()->setNoReferer(true);
            }
        }
    }


    protected function _welcomeCustomer($customer, $isJustConfirmed = false)
    {
        $schrackCustomerType  = $customer->getSchrackCustomerType();
        $schrackProspectTypes = array('light' => 0, 'full' => 1);

        if ($isJustConfirmed && $customer->getGroupId() == Mage::getStoreConfig('schrack/shop/prospect_group')) {
            $prospectMessageContent = array();
            $currentSchrackProspectType = 'error';
            if (stristr($schrackCustomerType, 'light')) {
                $currentSchrackProspectType = 'light';
            }
            if (stristr($schrackCustomerType, 'full')) {
                $currentSchrackProspectType = 'full';
            }

            if ($currentSchrackProspectType == 'error') {
                Mage::log(date('Y-m-d H:i:s') . ': _welcomeCustomer: Customer is in Prospect Group, but has no valid customer prospect type assignment (schrack_customer_type = ' . $schrackCustomerType . ') -> (customer_entity id = ' . $customer->getId() . ')', null, '/prospects/prospect_err.log');
                //throw new Exception('Customer is in Prospect Group, but has no type assignment (schrack_customer_type = ' . $schrackCustomerType . ') -> (customer_entity id = ' . $customer->getId() . ')');
            } else {
                $prospectMessageContent['schrack_prospect_type']      = $schrackProspectTypes[$currentSchrackProspectType];
                $prospectMessageContent['email']                      = $customer->getEmail();
                $prospectMessageContent['prefix']                     = $customer->getPrefix();
                $prospectMessageContent['lastname']                   = $customer->getLastname();
                $prospectMessageContent['firstname']                  = $customer->getFirstname();
                $prospectMessageContent['schrack_newsletter']         = $customer->getSchrackNewsletter();
                $prospectMessageContent['schrack_wws_contact_number'] = $customer->getSchrackWwwsContactNumber();
                $prospectMessageContent['salutatory']                 = $customer->getSchrackSalutatory();
                $prospectMessageContent['gender']                     = $customer->getGender();
                $prospectMessageContent['schrack_mobile_phone']       = $customer->getSchrackMobilePhone();
                $prospectMessageContent['schrack_fax']                = $customer->getSchrackFax();
                $prospectMessageContent['schrack_telephone']          = $customer->getSchrackTelephone();

                $account                                                  = $customer->getAccount();
                $prospectMessageContent['vat_identification_number']      = $account->getVatIdentificationNumber();
                $prospectMessageContent['vat_local_number']               = $account->getVatLocalNumber();
                if (strlen($account->getCompanyRegistrationNumber()) > 14) {
                    $prospectMessageContent['company_registration_number'] = substr($account->getCompanyRegistrationNumber(), 0, 14);
                } else {
                    $prospectMessageContent['company_registration_number'] = $account->getCompanyRegistrationNumber();
                }
                $prospectMessageContent['schrack_advisor_principal_name'] = $account->getAdvisorPrincipalName();
                $prospectMessageContent['name2']                          = $account->getName2();
                $prospectMessageContent['name3']                          = $account->getName3();

                $billingAddress                                           = $account->getBillingAddress();
                $street                                                   = isset($billingAddress) ? $billingAddress->getStreet() : '';
                if ($account->getName1() != 'PROSLI') {
                    $prospectMessageContent['name1']    = $account->getName1();
                    $prospectMessageContent['street']   = $street[0];
                    $prospectMessageContent['postcode'] = isset($billingAddress) ? $billingAddress->getPostcode() : '';
                    $prospectMessageContent['city']     = isset($billingAddress) ? $billingAddress->getCity() : '';
                } else {
                    $prospectMessageContent['name1']    = '';
                    $prospectMessageContent['street']   = '';
                    $prospectMessageContent['postcode'] = '';
                    $prospectMessageContent['city']     = '';
                }

                $strBillingCountry                                        = isset($billingAddress)
                                                                          ? $billingAddress->getCountry()
                                                                          : strtoupper(Mage::getStoreConfig('schrack/general/country'));;
                $strBillingCountry                                        = str_replace('COM', '', $strBillingCountry);
                $prospectMessageContent['country_id']                     = $strBillingCountry;
                $prospectMessageContent['homepage']                       = $account->getHomepage();
                $prospectMessageContent['telephone_company']              = isset($billingAddress) ? $billingAddress->getTelephone() : '';
                $prospectMessageContent['fax_company']                    = isset($billingAddress) ? $billingAddress->getFax() : '';
                $prospectMessageContent['newsletter']                     = $account->getNewsletter();
                $prospectMessageContent['user_confirmed']                 = 1;
                $prospectMessageContent['wws_customer_id']                = $account->getWwsCustomerId();
                $prospectMessageContent['shop_language']                  = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(), 0 , 2));
                // Fix for Saudi Arabia:
                if (stristr($prospectMessageContent['shop_language'], 'AR')) $prospectMessageContent['shop_language'] = 'EN';
                $prospectMessageContent['account_type']                   = $account->getAccountType();
                $prospectMessageContent['enterprise_size']                = $account->getEnterpriseSize();
                $prospectMessageContent['rating']                         = $account->getRating();
                $prospectMessageContent['wws_branch_id']                  = $account->getWwwsBranchId();
                $prospectMessageContent['sales_area']                     = $account->getSalesArea();
                $prospectMessageContent['homepage']                       = $account->getHomapage();
                $prospectMessageContent['description']                    = $account->getDescription();
                $prospectMessageContent['schrack_department']             = $account->getInformation();
                $prospectMessageContent['company_prefix']                 = $account->getCompanyPrefix();
                $prospectMessageContent['currency_code']                  = Mage::app()->getStore()->getBaseCurrencyCode();

                $this->_getSession()->addSuccess(
                    $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
                );

                Mage::log(date('Y-m-d H:i:s') . ': _welcomeCustomer: Customer is Prospect (' . $schrackCustomerType . ') and has successfully confirmed (schrack_customer_type = ' . $schrackCustomerType . ') -> (customer_entity id = ' . $customer->getId() . ') -> (eMail = ' . $customer->getEmail() . ')', null, 'confirmations.log');

                if ($isJustConfirmed == true) {
                    $customer->sendNewAccountEmail('confirmed');
                } else {
                    $customer->sendNewAccountEmail('registered');
                }

                // Send message to S4Y about successful confirmation of the customer:
                $prospectMessageContent['prospect_source'] = 0;  // SHOP sends always 0 as source
                $prospect = Mage::getSingleton('crm/connector')->putProspect($prospectMessageContent);

                $successUrl = Mage::getUrl('*/*/index', array('_secure'=>true));
                if ($this->_getSession()->getBeforeAuthUrl()) {
                    $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
                }
                return $successUrl;
            }
        } else {
            $this->_getSession()->addSuccess(
                $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
            );

            Mage::log(date('Y-m-d H:i:s') . ': _welcomeCustomer: Customer has successfully confirmed: (customer_entity id = ' . $customer->getId() . ') -> (eMail = ' . $customer->getId() . ')', null, 'confirmations.log');

            if ($isJustConfirmed == true) {
                $customer->sendNewAccountEmail('confirmed');
            } else {
                $customer->sendNewAccountEmail('registered');
            }

            $successUrl = Mage::getUrl('*/*/index', array('_secure'=>true));
            if ($this->_getSession()->getBeforeAuthUrl()) {
                $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
            }
            return $successUrl;
        }
        Mage::log(date('Y-m-d H:i:s') . ': _welcomeCustomer: Customer has successfully confirmed (schrack_customer_type = ' . $schrackCustomerType . ') -> (customer_entity id = ' . $customer->getId() . ') -> (eMail = ' . $customer->getEmail() . ')', null, 'confirmations.log');
        $failedUrl = Mage::getUrl('*/*/*');
        return $failedUrl;
    }


    public function indexAction() {
        try {
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->_initLayoutMessages('catalog/session');

            $this->getLayout()->getBlock('content')->append(
                    $this->getLayout()->createBlock('customer/account_dashboard')
            );
            $this->getLayout()->getBlock('head')->setTitle($this->__('My Account'));

            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            $this->_redirect('/');
        }
    }

    /**
     * Customer login form page
     */
    public function loginAction()
    {
        $session = $this->_getSession();
        if ( $session->isLoggedIn() ) {
            $this->_loginPostRedirect();
            return;
        }
        //Mage::getSingleton('cms/page')->setIdentifier('home');
        $this->getResponse()->setHeader('Login-Required', 'true');
        $this->loadLayout();
        $beforeUrl = $session->getBeforeAuthUrl();
        if ( strpos($beforeUrl,'/customer/link/fetch') !== false ) {
            $session->addNotice($this->__("To download our tools such as Schrack Design, you need to log in to your user account. If you do not have a user account yet, you can create a new user account with 'Create Account'."));
        }
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    /**
     * Customer login form page
     */
    public function loginPopupAction()
    {
        $this->getResponse()->setHeader('Login-Required', 'true');
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }


    public function loginPostAction() {

        $hookUrl = $this->getRequest()->getParam('HOOK_URL');
        if ( empty($hookUrl) ) {
            $this->getRequest()->getPost('HOOK_URL');
        }
        // DLA 20160919: Potential security whole opened here for SAP OCI :-(
        if ( empty($hookUrl) && !$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        } //Nagarro : Added form key
        $ajaxErrors = array();
        $session = $this->_getSession();
        $session->unsetData('sapoci');

        if ($this->getRequest()->isAjax()) {
	        if ($this->_getSession()->isLoggedIn()) {
		        echo json_encode(array('status' => 'ok', 'messages' => array()));
		        die();
	        }
            $login = $this->getRequest()->getPost('login');
			if(empty($login))
				$login = $this->getRequest()->getParam('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);

                    // Unset delet-flag from former login (session):
                    $session->setData('delete_rememberme', false);
                    // Set "keep-me-logged-in" Flag to session data:
                    if (! $session->getData('rememberme')) {
                        $expire = time() + (intval(Mage::getStoreConfig( 'web/cookie/keep_me_logged_in_cookie_lifetime')));
                        $remembermeFlagFromUserChoice = $this->getRequest()->getParam('remembermeValue');

						if ($remembermeFlagFromUserChoice == 1) {
                            $session->setData('rememberme', true);
                            setcookie('remembermetrigger', 'enabled', $expire, '/');
                        } else {
                            $session->setData('delete_rememberme', true);
                            $session->setData('rememberme', false);
                            setcookie('remembermetrigger', 'disabled', $expire, '/');
                        }
                    }

                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $customer = Mage::getModel('customer/customer')
                                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                                ->loadByEmail($login['username']);
                            if (!$customer->getId()) {
                                try {
                                    list($redirectUrl, $token) = Mage::helper('schrackcustomer/loginswitch')->getRedirectDataByLoginData($login['username'], $login['password']);
                                    if ($redirectUrl) {
                                        echo json_encode(array('status' => 'ok', 'messages' => array(), 'redirect' => $redirectUrl . '/token/' . $token));
                                        die;
                                    } else {
                                        throw new Exception('Unable to redirect');
                                    }
                                } catch (Exception $ex) {
                                    Mage::logException($ex);
                                    $this->_getSession()->addError('Unable to authenticate');
                                }
                            }

                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $ajaxErrors[] = $message;
                    $session->getMessages(true);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $ajaxErrors[] = $this->__('Login and password are required.');
                $session->getMessages(true);
            }
        } else {
            if ($this->getRequest()->isPost()) {
                $login = $this->getRequest()->getPost('login');
                if(empty($login))
                    $login = $this->getRequest()->getParam('login');

                if (!empty($login['username']) && !empty($login['password'])) {
                    try {
                        $session->login($login['username'], $login['password']);

                        // Unset delet-flag from former login (session):
                        $session->setData('delete_rememberme', false);
                        // Set "keep-me-logged-in" Flag to session data:
                        if (! $session->getData('rememberme')) {
                            $remembermeFlagFromUserChoice = $this->getRequest()->getParam('remembermeValue');
                            $expire = time() + (intval(Mage::getStoreConfig( 'web/cookie/keep_me_logged_in_cookie_lifetime')));
                            if ($remembermeFlagFromUserChoice == 1) {
                                $session->setData('rememberme', true);
                                setcookie('remembermetrigger', 'enabled', $expire, '/');
                            } else {
                                $session->setData('delete_rememberme', true);
                                $session->setData('rememberme', false);
                                setcookie('remembermetrigger', 'disabled', $expire, '/');
                            }
                        }

                        if ($session->getCustomer()->getIsJustConfirmed()) {
                            $this->_welcomeCustomer($session->getCustomer(), true);
                        }
                        setcookie('schrackliveLogin', $session->getCustomer()->getId(), time()+60*60*24*30, '/');
                        $rv = $this->_redirectReferer();
                    } catch (Mage_Core_Exception $e) {
                        switch ($e->getCode()) {
                            case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                                $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                                $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                                $this->_redirect('customer/account/login');
                                break;
                            case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                                $customer = Mage::getModel('customer/customer')
                                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                                    ->loadByEmail($login['username']);
                                    $this->_redirect('customer/account/login');
                                if (!$customer->getId()) {
                                    try {
                                        list($redirectUrl, $token) = Mage::helper('schrackcustomer/loginswitch')->getRedirectDataByLoginData($login['username'], $login['password']);
                                        if ($redirectUrl) {
                                            return $this->_redirectUrl($redirectUrl . '/token/' . $token);
                                        }
                                    } catch (Exception $ex) {
                                        Mage::logException($ex);
                                        $this->_getSession()->addError('Unable to authenticate');
                                    }
                                }

                                $message = $e->getMessage();
                                break;
                            default:
                                $message = $e->getMessage();
                        }
                        $ajaxErrors[] = $message;
                        $session->addError($message);
                        $messagesDl = Mage::getSingleton('customer/session')->getMessages(); // ###
                        $session->setUsername($login['username']);
                    } catch (Exception $e) {
                        // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                    }
                }
            }

            $stay = $this->getRequest()->getParam('stay');

            if ($hookUrl) {
                // Route : SAP OCI
                $rv = parent::loginPostAction();
            } else {
                if ( ! $this->_getRefererUrl() || $this->_getRefererUrl() === Mage::app()->getStore()->getBaseUrl() ) {
                    if ( isset($stay) ) {
                        $rv = $this->_redirectReferer();
                    } else {
                        $rv = parent::loginPostAction();
                        // $this->_loginPostRedirect();
                    }
                }
            }
        }

        // Log into TYPO3
        if (!$ajaxErrors) {
            try {
                $userData = base64_encode(serialize(array(
                    'country' => Mage::getStoreConfig('schrack/general/country'),
                    'id' => $session->getCustomer()->getId(),
                    'group_id' => $session->getCustomer()->getGroupId(),
                    'customer_id' => $session->getCustomer()->getSchrackWwsCustomerId(),
                    'contact_id' => $session->getCustomer()->getSchrackWwsContactNumber(),
                    'crm_id' => $session->getCustomer()->getSchrackS4yId(),
                    'email' => $session->getCustomer()->getEmail(),
                    'first_name' => $session->getCustomer()->getFirstname(),
                    'last_name' => $session->getCustomer()->getLastname(),
                    'title' => $session->getCustomer()->getPrefix(),
                    'company' => ($session->getCustomer()->getAccount() ? $session->getCustomer()->getAccount()->getName() : null),
                    'phone' => $session->getCustomer()->getSchrackTelephone(),
                    'postcode' => ($session->getCustomer()->getPrimaryBillingAddress() ? $session->getCustomer()->getPrimaryBillingAddress()->getPostcode() : null),
                    'principal' => $session->getCustomer()->getSchrackUserPrincipalName(),
                    'ses_permanent' => (int)$session->getData('rememberme')
                )));
                /** @var $typo3helper Schracklive_Typo3_Helper_Data */
                $typo3helper = Mage::helper('typo3');
                $response = $typo3helper->getResponse(Mage::getStoreConfig('schrack/typo3/typo3url') .
                    '?userEID=get_session&data=' . urlencode($userData) . '&auth=' .
                    hash_hmac('sha256', $userData, Mage::getStoreConfig('schrack/typo3/hash_key')), 1
                );
                $responseStatus = $response->getStatus();
            } catch (Exception $e) {
                $response = null;
                $responseStatus = 500;
            }
            if ($responseStatus === 200) {
                $session->setData('t3-session-name', $response->getHeader('x-t3-name'));
                $session->setData('t3-session-id', $response->getHeader('x-t3-session'));
                $session->setData('t3-session-last-refresh', time());
                if ($session->getData('rememberme')) {
                    $t3lifetime = Mage::getStoreConfig('web/cookie/keep_me_logged_in_cookie_lifetime');
                } else {
                    $t3lifetime = Mage::getStoreConfig('web/cookie/cookie_lifetime');
                }
                $session->setData('t3-session-lifetime', $t3lifetime);
                // Set cookie directly. Magento otherwise tries to refresh the cookie with value from request when rememberme is set.
                setcookie($response->getHeader('x-t3-name'), $response->getHeader('x-t3-session'), time() + $t3lifetime, '/');
            }
        }

        $trackingHelper = Mage::helper('schrackcustomer/tracking');

        if ($trackingHelper->maySetCookie() && $this->_getSession()->isLoggedIn()) {
            $trackingSessionId = $trackingHelper->createTrackingSessionId();
            $schrackWwsCustomerId = $this->_getSession()->getCustomer()->getSchrackWwsCustomerId();
            $schrackWwsContactNumber = $this->_getSession()->getCustomer()->getSchrackWwsContactNumber();
            $this->_updateCustomerTracking($trackingSessionId, $schrackWwsCustomerId, $schrackWwsContactNumber);
        }

        if ($this->_getSession()->isLoggedIn()) {
            $schrackWwsCustomerId = $this->_getSession()->getCustomer()->getSchrackWwsCustomerId();
            $customer = $this->_getSession()->getCustomer();
            $quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
            if ($quote) {
                $quoteWwsCustomerId = $quote->getSchrackWwsCustomerId();
            } else {
                $quote = Mage::getModel('checkout/cart')->getQuote();
                if ($quote) {
                    $quoteWwsCustomerId = $quote->getSchrackWwsCustomerId();
                }
            }

            if ($quoteWwsCustomerId) {
                // Remove wws_order_id from cart, if neccessary:
                if ($schrackWwsCustomerId && $quoteWwsCustomerId != $schrackWwsCustomerId) {
                    // Cleanup Cart, if customer has a valid wws-id and a quote with nobody-wws-id, just remove wws-order-id from quote:
                    $quote->setSchrackWwsCustomerId('');
                    $quote->setSchrackWwsOrderNumber('');
                    $quote->save();
                }
            }
        }

        if (!($this->getRequest()->isAjax())) {
            return $rv;
        } else {
            if (count($ajaxErrors)) {
                echo json_encode(array('status' => 'error', 'messages' => $ajaxErrors));
            } else {
                echo json_encode(array('status' => 'ok', 'messages' => array()));
            }
            die;
        }
    }

    public function loginLogAuthAction() {
            // data: { 'system' : system, 'username': username, 'responseText' : responseText }
        $system = $this->getRequest()->getParam('system');
        $username = $this->getRequest()->getParam('username');
        $responseText = $this->getRequest()->getParam('responseText');
        Mage::log("loginLogAuth: system='$system', username='$username', responseText='$responseText'", null, 'loginauth.log');
        die;
    }

    private function _updateCustomerTracking($sessionId, $schrackWwsCustomerId, $schrackWwsContactNumber) {
        $tracking = Mage::getModel('schrackcustomer/tracking');
        $tracking->setCustomerIdToSession($sessionId, $schrackWwsCustomerId, $schrackWwsContactNumber);
    }

    public function loginByTokenAction() {
        try {
            if ($this->getRequest()->isGet()) {
                $token =  $this->getRequest()->getParam('token');
                if (strlen($token)) {
                    Mage::helper('schrackcustomer/loginswitch')->loginByToken($token);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError('Unable to authenticate');
        }

        return $this->_redirect('*/*');
    }

    /**
     * Define target URL and redirect customer after logging in
     * copied from parent and amended to accomodate redirection to typo3
     */
    protected function _loginPostRedirect() // from parent
    {
        $this->_getSession()->setData('schracklive_post_login', true);
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            //
            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDefaultAfterLoginUrl());
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        $referer = Mage::helper('core')->urlDecode($referer);
                        if ( Mage::helper('schrackcore/url')->isUrlServerLocal($referer) ) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl(Mage::helper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() == Mage::helper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl() && $session->getBeforeAuthUrl() ) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        // DLA, 2015-07-28: this is a bloody hack to ensure, that a redirect will perform also when a 2nd login request follows up...
        if ( $session->getStoredRedirect() ) {
            $this->_redirectUrl($session->getStoredRedirect());
            $session->unsStoredRedirect();
        } else {
            $redir = $session->getBeforeAuthUrl(true);
            $session->setStoredRedirect($redir);
            $this->_redirectUrl($redir);
        }

        // Redirect must be patched for Newsletter:
        $evaluateSource = $this->getRequest()->getParam('utm_source');
        if ($evaluateSource && $evaluateSource == 'Newsletter') {
            $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
            if ($referer) {
                $referer = Mage::helper('core')->urlDecode($referer);
                if ( Mage::helper('schrackcore/url')->isUrlServerLocal($referer) ) {
                    $session->setBeforeAuthUrl($referer);
                    $this->_redirectUrl($referer);
                }
            }
        }
    }

	public function changePasswordPostAction () {
        $redirectParent = 'customer/account/editpassword';
        $redirectThis   = '*/*/*';

		if (    ! $this->_validateFormKey()
		     || ! $this->getRequest()->isPost()
             || ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
			return $this->_redirect($redirectParent);
		}

        $customer = Mage::getModel('schrackcustomer/customer')
                    ->load($this->_getSession()->getCustomerId())
                    ->setWebsiteId($this->_getSession()->getCustomer()->getWebsiteId());
        $currPass = $this->getRequest()->getPost('current_password');
        $newPass = $this->getRequest()->getPost('password');
        $confPass = $this->getRequest()->getPost('confirmation');

        if ( empty($currPass) || empty($newPass) || empty($confPass) ) {
            $errors[] = $this->__('Password fields can\'t be empty.');
        }

        if ( $msg = Mage::helper('customer')->checkNewPasswordReturningErrorMessage($newPass,$confPass) ) {
            $errors[] = $msg;
        }

        if ( empty($errors) ) {
            $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
            if ( strpos($oldPass, ':') ) {
                list($_salt, $salt) = explode(':', $oldPass);
            } else {
                $salt = false;
            }

            if ( $customer->hashPassword($currPass, $salt) == $oldPass ) {
                $customer->setPassword($newPass);
                $customer->setPasswordConfirmation($confPass);
            } else {
                $errors[] = $this->__('Invalid current password');
            }
            $customerErrors = $customer->validate();
            if ( is_array($customerErrors) ) {
                $errors = array_merge($errors, $customerErrors);
            }
        }
        if ( ! empty($errors) ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ( $errors as $message ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect($redirectThis);
            return $this;
        }
        try {
            $customer->save();
        } catch ( Exception $e ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Can\'t save customer'));
            return $this->_redirect($redirectThis);
        }
        $this->_getSession()->addSuccess($this->__('Your new password has been saved successfully. Note: please mind that you could possibly have to log in with the new password in other online tools aswell!'));
        return $this->_redirect($redirectParent);
    }

	public function setDefaultPaymentMethodPostAction () {
        $redirectParent = 'customer/account/editpayment';
        $redirectThis   = '*/*/*';
		if (    ! $this->_validateFormKey()
		     || ! $this->getRequest()->isPost()
             || ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
			return $this->_redirect($redirectParent);
		}

        $customer = Mage::getModel('schrackcustomer/customer')
                    ->load($this->_getSession()->getCustomerId())
                    ->setWebsiteId($this->_getSession()->getCustomer()->getWebsiteId());

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $allowedPaymentMethods = array('checkmo', 'schrackcash', 'schrackcod', 'schrackpo', 'paypal_standard', 'payunitycw_mastercard', 'payunitycw_visa');

        $selectedDefaultPaymentMethodPickup   = $this->getRequest()->getParam('default_payment_method_pickup');
        $selectedDefaultPaymentMethodShipping = $this->getRequest()->getParam('default_payment_method_shipping');

        $writeConnection->beginTransaction();
        try {
            if ( $selectedDefaultPaymentMethodPickup && in_array($selectedDefaultPaymentMethodPickup, $allowedPaymentMethods) ) {
                $query = "UPDATE customer_entity SET schrack_default_payment_pickup = '" . $selectedDefaultPaymentMethodPickup . "' WHERE entity_id = " . $customer->getId();
                $writeConnection->query($query);
            }

            if ( $selectedDefaultPaymentMethodShipping && in_array($selectedDefaultPaymentMethodShipping, $allowedPaymentMethods) ) {
                $query = "UPDATE customer_entity SET schrack_default_payment_shipping = '" . $selectedDefaultPaymentMethodShipping . "' WHERE entity_id = " . $customer->getId();
                $writeConnection->query($query);
            }
            $writeConnection->commit();
            $this->_getSession()->setCustomer($customer)->addSuccess($this->__('Default payment methods set successfully!'));
        } catch ( Exception $e ) {
            $writeConnection->rollBack();
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Can\'t save default payment methods!'));
            return $this->_redirect($redirectThis);
        }

        return $this->_redirect($redirectParent);
    }

	public function setDefaultPickupLocationPostAction () {
        $redirectParent = 'customer/account/editpickup';
        $redirectThis   = '*/*/*';
        $attrName       = 'schrack_pickup';
		if (    ! $this->_validateFormKey()
		     || ! $this->getRequest()->isPost()
             || ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
			return $this->_redirect($redirectParent);
		}

        $customer = Mage::getModel('schrackcustomer/customer')
                    ->load($this->_getSession()->getCustomerId())
                    ->setWebsiteId($this->_getSession()->getCustomer()->getWebsiteId());

        $data = $this->_filterPostData($this->getRequest()->getPost());
        if ( ! isset($data[$attrName]) || ! is_numeric($data[$attrName]) ) {
            $this->_getSession()->addError('Missing or invalid parameter schrack_pickup!');

        }
        try {
            $customer->setData($attrName, $data[$attrName]);
            // EAV attribute will not be saved if it wasn't created with entity:
            $customer->getResource()->saveAttribute($customer, $attrName);
            $customer->save();
            // EAV attribute will not be saved if it wasn't created with entity:
            $customer->getResource()->saveAttribute($customer, $attrName);
            $this->_getSession()->setCustomer($customer)
                ->addSuccess($this->__('Default pickup store was successfully saved!'));
        } catch ( Exception $e ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Can\'t save default pickup store!!'));
            return $this->_redirect($redirectThis);
        }

        return $this->_redirect($redirectParent);
    }

	public function editPostAction() {
		if (!$this->_validateFormKey()) {
			return $this->_redirect('*/*/edit');
		}

		if ($this->getRequest()->isPost()) {
			$customer = Mage::getModel('schrackcustomer/customer')
			            ->load($this->_getSession()->getCustomerId())
					    ->setWebsiteId($this->_getSession()->getCustomer()->getWebsiteId());

			$fields = Mage::getConfig()->getFieldset('customer_account');
			$data = $this->_filterPostData($this->getRequest()->getPost());

            $dataTemp = array();
            foreach($data as $key => $value) {
                $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                $dataTemp[$key] = $value;
            }
            $data = array();
            $data = $dataTemp;

			foreach ($fields as $code => $node) {
				if ($node->is('update') && isset($data[$code])) {
					$customer->setData($code, $data[$code]);
				}
			}

            if (isset($data['schrack_salutatory'])) {
                $data['schrack_salutatory'] = str_replace(' undefined', '', $data['schrack_salutatory']);
            }

			Mage::helper('schrackcustomer/phone')->setPhoneNumbers($data, $customer);
            $errors = array();
			$customerErrors = $customer->validateExtra();
			if (is_array($customerErrors)) {
				$errors = array_merge($customerErrors, $errors);
			}
			$errors = array_merge($errors, Mage::helper('schrackcustomer/phone')->validatePhonenumbers($data));

			/**
			 * we would like to preserver the existing group id
			 */
			if ($this->_getSession()->getCustomerGroupId()) {
				$customer->setGroupId($this->_getSession()->getCustomerGroupId());
			}

            $customerErrors = $customer->validate();
            if (is_array($customerErrors)) {
                $errors = array_merge($errors, $customerErrors);
            }
			if (!empty($errors)) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
				foreach ($errors as $message) {
					$this->_getSession()->addError($message);
				}
				$this->_redirect('*/*/edit');
				return $this;
			}

			try {
                Mage::getSingleton('core/session')->setUserModificationAction('contact changed himself');
                $customer->save();
				$this->_getSession()->setCustomer($customer)
						->addSuccess($this->__('Account information was successfully saved'));

				$this->_redirect('customer/account/edit');
				return;
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Schracklive_SchrackCustomer_EmailNotUniqueException $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($this->__('Customer email already exists'));
			} catch (Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Can\'t save customer'));
			}
		}

		$this->_redirect('*/*/edit');
	}

    public function actAsUserAction() {
        $this->loadLayout();
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ( !Mage::helper('schrackcustomer')->mayActAsUser($customer) ) {
                throw new Exception('Access denied.');
            }
        } catch ( Exception $e ) {
            Mage::logException($e);
            Mage::getSingleton('customer/session')->addError($this->__($e->getMessage()));
            $this->_redirect('*/*');
        }
        $this->renderLayout();

    }
    public function actAsUserPostAction() {
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            Mage::log($customer->getEmail(), null, "act_as_a_customer.log");
            if ( !$this->_validateFormKey() || !Mage::helper('schrackcustomer')->mayActAsUser($customer) ) {
                throw new Exception('Access denied.');
            }
            $session = Mage::getSingleton('customer/session');
            if ( $session->isLoggedIn() ) {
                // check whether user is a system user that may do "sudo" to other customers
                $customer = $session->getCustomer();
                $reqCustomerId = $this->getRequest()->getParam('customer_id');
                if(!$reqCustomerId){
                    $reqCustomerId = $this->getRequest()->getParam('aac_customer_id');
                }
                if ((int) $customer->getGroupId() === Schracklive_SchrackCustomer_Model_Customer::CUSTOMER_SUDO_GROUP_ID && isset($reqCustomerId)) {
                    $loggedInCustomer = $session->getCustomer();
                    $contact = Mage::helper('account')->getSystemContactByWwsCustomerId($reqCustomerId);
                    if ( !is_null($contact) && $contact->getId() ) {
                        $customer = $contact;
                        Mage::log($customer->getEmail(), null, "act_as_a_customer.log");
                        $session->setLoggedInCustomer($loggedInCustomer); // i.e., the logged-in customer, as opposed to the customer we're acting on-behalf-of in case of a "sudo"
                        // so, if c.friedl@at.schrac k.lan does something in the name of cust. 777777,
                        // then c.friedl@at.schrack.lan is the loggedInCustomer

                        // clearing everything we have:
                        foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){
                            Mage::getSingleton('checkout/cart')->removeItem($item->getId());
                        }
                        Mage::getSingleton('checkout/cart')->save();
                        Mage::getSingleton('checkout/session')->getQuote()->delete();
                        Mage::getSingleton('checkout/session')->clear();
                    } else {
                        throw new Exception('Access denied.');
                    }
                }
                $session->setCustomerAsLoggedIn($customer);
                if ($loggedInCustomer->getSchrackWwsCustomerId() != $customer->getSchrackWwsCustomerId()) {
                    Mage::log('Customer ' . $loggedInCustomer->getSchrackWwsCustomerId() . ' (' . $loggedInCustomer->getEmail()
                    . ') will act as customer ' . $customer->getSchrackWwsCustomerId() . ' (' . $customer->getEmail() . ')', null, 'act_as_user.log');
                }
                $session->renewSession();
            }
        } catch ( Exception $e ) {
            Mage::logException($e);
            Mage::getSingleton('customer/session')->addError($this->__($e->getMessage()));
        }
        return $this->_redirect('*/*/documentsDetailsearch');
    }

    public function unactAsUserAction() {
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $loggedInCustomer = $session->getLoggedInCustomer();
        $session->setData("real_user_email", '');
        if ( $customer && $customer->getId() && $loggedInCustomer && $loggedInCustomer->getId() ) {
            $session->setCustomer($loggedInCustomer);
            $session->setLoggedInCustomer(null);
        }

        return $this->_redirect('*/*');
    }


    public function validateActAsACustomerAction() {
        $realUserEmail = $this->getRequest()->getParam('user_email');
        $systemcontactEmail = $this->getRequest()->getParam('system_contact_email');

        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $loggedInCustomer = $session->getLoggedInCustomer();

        // Check, if user is logged in as a systemcontact:
        if ($customer && $customer->getEmail() && stristr($customer->getEmail(), 'live.schrack.com')) {
            if ($loggedInCustomer && $loggedInCustomer->getEmail()
                && $realUserEmail == $loggedInCustomer->getEmail() && $systemcontactEmail == $customer->getEmail()) {
                    echo json_encode(array('result' => "success"));
                } else {
                    echo json_encode(array('result' => "failed"));
                }
        } else {
            echo json_encode(array('result' => "failed"));
        }
        die();
    }


	/**
	 * Create customer account action
	 */
	public function createPostAction() {
	    $errUrl = null;
        $account = null;
        $errUrl = null;
		$session = $this->_getSession();
		$session->setData("had_error", false);
		if ($session->isLoggedIn()) {
			$this->_redirect('*/*/');
			return;
		}
                if (!$this->_validateFormKey()) {
                    $this->_redirectError($errUrl);
                    return;
                } //Nagarro : Added form key
		if ($this->getRequest()->isPost()) {
            $postDataFromForm = $this->getRequest()->getPost();
		    if(isset($postDataFromForm['request-type']) && $postDataFromForm['request-type'] == 'selfRegistration') {
		        $this->sendSelfRegistrationAction($postDataFromForm);
                return;
		    }
			$errors = array();

		    if ( $postDataFromForm['postcode'] > '' ) {
                Mage::log(__FILE__ . ':' . __LINE__,null,'count_address_modifications.log');
            }

            if ( !defined('DEBUG') ) {
                // Check VAT first
                if ((Mage::getStoreConfig('schrack/vatcheck/enabled') && Mage::getStoreConfig('schrack/vatcheck/required'))
                    || (!Mage::getStoreConfig('schrack/vatcheck/required') && $this->getRequest()->getPost('vat_identification_number'))
                ) {
                    $vatHelper = Mage::helper('account/vat');
                    $vat = $this->getRequest()->getPost('vat_identification_number');
                    if ($vatHelper->checkVat($vat)) {
                        if ( in_array($vatHelper->getLastCheckType(), array(Schracklive_Account_Helper_Vat::VAT_CHECKTYPE_STRUCTURE,
                            Schracklive_Account_Helper_Vat::VAT_CHECKTYPE_CHECKSUM)) ) {
                            // "soft" test succeeded after "hard test" server was unreachable
                            $this->_sendVatSoftCheckEmail($this->getRequest()->getParams());
                        }
                    } else {
                        $errors[] = $this->__($vatHelper->getLastResult());
                    }
                    if ($this->getRequest()->getPost('user_selected_country') && $vat) {
                        $userSelectedCountryID = $this->getRequest()->getPost('user_selected_country');
                        if ($userSelectedCountryID != strtoupper(substr($vat, 0, 2))) {
                            $errors[] = $this->__('VAT identification number doesn\'t match country of billing adress.') . ' (1)';
                        }
                    }
                    if (!$vatHelper->checkLastCountryCode()) {
                        $errors[] = $this->__('VAT identification number doesn\'t match country of billing adress.') . ' (2)';
                    }
                    if ( $vatHelper->vatExists($vat) ) {
                        $errors[] = $this->__('VAT identification number already exists.');
                    }
                }
            }

			// Check agreement
			if (!$this->getRequest()->getPost('agreement')) {
				$errors[] = $this->__('Please agree to all Terms and Conditions to create the account.');
			}

			// Fetch post data and check phone formats
			$postData = $this->getRequest()->getPost();
			$errors = array_merge($errors, Mage::helper('schrackcustomer/phone')->validatePhonenumbers($postData));

			/** @var $accountHelper Schracklive_Account_Helper_Data */
			$accountHelper = Mage::helper('account');

			// Split form data for processing
			if (empty($errors)) {
                $branch = '';
			    if (is_array($postData) && array_key_exists(($postData['schrack_pickup']) && $postData['schrack_pickup'] != '')) {
				    $branch = Mage::helper('branch')->findBranch($postData['schrack_pickup'])->getBranchId();
                }
				if (!$branch) {
					$branch = Mage::getStoreConfig('schrack/general/branch');
				}

				// Merge in fixed data
				$postData['wws_branch_id'] = $branch;
				$postData['schrack_wws_branch_id'] = $postData['wws_branch_id'];
                if ( ! Mage::getStoreConfig('schrack/vatcheck/contry_selectable') ) {
                    $postData['country_id'] = Mage::getStoreConfig('general/country/default');
                }
                if ($postData['schrack_telephone'] == '+') $postData['schrack_telephone'] = '';
				$postData['schrack_telephone'] = $postData['schrack_telephone'];
				$postData['telephone'] = $postData['schrack_telephone'];
                if ($postData['schrack_fax'] == '+') $postData['schrack_fax'] = '';
				$postData['schrack_fax'] = $postData['schrack_fax'];
				$postData['fax'] = $postData['schrack_fax'];
                if ($postData['schrack_mobile_phone'] == '+') $postData['schrack_mobile_phone'] = '';
				$postData['schrack_mobile_phone'] = $postData['schrack_mobile_phone'];
				$postData['schrack_advisor_principal_name'] = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
				$postData['advisor_principal_name'] = $postData['schrack_advisor_principal_name'];
				$postData['schrack_acl_role_id'] = Mage::helper('schrack/acl')->getAdminRoleId();
				list($accountData, $customerData, $addressData) = $accountHelper->splitModelData($postData);
			}

			/*
			 * Create Account
			 */
			if (empty($errors)) {
				try {
					$postData['prefix'] = $this->__('Company');
					$account = $accountHelper->updateOrCreateAccount(null, $postData);
				} catch (Schracklive_Account_Exception $e) {
					if ($e->getCode() == Schracklive_Account_Exception::VALIDATION_ERROR) {
						foreach ($e->getMessages() as $eMsg) {
							$errors[] = $eMsg->getText();
						}
					} else {
						throw $e;
					}
				}
				if (!$account->getId()) {
					$errors = array($this->__('Error saving account'));
				}
			}

			/*
			 * Create Customer
			 */
			if (empty($errors)) {
				/** @var $customer Schracklive_SchrackCustomer_Model_Customer */
				$customer = Mage::getModel('customer/customer')->setId(null);
				$customer->setData($customerData);
				$customer->setSchrackAccountId($account->getId());
				$customer->setSchrackMainContact(true);
				$customer->setGroupId(Mage::getStoreConfig('schrack/shop/prospect_group'));
				$customer->setSchrackWwsContactNumber(0);
				$customerErrors = $customer->validate();
				if (is_array($customerErrors)) {
					$errors = array_merge($customerErrors, $errors);
				}
				$customerErrors = $customer->validateExtra();
				if (is_array($customerErrors)) {
					$errors = array_merge($customerErrors, $errors);
				}
			}

			try {
				$validationResult = count($errors) == 0;

				if (true === $validationResult) {
					$systemContact = $accountHelper->updateOrCreateSystemContact($account, $postData);
					$customer->save();

					if ($customer->isConfirmationRequired()) {
						$customer->sendNewAccountEmail('confirmation', $session->getBeforeAuthUrl());
						$session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));
						$this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure' => true)));
						return;
					} else {
						$session->setCustomerAsLoggedIn($customer);
						$url = $this->_welcomeCustomer($customer);
						$this->_redirectSuccess($url);
						return;
					}
				} else {
					$session->setCustomerFormData($this->getRequest()->getPost());
					if (is_array($errors)) {
						foreach ($errors as $errorMessage) {
							$session->addError($errorMessage);
						}
					} else {
						$session->addError($this->__('Invalid customer data'));
					}
				}
			} catch (Mage_Core_Exception $e) {
				$session->setCustomerFormData($this->getRequest()->getPost());
				if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
					$url = Mage::getUrl('customer/account/forgotpassword');
					$message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
					$session->setEscapeMessages(false);
				} else {
					$message = $e->getMessage();
				}
				$session->addError($message);
			} catch (Exception $e) {
				$session->setCustomerFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Cannot save the customer.'));
			}
		}

		$session->setData("had_error", true);
		if ($account && is_object($account)) {
			$account->delete();
		}
		$this->_redirectError(Mage::getUrl('*/*/create', array('_secure' => true)));
	}


    /**
     * Customer logout action
     */
    public function logoutAction()
    {
        $session = $this->_getSession();

        $customer = $session->getCustomer();
        $loggedInCustomer = $session->getLoggedInCustomer();
        if ( $customer && $customer->getId() && $loggedInCustomer && $loggedInCustomer->getId() ) {
            $session->setCustomer($loggedInCustomer);
            $session->setLoggedInCustomer(null);
        }

        $url = Mage::getUrl();
        $session->logout()->setBeforeAuthUrl($url);

        $session->setData('rememberme', false);
        $session->setData('delete_rememberme', true);

        setcookie("keepmeloggedin", "", time()-3600, '/');

        setcookie('schrackliveLogin', '', time()-3600, '/');

        setcookie('remembermetrigger', '', time()-3600, '/');

        $typoHomePageUrl = Mage::getStoreConfig('schrack/typo3/typo3url');
        // Log out of TYPO3
        if ($session->getData('t3-session-id')) {
            try {
                $dummyData = base64_encode(random_bytes(30));
                /** @var $typo3helper Schracklive_Typo3_Helper_Data */
                $typo3helper = Mage::helper('typo3');
                $typo3helper->getResponse($typoHomePageUrl .
                    '?userEID=end_session&data=' . urlencode($dummyData) .
                    '&auth=' . hash_hmac('sha256', $dummyData, Mage::getStoreConfig('schrack/typo3/hash_key')),
                    1,
                    array($session->getData('t3-session-name') . '=' . $session->getData('t3-session-id'))
                );
            } catch (Exception $e) {}
            setcookie($session->getData('t3-session-name'), "", time()-3600, '/');
            $session->unsetData('t3-session-name');
            $session->unsetData('t3-session-id');
            $session->unsetData('t3-session-lifetime');
            $session->unsetData('t3-session-last-refresh');
        }

        // Added by Nagarro team to redirect customer on Typo or Website Home Page
        $this->_redirectUrl($typoHomePageUrl);
    }

	/**
	 * Send confirmation link to specified email
	 */
	public function confirmationAction() {
		$customer = Mage::getModel('customer/customer');
		if ($this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/*/');
			return;
		}

		// try to confirm by email
		$email = $this->getRequest()->getPost('email');
		if ($email) {
			try {
				$customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
				if (!$customer->getId()) {
					throw new Exception('');
				}
				if ($customer->getConfirmation()) {

					// schrack4you - new password as old is lost for the customer
					$newPassword = $customer->generatePassword();
					$customer->changePassword($newPassword);

					$customer->sendNewAccountEmail('confirmation');
					$this->_getSession()->addSuccess($this->__('Please, check your email for confirmation key.'));
				} else {
					$this->_getSession()->addSuccess($this->__('This email does not require confirmation.'));
				}
				$this->_getSession()->setUsername($email);
				$this->_redirectSuccess(Mage::getUrl('*/*/*', array('_secure' => true)));
			} catch (Exception $e) {
				$this->_getSession()->addException($e, $this->__('Wrong email.'));
				$this->_redirectError(Mage::getUrl('*/*/*', array('email' => $email, '_secure' => true)));
			}
			return;
		}

		// output form
		$this->loadLayout();

		$this->getLayout()->getBlock('accountConfirmation')
				->setEmail($this->getRequest()->getParam('email', $email));

		$this->_initLayoutMessages('customer/session');
		$this->renderLayout();
	}

	/**
	 * Forgot customer password action
	 */
	public function forgotPasswordPostAction() {
		$email = (string) $this->getRequest()->getPost('email');    // Nagarro added: email type casting to string type

        $emailVerifyExpression = false;
		if ($email) {
            $emailVerifyExpression = (bool) preg_match('/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/', trim($email) );

            if (!$emailVerifyExpression) {
                    $this->_getSession()->setForgottenEmail($email);
                    $this->_getSession()->addError($this->__('Invalid email address.'));
                    $this->getResponse()->setRedirect(Mage::getUrl('*/*/forgotpassword'));
                    return;
            }
			$customer = Mage::getModel('customer/customer')
					->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
					->loadByEmail($email);

			if ($customer->getId()) {
				try {
					if ($customer->getConfirmation() && $customer->isConfirmationRequired()) {
						$value = Mage::helper('customer')->getEmailConfirmationUrl($email);
						$message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);

						$this->_getSession()->addError($message);
						$this->_getSession()->setUsername($email);
					} else {
					    $customer->setSchrackChangepwToken($customer->getRandomConfirmationKey());
					    $customer->save();
						$customer->sendResetPasswordEmail();
						$this->_getSession()->addSuccess($this->__('An email with a link to reset your password has been sent.'));
					}

					$this->getResponse()->setRedirect(Mage::getUrl('*/*/forgotpassword'));
					return;
				} catch (Exception $e) {
					$this->_getSession()->addError($e->getMessage());
				}
			} else {
				$this->_getSession()->addError($this->__('This email address was not found in our records.'));
				$this->_getSession()->setForgottenEmail($email);
			}
		} else {
			$this->_getSession()->addError($this->__('Please enter your email.'));
			$this->getResponse()->setRedirect(Mage::getUrl('*/*/forgotPassword'));
			return;
		}

		$this->getResponse()->setRedirect(Mage::getUrl('*/*/forgotpassword'));
	}


    /**
     * NOTE: This code stays in here as NOTLÖSUNG until I have gotten the phpunit tests to run... c.friedl
     */
    public function testgeoipAction() {

        $geoIP = Mage::getModel('geoip/country');

        if (!$geoIP->isInBackStage($_SERVER['REMOTE_ADDR'])) {
            $this->_getSession()->addError($this->__('This action is not valid.'));
            return $this->_redirect('*/*');
        }
        header('Content-type', 'text/plain');

        echo "Config setting: " . Mage::getStoreConfig('schrack/general/redirectGeoIP'). "\n";

        echo "request: ";
        var_dump($_REQUEST);
        echo "<br/>\n";
        echo "server: ";
        var_dump($_SERVER);
        echo "<br/>\n";
        echo "<br/>=========================<br/>\n";

        // try to trigger the country_sorry redirection on live machine @ :

        // .at -> .de, verboten
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '77.119.1.182',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        //$_SERVER['backstage'] = 'lsa5fej';
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);


        // .it -> .de, sollte erlaubt sein
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '77.73.57.78',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        //$_SERVER['backstage'] = 'lsa5fej';
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        // .it -> .com, sollte erlaubt sein
        $server = array(
            'SERVER_NAME' => 'www.schrack.com',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '77.73.57.78',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        //$_SERVER['backstage'] = 'lsa5fej';
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);


        // country matches, no redirect
        $server = array(
            'SERVER_NAME' => 'www.schrack.at',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        // country does not match, redirect
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);


        // robots.txt, sitemap.xml, crawler: no redirect
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/robots.txt',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/sitemap.xml',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Googlebot'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        // cannot determine country
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '192.168.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        // backstage - negative
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '194.107.228.12',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);
        assert($geoIP->isInBackstage('194.107.228.12') === false);

        // backstage
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '10.31.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);
        assert($geoIP->isInBackstage('10.31.0.1') === true);
        assert($geoIP->isInBackstage('10.32.0.1') === false);

        // backstage
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/testit/',
            'REMOTE_ADDR' => '194.228.101.178',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        // paypal
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/paypal',
            'REMOTE_ADDR' => '194.228.101.178',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);



        $geoIP = Mage::getModel('geoip/country');
        var_dump($geoIP->getCountryByIP('82.146.122.8'));
        // .be, no redirection
        $server = array(
            'SERVER_NAME' => 'www.schrack.at',
            'REQUEST_URI' => '/shop/customer/acount/login/',
            'REMOTE_ADDR' => '82.146.122.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);

        $geoIP = Mage::getModel('geoip/country');
        var_dump($geoIP->getCountryByIP('82.146.122.8'));

        // .be, with redirection (trying to access .de)
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '82.146.122.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);

        var_dump($geoIP->getRedirectUrl());

        assert(preg_match('#^http://www.schrack.be/#', $geoIP->getRedirectUrl()));

        // overriden by backdoor/assumedIpCountry, should redirect to .sk
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '194.228.101.178',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false, 'SK');
        assert($geoIP->getNeedsRedirect() === true);

        var_dump($geoIP->getRedirectUrl());

        assert(preg_match('#^http://www.schrack.sk/#', $geoIP->getRedirectUrl()));


        // .be, with redirection, assumedIpCountry is set but ip is not in backdoor, so redirection should occur to original country (.be)
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '82.146.122.8',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false, 'SK');
        assert($geoIP->getNeedsRedirect() === true);

        var_dump($geoIP->getRedirectUrl());

        assert(preg_match('#^http://www.schrack.be/#', $geoIP->getRedirectUrl()));


        // .at ip tries to connect to .com, should not?? be redirected to .at
        $server = array(
            'SERVER_NAME' => 'www.schrack.com',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '194.116.243.20',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === false);


        // .sk ip tries to connect to .de, that should redirect
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '93.184.77.53',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);
        assert(preg_match('#^http://www.schrack.sk/#', $geoIP->getRedirectUrl()));


         // CPF to connect to .de from smartphone, that should redirect
        $server = array(
            'SERVER_NAME' => 'www.schrack-technik.de',
            'REQUEST_URI' => '/shop/test/',
            'REMOTE_ADDR' => '77.119.2.225',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.3'
        );
        $geoIP = Mage::getModel('geoip/country');
        $geoIP->determineRedirection($server['SERVER_NAME'], $server['REQUEST_URI'], $server['REMOTE_ADDR'], $server['HTTP_USER_AGENT'], false);
        assert($geoIP->getNeedsRedirect() === true);
        assert(preg_match('#^http://www.schrack.at/#', $geoIP->getRedirectUrl()));

        echo "url: " . $geoIP->getRedirectUrl() . "<br/>\n";
        echo "needs: " . $geoIP->getNeedsRedirect() . "<br/>\n";

        echo "--- ALL MODEL TESTS PASSED --- <br/>\n";
        die;
    }

    public function offersAction() {
        $this->_documentsAction();
    }

    public function orderNowAction () {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }
        $json = Mage::getModel('schrackcore/jsonresponse');
        $this->loadLayout();
        $orderNo = $this->getRequest()->getParam('orderNo');

        // Changed data by customer from offer, as an associative array (key is sku / value is quantity):
        $articleData = json_decode($this->getRequest()->getParam('articleData'), true);
        $this->_getSession()->setChangedOfferValues($articleData);

        $helper = Mage::helper('schracksales/order');
        $order = $helper->getFullOrder($orderNo);

        if ( ! $order ) {
            die('No such order');
        }
        $block = $this->getLayout()->createBlock('Schracklive_SchrackCustomer_Block_Account_OrderNowPopup');
        $block->setOrder($order);

        $block->setTemplate('customer/account/documents/ordernowpopup.phtml');
        $html = $block->toHtml();
        $json->setHtml($html);

        $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $json->encodeAndDie();
    }

    public function doOrderNowAction () {
        $this->logAcceptOffer('begin doOrderNowAction()');
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }
        /** @var Schracklive_SchrackCustomer_Helper_Tracking $trackHelper */
        $trackHelper = Mage::helper('schrackcustomer/tracking');
        $json = Mage::getModel('schrackcore/jsonresponse');
        $this->loadLayout();
        $request = Mage::app()->getRequest();
        // order_no=340276099&pickup_delivery=delivery&shipping_address_id=40519
        $orderNo = $request->getParam('order_no');
        $this->logAcceptOffer('orderNo: ' . $orderNo);
        $pickupDelivery = $request->getParam('pickup_delivery');
        $this->logAcceptOffer('pickupDelivery: ' . $pickupDelivery);
        $pickup = $pickupDelivery === 'pickup' ? 1 : 0;
        $shippingAddressId = $request->getParam('shipping_address_id');
        $this->logAcceptOffer('shippingAddressId: ' . $shippingAddressId);
        $pickupAddressId = $request->getParam('pickup_address_id');
        $this->logAcceptOffer('pickupAddressId: ' . $pickupAddressId);
        $mustReload = $request->getParam('must_reload');
        If (intval($mustReload) == 1) {
            $mustReload = true;
        } else {
            $mustReload = false;
        }
        $this->logAcceptOffer('mustReload: ' . $mustReload);
        $customerReference = $request->getParam('customer_reference');
        $this->logAcceptOffer('customerReference: ' . $customerReference);
        $customerReference = str_replace(';','&#59;',$customerReference);

        $helper = Mage::helper('schracksales/order');
        $order = $helper->getFullOrder($orderNo);
        /** @var $customer Schracklive_SchrackCustomer_Model_Customer */
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $this->logAcceptOffer('user: ' . $customer->getEmail());
        $this->logAcceptOffer('customer: ' . $customer->getSchrackWwsCustomerId());
        /** @var $advisor Schracklive_SchrackCustomer_Model_Customer */
        $advisor = $customer->getAccount()->getAdvisor();
        $this->logAcceptOffer('advisor: ' . $advisor->getEmail());
        $articleData = $this->_getSession()->getChangedOfferValues();
        $this->_getSession()->unsChangedOfferValues();
        $hasChanges = false;
        if ( $articleData ) {
            $articleDataNew = array();
            foreach ( $articleData as $row ) {
                $articleDataNew[$row['positionNum']] = $row;
            }
            $articleData = $articleDataNew;
            foreach ( $order->getAllItems() as $item ) {
                $posNo = $item->getData('PositionNumber');
                $sku = $item->getSku();
                if ( isset($articleData[$posNo]) ) {
                    $this->logAcceptOffer('article data from HTML-form: ' . $posNo . ' - ' . $sku . ' = ' . $articleData[$posNo]['qty']);
                    $articleData[$posNo]['qty'] = str_replace(array('.',','),array('',''),$articleData[$posNo]['qty']);
                    if ( intval($articleData[$posNo]['qty']) != intval($item->getQtyOrdered()) ) {
                        $formQty = intval($articleData[$posNo]['qty']);
                        $offerQty = intval($item->getQtyOrdered());
                        $this->logAcceptOffer('offer qty = ' . $offerQty . ' --> form data offer qty = ' . $formQty );
                        $this->logAcceptOffer('^CHANGE^');
                        $hasChanges = true;
                    }
                }
            }
        }
        if ( ! $hasChanges ) {
            $articleData = null;
        }

        if ( $order->isOfferAndCanBeOrdered() < 1 ) {
            $shopMsg = null;
            if ( $order->isOffer() ) {
                if ( $order->isOfferOutdated() ) {
                    $this->logAcceptOffer('State: manual-outdated');
                    $trackHelper->trackAcceptOffer($order,'manual-outdated',$articleData != null); // case 5
                    $shopMsg = $this->__('Offer has expired.');
                } else {
                    $this->logAcceptOffer('State: manual-constraints');
                    $trackHelper->trackAcceptOffer($order,'manual-constraints',$articleData != null); // case 6
                    $shopMsg = $this->__('Offer was not prepared from WWS to can be accepted automatically.');
                    $reasons = $order->getData('OfferNotValidReason');
                    if ( $reasons && $reasons > '' ) {
                        $shopMsg .= $this->__('WWS explanation is:');
                        $shopMsg .= "<br>\n";
                        $reasons = explode('|',$reasons);
                        foreach ( $reasons as $reason ) {
                            $shopMsg .= "<br>\n";
                            $shopMsg .= $this->__($reason);
                        }
                    }
                }
            } else {
                $this->logAcceptOffer('State: re-order');
                $trackHelper->trackAcceptOffer($order,'re-order',$articleData != null); // case 7
            }
            //                                $isReOrder,         $mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,$messages,$exception,$sku2changedQtyMap,$shopMessageText,$reference
            $this->startManualOrderProcessing(! $order->isOffer(),$mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,null,     null,      $articleData,      $shopMsg,        $customerReference);
        } else if ( $articleData ) {
            $this->logAcceptOffer('State: manual-changed');
            $trackHelper->trackAcceptOffer($order,'manual-changed',true); // case 4
            //                                $isReOrder,$mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,$messages,$exception,$sku2changedQtyMap,$shopMessageText,$reference
            $this->startManualOrderProcessing(false,     $mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,null,     null,      $articleData,      null,            $customerReference);
        } else {
            $requestHelper = Mage::helper('wws/request');
            try {
                $this->logAcceptOffer('before orderOfferWithoutQuote()');
                $messages = $requestHelper->orderOfferWithoutQuote($order,$customer,$advisor,$pickup,$pickupAddressId,$shippingAddressId,$customerReference);
                $this->logAcceptOffer('after orderOfferWithoutQuote()');
                $errorCnt = $messages->count(Mage_Core_Model_Message::ERROR) + $messages->count(Mage_Core_Model_Message::WARNING);
                if ( $errorCnt > 0 ) {
                    $mostRecentWwsMsg = null;
                    $userShouldRetry = false;
                    $items = $messages->getItems();
                    for ( $i = 0; $i < count($items); $i++ ) {
                        $wwsError = $items[$i];
                        if ( $wwsError instanceof Mage_Core_Model_Message_Warning || $wwsError instanceof Mage_Core_Model_Message_Error ) {
                            if ( $wwsError->getIdentifier() != 'WWS-400' ) {
                                $mostRecentWwsMsg = $wwsError->getIdentifier() . ': ' . $wwsError->getCode();
                            } else {
                                $this->logAcceptOffer('ERROR: WWS-400');
                            }
                            if ( $wwsError->getIdentifier() == 'WWS-701' /*|| substr($wwsError->getIdentifier(), 0, 5) == 'WWS-3'*/ ) {
                                $userShouldRetry = true;
                            }
                        }
                    }
                    if ( $userShouldRetry ) {
                        $this->logAcceptOffer('State: auto-modified');
                        $trackHelper->trackAcceptOffer($order,'auto-modified'); // case 2
                        $this->waitOrderNowMessage($mustReload, $json);
                    } else {
                        $this->logAcceptOffer('State: auto-error (ACHTUNG)');
                        $this->logAcceptOffer($mostRecentWwsMsg);
                        $trackHelper->trackAcceptOffer($order,'auto-error',false, $mostRecentWwsMsg); // case 3
                        // case 3
                        $this->startManualOrderProcessing(false,
                            $mustReload,
                            $customer,
                            $order,
                            $pickup,
                            $pickupAddressId,
                            $shippingAddressId,
                            $json,
                            $mostRecentWwsMsg,
                            null,
                            null,
                            null,
                            $customerReference);
                    }
                } else {
                    $this->logAcceptOffer('State: auto-success');
                    $trackHelper->trackAcceptOffer($order,'auto-success'); // case 1
                    $this->storeOfferAsOrdered($customer, $order);
                    $msg = $this->__('The offer was successfully ordered.') . ' ' . $this->__('You will receive your order confirmation via e-mail.');
                    if ( $mustReload ) {
                        Mage::getSingleton('core/session')->addSuccess($msg);
                    }
                    $json->setMessage($msg);
                }
            } catch ( Exception $ex ) {
                Mage::logException($ex);
                try {
                    $this->logAcceptOffer('Exeption 1: ' . $ex->getMessage());
                    $this->logAcceptOffer('State: technical-error');
                    $trackHelper->trackAcceptOffer($order,'technical-error'); // case 8
                    //                                $isReOrder,$mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,$messages,$exception,$sku2changedQtyMap,$shopMessageText,$reference
                    $this->startManualOrderProcessing(false,     $mustReload,$customer,$order,$pickup,$pickupAddressId,$shippingAddressId,$json,null,     $ex,       null,              null,            $customerReference);
                } catch ( Exception $ex2 ) {
                    $this->logAcceptOffer('Exeption 2: ' . $ex2->getMessage());
                    Mage::logException($ex2);
                }
            }
        }

        $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $this->logAcceptOffer('end doOrderNowAction()');
        $json->encodeAndDie();
    }

    private function waitOrderNowMessage ( $mustReload, $json ) {
        $this->logAcceptOffer('begin waitOrderNowMessage()');
        $msg = $this->__('This offer has just been edited by a Schrack Technik employee.') . ' '
             . $this->__('The transmission delay to Webshop can be up to one minute.') . ' '
             . $this->__('Please have patience.');
        if ( $mustReload ) {
            Mage::getSingleton('core/session')->addNotice($msg);
        }
        $json->setMessage($msg);
        $this->logAcceptOffer('end waitOrderNowMessage()');
    }

    private function startManualOrderProcessing ( $isReOrder, $mustReload, $customer, $order, $pickup, $pickupAddressId, $shippingAddressId, $json, $messages, $exception, $pos2changedSkuQtyMap, $shopMessageText, $reference ) {
        $this->logAcceptOffer('begin startManualOrderProcessing()');
        try {
            if ( $exception ) {
                $msg = $this->__('A problem occurred while trying to order this offer. Please contact your contact person.');
                if ( $mustReload ) {
                    Mage::getSingleton('core/session')->addError($msg);
                }
            } else {
                $msg = $this->__('Thanks for your order request!') . ' '
                    . $this->__('The accepted offer is still under consideration by an employee.') . ' '
                    . $this->__('You will then receive an order confirmation via e-mail, or a representative will personally contact you.');
                if ( $mustReload ) {
                    Mage::getSingleton('core/session')->addNotice($msg);
                }
            }
            $json->setMessage($msg);

            $wwsMessageText = null;
            if ( $messages != null  ) {
                if ( is_array($messages) && $messages->count(Mage_Core_Model_Message::ERROR) > 0 ) {
                    $wwsMessageText = $messages->getErrors()[0]->getText();
                } else if ( is_string($messages) ) {
                    $wwsMessageText = $messages;
                }
            }
            $exceptionText = null;
            if ( $exception ) {
                $exceptionText = $exception->getMessage();
            }

            $helper = Mage::helper('customer');
            $helper->sendOrderingOfferManuallyEmail($isReOrder, $customer, $order, $pickup, $pickupAddressId, $shippingAddressId, $wwsMessageText, $shopMessageText, $exceptionText, $pos2changedSkuQtyMap, $reference, self::LOG_ACCEPT_OFFER ? self::LOG_ACCEPT_OFFER_FILE : false);
            $this->logAcceptOffer('no exception got from sendOrderingOfferManuallyEmail(), saving order now');
            $this->storeOfferAsOrdered($customer, $order);
            $this->logAcceptOffer('before sendProcessingOrderedOfferEmail2Customer()');
            $this->sendProcessingOrderedOfferEmail2Customer($customer, $order);
            $this->logAcceptOffer('after sendProcessingOrderedOfferEmail2Customer()');
        } catch ( Exception $ex ) {
            $this->logAcceptOffer('!! exception got from sendOrderingOfferManuallyEmail(), no further processing, see exception.log !!!');
            Mage::logException($ex);
            throw $ex;
        }
        $this->logAcceptOffer('end startManualOrderProcessing()');
    }

    private function storeOfferAsOrdered ( $customer, $order ) {
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO sales_schrack_ordered_offer (customer_number, order_number) VALUES(?,?)";
        $writeConnection->query($sql, array($customer->getSchrackWwsCustomerId(),$order->getSchrackWwsOrderNumber()));
    }

    private function sendProcessingOrderedOfferEmail2Customer ( $customer, $order ) {
        $this->logAcceptOffer('begin sendProcessingOrderedOfferEmail2Customer()');
        $xmlPath = 'schrack/customer/notifyOrderedOfferProcess';
        $email = defined('OVERRIDE_EMAIL_TO') ? OVERRIDE_EMAIL_TO : $customer->getEmail();
        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
        $singleMailApi->setMagentoTransactionalTemplateVariables(array('customer' => $customer,'order'=> $order));
        $singleMailApi->addToEmailAddress($email);
        $singleMailApi->setFromEmail('general');
        $singleMailApi->createAndSendMail();
        $this->logAcceptOffer('end sendProcessingOrderedOfferEmail2Customer()');
    }

    private function orderWentWrong ( $customer, $advisor, $order, $json, $messages = null ) {
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template');
        $block->setTemplate('customer/account/documents/ordernotpossible.phtml');
        $block->setData('customer', $customer);
        $block->setData('advisor', $advisor);
        $block->setData('order', $order);
        $block->setData('messages', $messages);
        $html = $block->toHtml();
        $json->setHtml($html);
    }

    public function sendOfferWentWrongAction () {
        $name       = $this->getRequest()->getParam('name');
        $email      = $this->getRequest()->getParam('email');
        $phone      = $this->getRequest()->getParam('phone');
        $text       = $this->getRequest()->getParam('text');

        $company    = $this->getRequest()->getParam('company');
        $customerNo = $this->getRequest()->getParam('customer-no');
        $country    = $this->getRequest()->getParam('country');

        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $branch = $customer->getAccount()->getWwsBranchId();
        } else {
            $branch = '';
        }

        $advisor = Mage::helper('schrack')->getAdvisor();
        $helper = Mage::helper('customer');
        $helper->sendOrderWentWrongEmail($name,$email,$phone,$company,$customerNo,$country,$branch,$text,$advisor);
        $msg = $this->__('Your request has been sent. Your contact person will contact you soon.');
        Mage::getSingleton('core/session')->addSuccess($msg);

        return $this->_redirectReferer();
    }


    private function _hasPageRequestParamsSet() {
        $params = $this->getRequest()->getParams();
        return (isset($params['p']) || isset($params['limit']));
    }

/**
 *
 * default action for all documents-related stuff
 */
    private function _documentsAction() {
        $params = $this->getRequest()->getParams();
        if (count($params) > 0 && (!isset($params['excludeAjaxCall']) || !($params['excludeAjaxCall']))) {	// Added by Nagarro to by pass redirection to exclude AJAX call for performance testing
            if (isset($params['reset']))
               Mage::getSingleton('customer/session')->setData('documentParams', null);
            else {
                $sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
                if (isset($sessionParams)) {
                    $sessionParams = array_merge($sessionParams, $params);
                } else
                    $sessionParams = $params;
                Mage::getSingleton('customer/session')->setData('documentParams', $sessionParams);

            }
             $this->_redirect('*/*/*');
        } else {
            try {
                $this->loadLayout();
                $this->renderLayout();
            } catch (Exception $e) {
                Mage::getSingleton('customer/session')->addError($e->getMessage());
                Mage::logException($e);
                $this->_redirect('*/*');
            }
        }
    }

    public function ordersAction() {
        $this->_documentsAction();
    }

    public function shipmentsAction() {
        $this->_documentsAction();
    }

    public function invoicesAction() {
        $this->_documentsAction();
    }

    public function creditmemosAction() {
        $this->_documentsAction();
    }

    public function documentsDetailsearchAction() {
        $this->_documentsAction();
    }

    public function backordersAction() {
        try {
            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            Mage::logException($e);
            $this->_redirect('*/*');
        }
    }

    public function documentsDetailviewAction() {
        try {
            $session = Mage::getSingleton('customer/session');
            $loggedInCustomer = $session->getLoggedInCustomer();
            if ( is_object($loggedInCustomer) ) {
                $customer = $session->getCustomer();
                $request = $this->getRequest()->getParams();
                if($loggedInCustomer->getSchrackWwsCustomerId() != $customer->getSchrackWwsCustomerId()) {
                    Mage::log('documentsDetailview: Customer ' . $loggedInCustomer->getSchrackWwsCustomerId() . ' (' . $loggedInCustomer->getEmail()
                        . ') is viewing document (type=' . $request['type'] . ', ' . $request['documentId'] . ') for customer ' . $customer->getSchrackWwsCustomerId() . ' (' . $customer->getEmail() . ')',
                        null, 'act_as_user.log'
                    );
                }
            }
            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            $this->_redirect('*/*');
        }
    }

    public function documentsDownloadAction() {
        try {
            Mage::helper("schrackcustomer/order")->documentsDownloadAction();
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            $this->getResponse()->setHeader('Content-type', 'text/plain');
            $this->getResponse()->setBody($this->__('An error occurred: ') . $this->__($e->getMessage()));
            $this->getResponse()->sendResponse();
        }
        die;
    }

    /**
     * add multiple items to cart by sku. This is a pure ajax action that
     * returns a summed-up ok message, or an error string via json
     */
    public function batchAddProductsToCartAction() {
        $jsonResponse = array();
        $params = $this->getRequest()->getParams();
        $successCount = 0;
        if (isset($params['products'])) {
            $maxAmount = Mage::helper('schrack')->getMaximumOrderAmount();
            $cartItemCnt = count(Mage::getSingleton('checkout/cart')->getQuote()->getAllVisibleItems());

            $products = explode(';', $params['products']);
            foreach ($products as $productData) {
                if ( $cartItemCnt >= $maxAmount ) {
                    $msg = $this->__('Product cannot be added to cart because maximum item count %d has already been reached.', $maxAmount);
                    $this->addError($msg, $jsonResponse, false);
                    break;
                }

                list($sku, $qty) = explode(':', $productData);

                $product = Mage::getModel('catalog/product')->loadBySku($sku);

                if (is_object($product) && fmod($qty, $product->calculateMinimumQuantityPackage()) != 0) {
                    $msg = $this->__('The item has not been added to shopping cart. Please check quantity and packaging.');
                    $msg .= ': <span style="font-weight: bold; color: #00589D;">';
                    $msg .= Mage::helper('core')->htmlEscape($product->getName()) . '</span>';
                    $this->addError($msg, $jsonResponse, false);
                } else {
                    $this->_addProductToCartBySku($sku, $qty, $jsonResponse);
                    $successCount++;
                    $cartItemCnt++;
                }
            }
        }
        if ($successCount > 1) {
            $this->removeSuccessMessages($jsonResponse);
            $this->addSuccess($this->__('%d products where added to cart.', $successCount), $jsonResponse);
        }

        $jsonResponse['ok'] = true;  // NOTE: Also "true", if something went wrong, e.g. "article not found" (mysterious behaviour!!)
        $numberOfDifferentItemsInCart = intval(Mage::helper('checkout/cart')->getSummaryCount());
        $jsonResponse['numberOfDifferentItemsInCart'] = $numberOfDifferentItemsInCart;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
    }

    public function batchAddProductsToCartFromPartslistAction() {
        $jsonResponse = array();
        $params = $this->getRequest()->getParams();
        $successCount = 0;
        if (isset($params['products'])) {
            $products = explode(';', $params['products']);
            foreach ($products as $productData) {
                list($sku, $qty) = explode(':', $productData);

                $product = Mage::getModel('catalog/product')->loadBySku($sku);

                if (is_object($product) && fmod($qty, $product->calculateMinimumQuantityPackage()) != 0) {
                    $msg  = $this->__('The item has not been added to shopping cart. Please check quantity and packaging.');
                    $msg .= ': <span style="font-weight: bold; color: #00589D;">';
                    $msg .= Mage::helper('core')->htmlEscape($product->getName()) . '</span>';
                    $this->addError($msg, $jsonResponse, false);
                } else {
                    $this->_addProductToCartBySku($sku, $qty, $jsonResponse, 'partslist');
                    $successCount++;
                }
            }
        }
        if ($successCount > 1) {
            $this->removeSuccessMessages($jsonResponse);
            $this->addSuccess($this->__('%d products where added to cart.', $successCount), $jsonResponse);
        }

        $jsonResponse['ok'] = true;  // NOTE: Also "true", if something went wrong, e.g. "article not found" (mysterious behaviour!!)
        $numberOfDifferentItemsInCart = Mage::helper('schrackcheckout/cart')->getNumerbOfDifferentItemsInCart();
        $jsonResponse['numberOfDifferentItemsInCart'] = $numberOfDifferentItemsInCart;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
    }


    public function batchAddProductsToCartByPartslistAction() {
        $jsonResponse = array();
        $sku_qty_list = "";
        $partslistId = $this->getRequest()->getParam('id');
        $model = Mage::getModel('schrackwishlist/partslist');

        $sharedpartslistmode = $this->getRequest()->getParam('sharedpartslistmode');

        if ($sharedpartslistmode == 'yes') {
            $model = Mage::getModel('schrackwishlist/partslist');
            $sql = "SELECT qty, product_id FROM partslist_item  WHERE partslist_id = " . intval($partslistId);
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $queryResult = $readConnection->query($sql);

            foreach ($queryResult as $recordset) {
                $qty        = $recordset['qty'];
                $product_id = $recordset['product_id'];
                $product = Mage::getModel('Schracklive_SchrackCatalog_Model_Product')->setStoreId(Mage::app()->getStore()->getId());
                $product = $product->load($product_id);

                $sku_qty_list .= $product->getSku() . ':' . $qty . ';';
            }

        } else {
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();

            $partslist = $model->loadByCustomerAndId($customer, $partslistId);
            $sku_qty_list = '';
            foreach ($partslist->getItemCollection() as $item) {
                $product = $item->getProduct();
                $sku_qty_list .= $product->getSku() . ':' . intval($item->getQty()) . ';';
            }
        }
        
        $sku_qty_list = substr($sku_qty_list, 0, -1);

        $successCount = 0;
        if ($sku_qty_list) {
            $products = explode(';', $sku_qty_list);
            foreach ($products as $productData) {
                list($sku, $qty) = explode(':', $productData);

                $product = Mage::getModel('catalog/product')->loadBySku($sku);

                if (is_object($product) && fmod($qty, $product->calculateMinimumQuantityPackage()) != 0) {
                    $this->addError($this->__('The item has not been added to shopping cart. Please check quantity and packaging.') . ': <span style="font-weight: bold; color: #00589D;">' . Mage::helper('core')->htmlEscape($product->getName()) . '</span>', $jsonResponse, false);
                    $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty), true, array(), 'AccountController::batchAddProductsToCartAction()');
                    //$resultQtyData['closestHigherQuantity']
                } else {
                    $this->_addProductToCartBySku($sku, $qty, $jsonResponse, 'partslist');
                    $successCount++;
                }
            }
        }
        if ($successCount > 1) {
            $this->removeSuccessMessages($jsonResponse);
            $this->addSuccess($this->__('%d products where added to cart.', $successCount), $jsonResponse);
        }

        $jsonResponse['ok'] = true;  // NOTE: Also "true", if something went wrong, e.g. "article not found" (mysterious behaviour!!)
        $numberOfDifferentItemsInCart = Mage::helper('schrackcheckout/cart')->getNumerbOfDifferentItemsInCart();
        $jsonResponse['numberOfDifferentItemsInCart'] = $numberOfDifferentItemsInCart;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
    }


    public function batchAddDocumentsToCartAction() {
        $successCount = 0;
        $sapOciOfferActive = '';
        $offerNumberReference = '';

        if ($this->getRequest()->isAjax()) {
            $jsonResponse = array();
            $paramDocuments = $this->getRequest()->getParam('documents');
            if (isset($paramDocuments)) {
                $documents = explode(';', $paramDocuments);
                foreach ($documents as $document) {
                    list ($docId, $type) = explode(':', $document);
                    try {
                        if (Mage::helper('sapoci')->isSapociCheckout()) {
                            $type = str_replace('offer', 'order', $type);
                            $sapOciOfferActive = 'sap_oci_offer_active';
                            $offerNumberReference = $docId;
                        }
                        $this->_addDocumentToCart($type, $docId, $sapOciOfferActive, $offerNumberReference);
                        $successCount++;
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->addError($this->__($e->getMessage()), $jsonResponse, false);
                    }
                }
            }
            if ($successCount > 0) {
                $this->removeSuccessMessages($jsonResponse);
                if ($successCount > 1)
                    $this->addSuccess($this->__('%d documents were added to cart.', $successCount), $jsonResponse, false);
                else
                    $this->addSuccess($this->__('1 document was added to cart.', $successCount), $jsonResponse, false);
            }


            $jsonResponse['ok'] = true;  // NOTE: Also "true", if something went wrong, e.g. "article not found" (mysterious behaviour!!)

            foreach (Mage::getSingleton('checkout/cart')->getQuote()->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $numberOfDifferentItemsInCart[] = $product->getSku();
            }

            $jsonResponse['numberOfDifferentItemsInCart'] = count($numberOfDifferentItemsInCart);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
        }
        else
            throw new Exception('invalid action for non-ajax request');
    }

    public function batchAddOfferProductsToCartAction() {
        if (!$this->getRequest()->isAjax() && $this->disableAjaxCheck == false) {
            throw new Exception('invalid action #201883627');
        }

        $jsonResponse = array();
        $params = $this->getRequest()->getParams();
        $successCount = 0;
        $offerNumberReference = '';

        if (isset($params['offer'])) {
            $offerNumberReference = $params['offer'];
        } else {
            if (isset($params['documents'])) {
                $offerNumberReference = str_replace(':offer', '', $params['documents']);
                $offerNumberReference = str_replace(':order', '', $offerNumberReference);
            } else {
                $this->addError($this->__('No Document Id for this Offer.'));
            }
        }

        //---------------------------------------------------- Add items to cart
        $orderHelper = Mage::helper('schracksales/order');
        $document = $orderHelper->getFullDocument($offerNumberReference, 'Order');
        $timestamp = $document->getData('OfferValidUntil');

        if (isset($params['products'])) {
            $products = explode(';', $params['products']);
            foreach ($products as $productData) {
                list($sku, $qty) = explode(':', $productData);
                $product = Mage::getModel('catalog/product')->loadBySku($sku);
                if (is_object($product) && fmod($qty, $product->calculateMinimumQuantityPackage()) != 0) {
                    $msg = $this->__('The item has not been added to shopping cart. Please check quantity and packaging.');
                    $msg .= ': <span style="font-weight: bold; color: #00589D;">';
                    $msg .= Mage::helper('core')->htmlEscape($product->getName()) . '</span>';
                    $this->addError($msg, $jsonResponse, false);
                } else {
                    $this->_addProductToCartBySku($sku, $qty, $jsonResponse, '', $offerNumberReference);
                    $successCount++;
                }
            }

            if ($successCount > 1) {
                $this->removeSuccessMessages($jsonResponse);
                $this->addSuccess($this->__('%d products where added to cart.', $successCount), $jsonResponse);
            }
        }

        $paramDocuments = $this->getRequest()->getParam('documents');
        if (isset($paramDocuments)) {
            $documents = explode(';', $paramDocuments);
            foreach ($documents as $document) {
                list ($docId, $type) = explode(':', $document);
                try {
                    $this->_addDocumentToCart($type, $docId, 'sap_oci_offer_active', $offerNumberReference);
                    $successCount++;
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->addError($this->__($e->getMessage()), $jsonResponse, false);
                }
            }

            if ($successCount > 0) {
                $this->removeSuccessMessages($jsonResponse);
                if ($successCount > 1) $this->addSuccess($this->__('%d documents were added to cart.', $successCount), $jsonResponse, false); else
                    $this->addSuccess($this->__('1 document was added to cart.', $successCount), $jsonResponse, false);
            }
        }

        $jsonResponse['ok'] = true;  // NOTE: Also "true", if something went wrong, e.g. "article not found" (mysterious behaviour!!)
        $numberOfDifferentItemsInCart = intval(Mage::helper('checkout/cart')->getSummaryCount());
        $jsonResponse['numberOfDifferentItemsInCart'] = $numberOfDifferentItemsInCart;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
    }

    private function _addDocumentToCart($type, $documentId, $sapOciOfferActive = '', $offerNumberReference = 0)
    {
        $helper = Mage::helper('schracksales/order');
        $document = $helper->getFullDocument($documentId, $type);
        $successCount = 0;
        if (!$document) {
            throw new Exception("No such document as '" . $documentId . "'");
        }
        $items = $document->getItemsCollection();
        $maxAmount = Mage::helper('schrack')->getMaximumOrderAmount();
        $cartItemCnt = count(Mage::getSingleton('checkout/cart')->getQuote()->getAllVisibleItems());
        foreach ($items as $item) {
            if ($cartItemCnt >= $maxAmount) {
                $msg = $this->__('Product cannot be added to cart because maximum item count %d has already been reached.', $maxAmount);
                throw new Exception($msg);
            }
            $sku = $item->getSku();
            $product_helper = Mage::getModel('schrackcatalog/product');
            $productId = $product_helper->getIdBySku($sku);
            if ($productId) {
                if (in_array($type, array('offer', 'order'))) $qty = $item->getQtyOrdered(); else $qty = $item->getQty();
                $dummyJr = array();
                if ('sap_oci_offer_active' == $sapOciOfferActive && $offerNumberReference > 0) {
                    $this->_addProductToCartById($product_helper, $productId, $qty, $dummyJr, '', 'sap_oci_offer_active', $offerNumberReference);
                } else {
                    $this->_addProductToCartById($product_helper, $productId, $qty, $dummyJr);
                }
                $successCount++;
                $cartItemCnt++;
            }
        }
        return $successCount;
    }

    /**
     *
     * @param type $product_helper
     * @param type $productId
     * @param type $qty
     */
    protected function _addProductToCartById( $product_helper, $productId, $qty, &$jsonResponse, $source = '', $offerNumberReference = 0 ) {
        $product = $product_helper->load( $productId );
        if ($product) {
            $categories = $product->getCategoryIds();
            if (!$categories) {
                if ($source == '') {
                    //throw new Exception($this->__('Product %s can not be added to cart.', $product->getSku()));
                } else {
                    $msg = $this->__('Product %s can not be added to cart.', $product->getSku());
                    $this->addError($msg, $jsonResponse, false);
                }
            }
            if (!Mage::helper('schrackcustomer/order')->productCanBeAddedToLists($product)) {
                if ($source == '') {
                    throw new Exception($this->__('Product %s can not be added to cart.', $product->getSku()));
                } else {
                    $msg = $this->__('Product %s can not be added to cart.', $product->getSku());
                    $this->addError($msg, $jsonResponse, false);
                }
            }

            $cart = Mage::getSingleton('checkout/cart');

            // Bei Set-Artikeln, dürfen die sub-Artikel nicht in den Warenkorb:
            $articleIsSubArticleFromSet = false;
            if ($offerNumberReference > 0) {
                $orderHelper = Mage::helper('schracksales/order');
                $document = $orderHelper->getFullDocument($offerNumberReference, 'Order');

                $items = $document->getItemsCollection();
                $offerSchrackPriceUnit = 1;

                foreach ($items as $item) {
                    if($product->getSku() == $item->getItemID() && $item->getIsSubPosition() == true) {
                        $articleIsSubArticleFromSet = true;
                    }
                    $offerSchrackPriceUnit = $item->getSchrackPriceunit();
                }

                if ($articleIsSubArticleFromSet == false) {
                    $cart->addProduct($product, array('qty' => $qty));
                    $cart->getQuote()->setDataChanges(true);
                    $cart->save();
                }
            } else {
                $cart->addProduct($product, array('qty' => $qty));
                $cart->getQuote()->setDataChanges(true);
                $cart->save();
            }

            if ($offerNumberReference > 0 && $articleIsSubArticleFromSet == false) {
                $orderHelper = Mage::helper('schracksales/order');
                $document = $orderHelper->getFullDocument($offerNumberReference, 'Order');
                $items = $document->getItemsCollection();

                foreach ($items as $item) {
                    if ($product->getSku() == $item->getSku()) {
                        $offerPrice                  = $item->getPrice();
                        $offerSchrackPriceUnit       = $item->getSchrackPriceunit();
                        $offerRowTotal               = $item->getRowTotal();
                        $offerBaseRowTotal           = $item->getBaseRowTotal();
                        $offerRowTotalInclTax        = $item->getRowTotalInclTax();
                        $offerBaseRowTotalInclTax    = $item->getBaseRowTotalInclTax();
                        $offerBaseTaxAmount          = $item->getBaseTaxAmount();
                        $offerTaxAmount              = $item->getTaxAmount();
                        $offerSchrackSurchargeSimple = $item->getSchrackSurcharge() * ($item->getSchrackPriceunit() / $item->getQtyOrdered());
                        $offerQuantity               = $item->getQtyOrdered();
                    }
                }

                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $query  = "UPDATE sales_flat_quote_item SET";
                $query .= " schrack_offer_price_per_unit = " . number_format(($offerPrice), 2, '.','') . ",";
                $query .= " schrack_offer_unit = " . $offerSchrackPriceUnit . ",";
                $query .= " schrack_offer_tax = " . number_format(($offerTaxAmount/$qty), 4, '.','') . ",";
                $query .= " schrack_offer_surcharge = " . number_format(($offerSchrackSurchargeSimple), 4, '.','') . ",";
                $query .= " schrack_offer_reference = '" . $offerNumberReference . "',";
                $query .= " schrack_offer_number  = '" . $document->getSchrackWwsOfferNumber() . "'";
                $query .= " WHERE quote_id = " . $cart->getQuote()->getEntityId();
                $query .= " AND sku LIKE '" . $product->getSku() . "'";

                if ($item->getSchrackOfferReference() == null || $item->getSchrackOfferReference() == '') {
                    if (floatval($offerPrice) > 0) {
                        $writeConnection->query($query);
                    }
                }
            }
            $this->_getSession()->setCartWasUpdated(true);
            $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->htmlEscape($product->getName()));
            $this->addSuccess($message, $jsonResponse, false);
        } else {
            Mage::getSingleton('core/session')->addError(str_replace('%s', $productId, $this->__('Product number %s not found.')));
        }
    }

    private function dumpCart () {
        $res = '';
        $cart = Mage::getModel('checkout/cart')->getQuote();
        foreach ($cart->getAllItems() as $item) {
            if ( $res !== '' ) {
                $res .= ', ';
            }
            $sku = $item->getProduct()->getSku();
            $qty = $item->getQty();
            $res .= $qty;
            $res .= 'x';
            $res .= $sku;
        }
        return $res;
    }

    /**
     *
     * @param string $sku
     * @param float $qty
     */
    protected function _addProductToCartBySku($sku, $qty, &$jsonResponse, $source = '', $offerNumberReference = '') {
        $product_helper = Mage::getModel('schrackcatalog/product');
        $productId = $product_helper->getIdBySku($sku);
        if ($productId) {
            $this->_addProductToCartById($product_helper, $productId, $qty, $jsonResponse, $source, $offerNumberReference);
        } else {
            if ($source == '') {
                throw new Exception($this->__('Unable to find product for sku %s', $sku));
            } else {
                $this->addError($this->__('Unable to find product for sku %s', $sku), $jsonResponse, false);
            }
        }
    }

    private function addError($msg, &$jsonResponse, $forceToSession = false) {
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['errors']) || !is_array($jsonResponse['errors']))
                $jsonResponse['errors'] = array();
            array_push($jsonResponse['errors'], $msg);
        } else
            Mage::getSingleton('core/session')->addError($msg);
    }

    private function addSuccess($msg, &$jsonResponse, $forceToSession = false) {
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['messages']) || !is_array($jsonResponse['messages']))
                $jsonResponse['messages'] = array();
            array_push($jsonResponse['messages'], $msg);
        } else
            Mage::getSingleton('core/session')->addSuccess($msg);
    }

    private function removeSuccessMessages(&$jsonResponse) {
        $jsonResponse['messages'] = array();
    }

    private function _sendVatSoftCheckEmail($customerData = array()) {
        /* @var $block Mage_Checkout_Block_Onepage_Review_Info */
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('customer/account/vat_soft_check.phtml');
        if (!empty($customerData)) {
            $params = $customerData;
        } else {
            $params = $this->getRequest()->getParams();
        }

        if ($params['vat_identification_number']
            && $params['email']
            && $params['name1']) {
            $block->assign('params', $params);
            $html = $block->toHtml();
            $mailHelper = Mage::helper('wws/mailer');
            $toAddress = Mage::getStoreConfig('schrack/customer/vatCheckEmailToAddress');

            $customer = Mage::getModel('customer/customer');
            $customer->loadByEmail($params['email']);

            $wwsCustomerId = '';
            if ($customer && intval($customer->getSchrackWwsCustomerId(), 10) > 0) {
                $wwsCustomerId = $customer->getSchrackWwsCustomerId();
            }
            if ($wwsCustomerId == '' && intval(Mage::getStoreConfig('schrack/new_self_registration/vatCheckSendEmailToSupport'), 10) == 0 ) {
                return;
            }

            if (isset($toAddress)) {
                // SOFT VAT Cache:
                // Check our own local ressources first, before stressing the EU VIES Server:
                $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                $vatNumber = $params['vat_identification_number'];
                Mage::log("VAT-Soft-Check = " . $vatNumber, null, 'vies_soft_check.log');

                $vatSoftCheckTable = Mage::getSingleton('core/resource')->getTableName('schrack_vat_softcheck_cache');
                $query = "SELECT * FROM " . $vatSoftCheckTable . " WHERE vat LIKE ?";
                $queryResult = $readConnection->fetchAll($query, $vatNumber);
                if (count($queryResult) > 0) {
                    // Already existing: don't send E-Mail again
                    Mage::log("VAT-Soft-Check E-Mail BLOCKED = " . $vatNumber, null, 'vies_soft_check.log');
                } else {
                    // Inserts already existing soft checks
                    $query = "INSERT INTO " . $vatSoftCheckTable . " SET vat = :uid,";
                    $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";
                    $binds = array(
                        'uid' => $vatNumber,
                    );
                    $queryResult = $writeConnection->query($query, $binds);

                    Mage::log("_sendVatSoftCheckEmail sends mail to " . $toAddress, null, 'xian.log');
                    $args = array('subject' => $this->__('Customer Registration VAT Soft Check Succeeded'),
                        'to' => $toAddress,
                        'cc' => $toAddress,
                        'bcc' => $toAddress,
                        'body' => $html,
                        'templateVars' => array()
                    );
                    $mailHelper->send($args);
                }
            } else {
                throw new Exception('no address for checkout request email given.');
            }
        }
    }


    public function checkUidAction($customerData = array()) {
        if ($customerData == array()) {
            if (!$this->getRequest()->getPost('vat_identification_number')) {
                $this->_redirectUrl(Mage::getUrl('customer/account'));
                return;
            }
        }
        $errors = array();
        $uidCheck = Mage::getStoreConfig('schrack/vatcheck/enabled');

        if ($uidCheck) {
                $vatHelper = Mage::helper('account/vat');
            if ($customerData == array()) {
                $vat = strtoupper(str_replace(' ', '', $this->getRequest()->getPost('vat_identification_number')));
            } else {
                $vat = strtoupper(str_replace(' ', '', $customerData['vat_identification_number']));
            }

            if($vat) {
                if ($vatHelper->checkVat($vat)) {
                    if ( in_array($vatHelper->getLastCheckType(), array(Schracklive_Account_Helper_Vat::VAT_CHECKTYPE_STRUCTURE,
                        Schracklive_Account_Helper_Vat::VAT_CHECKTYPE_CHECKSUM)) ) {
                        // "soft" test succeeded after "hard test" server was unreachable
                        if ($customerData == array()) {
                            $this->_sendVatSoftCheckEmail($this->getRequest()->getParams());
                        } else {
                            $this->_sendVatSoftCheckEmail($customerData);
                        }
                    }
                } else {
                    $errors['errormsg'] = $this->__($vatHelper->getLastResult());
                }
                if ($this->getRequest()->getPost('user_selected_country')) {
                    $userSelectedCountryID = $this->getRequest()->getPost('user_selected_country');
                    if ($userSelectedCountryID != strtoupper(substr($vat, 0, 2))) {
                        $errors['errormsg'] = $this->__('VAT identification number doesn\'t match country of billing adress.') . ' (11)';
                    }
                }
                if (!$vatHelper->checkLastCountryCode()) {
                    $errors['errormsg'] = $this->__('VAT identification number doesn\'t match country of billing adress.') . ' (21)';
                }
                if ( $vatHelper->vatExists($vat) ) {
                    $errors['errormsg'] = $this->__('VAT identification number already exists.');
                }
            } else {
                // Everything okay, because there should be no check on empty values
            }
        }

        if (is_array($errors) && !empty($errors)) {
            echo json_encode($errors);
            die();
        }
        echo json_encode(array('successmsg' => 'okay'));
        die();
    }


    public function checkEmailDoubletteAction($email = '') {
        // Argument overrides POST-DATA:
        if ($email == '') {
            $email = $this->getRequest()->getPost('email');
        }
        if($email) {
            // Check for email doublette:
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);
            if ($customer->getId() > 0) {
                $errors['errormsg'] = $this->__('Customer email already exists');
                echo json_encode($errors);
                die();
            } else {
                echo json_encode(array('successmsg' => 'okay'));
                die();
            }
        } else {
            $this->_redirectUrl(Mage::getUrl('customer/account'));
            return;
            //$errors['errormsg'] = $this->__('Invalid email address.');
            //echo json_encode($errors);
            //die();
        }
    }


    public function checkCommonEmailAction($email = '') {
        // Argument overrides POST-DATA:
        if ($email == '') {
            $email = $this->getRequest()->getPost('email');
        }
        if($email) {
            // Search in commons_db, if customer exists there:
            $helper = Mage::helper('schrack/email');
            if ( $helper->emailExistsInCommenDB($email) ) {
                $errors['errormsg'] = $this->__('Customer email already exists');
                echo json_encode($errors);
                die();
            } else if ( $this->getRequest()->getPost('validate') && ! $helper->validateEmailAddress($email) ) {
                $errors['errormsg'] = $this->__('Invalid email address');
                echo json_encode($errors);
                die();
            } else {
                echo json_encode(array('successmsg' => 'okay'));
                die();
            }
        } else {
            $this->_redirectUrl(Mage::getUrl('customer/account'));
            return;
            //$errors['errormsg'] = $this->__('Invalid email address.');
            //echo json_encode($errors);
            //die();
        }
    }

    /*
     * Create customer account action
     */
    private function sendSelfRegistrationAction($postRegistrationData) {
        $session = $this->_getSession();
        $errors = array();

        // If customer is logged in, there is no need to register (customer should logged out):
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if ( $msg = Mage::helper('customer')->checkNewPasswordReturningErrorMessage($postRegistrationData['schrack_password'],$postRegistrationData['schrack_password_confirmation']) ) {
            $errors['errormsg'] = $msg;
            echo json_encode($errors);
            die();
        }

        if (isset($postRegistrationData['schrack_salutatory'])) {
            $postRegistrationData['schrack_salutatory'] = str_replace('undefined', '', $postRegistrationData['schrack_salutatory']);
        }

        $postRegistrationDataTemp = array();
        foreach ($postRegistrationData as $key => $value) {
            if (stristr($value, 'undefined')) {
                $errors['errormsg'] = $this->__('Please check data.');
                echo json_encode($errors);
                die();
            } else {
                $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                $postRegistrationDataTemp[$key] = $value;
            }
        }

        $postRegistrationData = array();
        $postRegistrationData = $postRegistrationDataTemp;
       
        // Check agreement (light-registration):
        if ($postRegistrationData['request-type-definition'] == 'light-registration' && !$postRegistrationData['agreement_light_registration']) {
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxAGB')) == 1) {
                $errors['errormsg'] = $this->__('Please agree to all Terms and Conditions to create the account.');
                echo json_encode($errors);
                die();
            }
        }

        // Check agreement (full-registration):
        if ($postRegistrationData['request-type-definition'] == 'full-registration' && !$postRegistrationData['agreement_full_registration']) {
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxAGB')) == 1) {
                $errors['errormsg'] = $this->__('Please agree to all Terms and Conditions to create the account.');
                echo json_encode($errors);
                die();
            }
        }

        $phoneNumberValidationResult = Mage::helper('schrackcustomer/phone')->validatePhonenumbers($postRegistrationData);
        if (is_array($phoneNumberValidationResult) && !empty($phoneNumberValidationResult)) {
            $errors['errormsg'] = $phoneNumberValidationResult;
            echo json_encode($errors);
            die();
        }

        if (isset($postRegistrationData['company_registration_number']) && strlen($postRegistrationData['company_registration_number']) > 14) {
            $errors['errormsg'] = $this->__('Company Registration Number too long.');
            echo json_encode($errors);
            die();
        }

        // Split form data for processing:
        if (isset($postRegistrationData['schrack_pickup']) && $postRegistrationData['schrack_pickup'] != '') {
            $branch = Mage::helper('branch')->findBranch($postRegistrationData['schrack_pickup'])->getBranchId();
        } else {
            $branch = Mage::getStoreConfig('schrack/general/branch');
        }

        if ($postRegistrationData['request-type-definition'] == 'light-registration') {
            $postRegistrationData['schrack_prospect_type'] = 0;
        }

        if ($postRegistrationData['request-type-definition'] == 'full-registration') {
            $postRegistrationData['schrack_prospect_type'] = 1;
        }

        if (!$branch) {
            $branch = Mage::getStoreConfig('schrack/general/branch');
        }

        // Merge in fixed data
        $postRegistrationData['wws_branch_id'] = $branch;
        $postRegistrationData['sales_area'] = $branch;
        $postRegistrationData['schrack_wws_branch_id'] = $postRegistrationData['wws_branch_id'];
        if ( !Mage::getStoreConfig('schrack/vatcheck/contry_selectable') ) {
            $strCountry = Mage::helper('core')->getDefaultCountry();
            $strCountry = str_replace('COM', '', $strCountry);
            $postRegistrationData['country_id'] = $strCountry;
        }

        if ($postRegistrationData['schrack_telephone'] == '' || $postRegistrationData['schrack_telephone'] == null) {
            $postRegistrationData['schrack_telephone'] = $postRegistrationData['schrack_mobile_phone'];
            if ($postRegistrationData['schrack_telephone'] == '' || $postRegistrationData['schrack_telephone'] == null) {
                $errors['errormsg'] = $this->__('Incomplete telephone number');
                echo json_encode($errors);
                die();
            }
        }

        $postRegistrationData['schrack_telephone'] = str_replace(array(' ', '-'), array('', ''), $postRegistrationData['schrack_telephone']);

        $postRegistrationData['schrack_fax'] = str_replace(array(' ', '-'), array('', ''), $postRegistrationData['schrack_fax']);

        $postRegistrationData['schrack_mobile_phone'] = str_replace(array(' ', '-'), array('', ''), $postRegistrationData['schrack_mobile_phone']);

        if ($postRegistrationData['telephone_company'] == '+') $postRegistrationData['telephone_company'] = '';
        if (strlen($postRegistrationData['telephone_company']) > 0 && strlen($postRegistrationData['telephone_company']) < 7) {
            if ($postRegistrationData['schrack_telephone']) {
                $postRegistrationData['telephone_company'] = $postRegistrationData['schrack_telephone'];
            }
        } else {
            $postRegistrationData['telephone_company'] = str_replace(' ', '', $postRegistrationData['telephone_company']);
        }

        // SPECIAL : New Advisor Route for registration, if required:
        if (strlen(Mage::getStoreConfig('schrack/customer/registration_advisor_redirect')) > 2) {
            $postRegistrationData['schrack_advisor_principal_name'] = Mage::getStoreConfig('schrack/customer/registration_advisor_redirect');
        } else {
            $postRegistrationData['schrack_advisor_principal_name'] = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
        }

        if ($postRegistrationData['student_status'] == 1) {
            $postRegistrationData['account_type'] = "44";

            // SPECIAL : New Advisor Route for registration - especially for pupils - , if required:
            if (strlen(Mage::getStoreConfig('schrack/customer/registration_advisor_redirect_for_pupils')) > 2) {
                $postRegistrationData['schrack_advisor_principal_name'] = Mage::getStoreConfig('schrack/customer/registration_advisor_redirect_for_pupils');
            } else {
                $postRegistrationData['schrack_advisor_principal_name'] = Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor();
            }
        }

        $postRegistrationData['advisor_principal_name'] = $postRegistrationData['schrack_advisor_principal_name'];
        $postRegistrationData['schrack_acl_role_id'] = Mage::helper('schrack/acl')->getAdminRoleId();
        $postRegistrationData['currency_code'] = Mage::app()->getStore()->getBaseCurrencyCode();
        $postRegistrationData['shop_language'] = strtoupper(substr(Mage::app()->getLocale()->getLocaleCode(), 0 , 2));
        // Fix for Saudi Arabia:
        if (stristr($postRegistrationData['shop_language'], 'AR')) $postRegistrationData['shop_language'] = 'EN';
        if (Mage::getStoreConfig('schrack/new_self_registration/triggerLocalVat') && $postRegistrationData['vat_special'] == 'vat_local') {
            $postRegistrationData['vat_local_number'] = strtoupper(str_replace(' ', '', $postRegistrationData['vat_identification_number']));
            // Only one type of VAT is needed, so just remove the normal, if the local VAT is existing:
            $postRegistrationData['vat_identification_number'] = '';
        }

        if (isset($postRegistrationData['name1']) && strlen($postRegistrationData['name1']) == 1) {
            $postRegistrationData['name1'] = $postRegistrationData['name1'] . 'NN';
        }

        // Implementing 30 character limit also to backend side:
        if (isset($postRegistrationData['name1']) && strlen($postRegistrationData['name1']) > 30) {
            $errors['errormsg'] = $this->__('Cannot save the customer.');
            echo json_encode($errors);
            die();
        }

        if (isset($postRegistrationData['name2']) && strlen($postRegistrationData['name2']) > 30) {
            $errors['errormsg'] = $this->__('Cannot save the customer.');
            echo json_encode($errors);
            die();
        }

        if (isset($postRegistrationData['name3']) && strlen($postRegistrationData['name3']) > 30) {
            $errors['errormsg'] = $this->__('Cannot save the customer.');
            echo json_encode($errors);
            die();
        }

        try {
            // $customer->sendNewAccountEmail('confirmation', $session->getBeforeAuthUrl());
            // $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail())));

            // Put all neccesary user data into the message queue:
            $postRegistrationData['prospect_source'] = 0;  // SHOP sends always 0 as source

            unset($postRegistrationData['company_prefix']); // Delete Company Salutation from Message -> S4Y sets by itself, if nothing comes from shop!!

            // SPAM-Protection ("pseudonym")
            if (!isset($postRegistrationData['pseudonym'])) die();
            if (isset($postRegistrationData['pseudonym']) && $postRegistrationData['pseudonym'] == '') die();
            $postRegistrationData['email'] = $postRegistrationData['pseudonym'];

            Mage::helper('schrackcustomer')->rememberPasswordHash($postRegistrationData['email'],$postRegistrationData['schrack_password']);
            $prospect = Mage::getSingleton('crm/connector')->putProspect($postRegistrationData);

            $postRegistrationData['DEV-HINT'] = date('Y-m-d H:i:s') . ' NEW_PROSPECT (Registration Only Page)';

            if ($postRegistrationData['request-type-definition'] == 'light-registration') {
                $postRegistrationData['vat_identification_number'] = '';
                $rightsInformationNoticeRegistration = 'Light Registration';
                $confirmationAGBCheckboxText = $this->__("Schrack AGB Checkbox Confirm Text");
            } else{
                $rightsInformationNoticeRegistration = 'FULL Registration';
                $confirmationAGBCheckboxText = $this->__("Checkout Terms and Conditions Complete"); 
            }

            unset($postRegistrationData['schrack_password']);
            unset($postRegistrationData['schrack_password_confirmation']);
            Mage::log($postRegistrationData, null, '/prospects/prospects_reg.log');

            // Save also rightsinformation data:
            $email                       = addslashes($postRegistrationData['email']);
            $confirmationText            = $this->__("DSGVO Schrack Confirm Text");

            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDataProtection')) == 1) {
                $confirmationDataProtectionCheckboxText  = $this->__("Schrack DataProtection Checkbox Confirm Text");
                $confirmationDataProtectionCheckboxValue = 1;
            } else {
                $confirmationDataProtectionCheckboxText  = '';
                $confirmationDataProtectionCheckboxValue = 0;
            }

            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDSGVO')) == 1) {
                $confirmationDSGVOCheckboxText  = $this->__("Schrack DSGVO Checkbox Confirm Text");
                $confirmationDSGVOCheckboxValue = 1;
            }else {
                $confirmationDSGVOCheckboxText  = '';
                $confirmationDSGVOCheckboxValue = 0;
            }

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            $query = "INSERT INTO customer_dsgvo SET email = '" . $email . "',";

            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDSGVO')) == 1) {
                $query .= " schrack_confirmed_dsgvo = " . $confirmationDSGVOCheckboxValue . ",";
                $query .= " schrack_confirmed_dsgvo_confirm_text = '" . addslashes($confirmationText) . "',";
                $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDSGVOCheckboxText) . "',";
            } else {
                // PHU-2021-05-27 - Change: now DSGVO was merged into general data protection declaration confirmation:
                $query .= " schrack_confirmed_dsgvo = " . $confirmationDataProtectionCheckboxValue . ",";
                $query .= " schrack_confirmed_dsgvo_confirm_text = '" . 'n.a.' . "',";
                $query .= " schrack_confirmed_dsgvo_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
            }
            // PHU-2021-05-27 - Change: AGB Confirmation only in checkout required:
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxAGB')) == 1) {
                $query .= " schrack_confirmed_agb = 1,";
            } else {
                $query .= " schrack_confirmed_agb = 0,";
            }
            $query .= " schrack_confirmed_agb_confirm_checkboxtext = '" . addslashes($confirmationAGBCheckboxText) . "',";
            if (intval(Mage::getStoreConfig('schrack/dsgvo/activateRegistrationCheckboxDataProtection')) == 1) {
                $query .= " schrack_confirmed_dataprotection = " . $confirmationDataProtectionCheckboxValue . ",";
            } else {
                $query .= " schrack_confirmed_dataprotection = 0,";
            }
            $query .= " schrack_confirmed_dataprotection_confirm_checkboxtext = '" . addslashes($confirmationDataProtectionCheckboxText) . "',";
            $query .= " schrack_confirmed_rightsinformation_notice = '" . $rightsInformationNoticeRegistration . "',";
            $query .= " schrack_confirmed_rightsinformation_date = '" . date('Y-m-d H:i:s') . "'";

            $writeConnection->query($query);

            // Also finish user terms process:
            // Getting current version:
            $query = "SELECT * FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $termsId      = $recordset['entity_id'];
                    $termsVersion = $recordset['version'];
                    $contentHash  = $recordset['content_hash'];
                }
            }

            $requestIpAddressRemote = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            $requestIpAddress = ((isset($_SERVER['X_FORWARDED_FOR']) && $_SERVER['X_FORWARDED_FOR']) ? '/' . $_SERVER['X_FORWARDED_FOR'] : '');

            $query  = "SELECT client_terms_content_hash FROM schrack_terms_of_use_confirmation";
            $query .= " WHERE user_email LIKE '" . $email . "'";
            $query .= " AND terms_id = " . $termsId;
            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() === 0) {
                // Insert new entry -> schrack_terms_of_use_confirmation (history table)!!
                $query  = "INSERT INTO schrack_terms_of_use_confirmation SET user_email = '" . $email . "',";
                $query .= " terms_id = " . $termsId . ",";
                $query .= " terms_version = '" . $termsVersion . "',";
                $query .= " client_terms_content_hash = '" . $contentHash . "',";
                $query .= " client_ip = '" . $requestIpAddress . "',";
                $query .= " client_ip_remote = '" . $requestIpAddressRemote . "',";
                $query .= " client_type = 'webshop',";
                $query .= " confirmed_at = '" . date("Y-m-d H:i:s") . "'";
                $writeConnection->query($query);
            }

            $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure' => true)));
            echo json_encode(array('success' => 'message finished'));
            die();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            echo json_encode(array('errormsg', $e->getMessage()));
            die();
        } catch (Exception $e) {
            Mage::logException($e);
            echo json_encode(array('errormsg', $e->getMessage()));
            die();
        }
        $errors['errormsg'] = $this->__('Cannot save the customer.');
        echo json_encode($errors);
        die();
    }

    /**
     * Forgot customer account information page
     */
    public function editpasswordAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            $customer->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }
    /**
     * Set password first time page ###
     */
    public function confirmAndSetPasswordAction () {
        $id  = $this->getRequest()->getParam('id', false);
        $key = $this->getRequest()->getParam('key', false);

        try {
            $customer = $this->_getModel('customer/customer')->load($id);
            if ( ! $customer || ! $customer->getId() ) {
                throw new Exception($this->__('Wrong customer account specified.'));
            }
            if ( ! $customer->getConfirmation() || $customer->getConfirmation() !== $key ) {
                throw new Exception($this->__('Wrong confirmation key.'));
            }
        }
        catch ( Exception $e ) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }

        $session = $this->_getSession();
        if ( $session->isLoggedIn() ) {
            $session->logout()->regenerateSessionId();
        }

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_confirm_setpw');
        $block->setSecurityKey($key);
        $block->setCustomerId($id);
        $block->setCustomer($customer);

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

	public function confirmAndSetPasswordPostAction () {
        $redirectParent = 'customer/account';
        $redirectThis   = 'customer/account/confirmAndSetPassword';

		if (    ! $this->_validateFormKey()
		     || ! $this->getRequest()->isPost()
             || Mage::getSingleton('customer/session')->isLoggedIn() ) { // yes, this is only allowed when we are not logged in.
			return $this->_redirect($redirectParent);
		}

        $newPass        = $this->getRequest()->getPost('password');
        $confPass       = $this->getRequest()->getPost('confirmation');
        $customerId     = $this->getRequest()->getPost('customer_id');
        $securityKey    = $this->getRequest()->getPost('security_key');

        $customer = Mage::getModel('schrackcustomer/customer')->load($customerId);
        if ( ! $customer || ! $customer->getId() ) {
            $errors[] = $this->__('Wrong customer account specified.');
        }

        if ( empty($newPass) || empty($confPass) ) {
            $errors[] = $this->__('Password fields can\'t be empty.');
        }

        if ( $msg = Mage::helper('customer')->checkNewPasswordReturningErrorMessage($newPass,$confPass) ) {
            $errors[] = $msg;
        }

        if ( ! $customer->getConfirmation() || $customer->getConfirmation() !== $securityKey ) {
            $errors[] = $this->__('Wrong confirmation key.');
        }

        if ( empty($errors) ) {
            $customer->setPassword($newPass);
            $customer->setPasswordConfirmation($confPass);
            $customer->setConfirmation(null);
            $customerErrors = $customer->validate();
            if ( is_array($customerErrors) ) {
                $errors = array_merge($errors, $customerErrors);
            }
        }
        if ( ! empty($errors) ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ( $errors as $message ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect($redirectThis,array('id' => $customerId, 'key' => $securityKey));
            return $this;
        }
        try {
            $customer->save();
        } catch ( Exception $e ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Can\'t save customer'));
            return $this->_redirect($redirectThis,array('id' => $customerId, 'key' => $securityKey));
        }
        $this->_getSession()->addSuccess($this->__('New account confirmed'));
        $this->_getSession()->setCustomerAsLoggedIn($customer);
        $this->_redirectSuccess(Mage::getUrl($redirectParent));
    }


    /**
     * reset forgotten password with link from email ###
     */
    public function changeForgottenPasswordLinkAction () {
        $id  = $this->getRequest()->getParam('id', false);
        $key = $this->getRequest()->getParam('key', false);

        try {
            $customer = $this->_getModel('customer/customer')->load($id);
            if ( ! $customer || ! $customer->getId() ) {
                throw new Exception($this->__('Wrong customer account specified.'));
            }
            if ( $customer->getConfirmation() && $customer->getConfirmation() > '' ) {
                $value = Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail());
                $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                throw new Exception($this->__($message));
            }
            if ( ! $customer->getSchrackChangepwToken() || $customer->getSchrackChangepwToken() !== $key ) {
                throw new Exception($this->__('Wrong security token.'));
            }
        }
        catch ( Exception $e ) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }

        $session = $this->_getSession();
        if ( $session->isLoggedIn() ) {
            $session->logout()->regenerateSessionId();
        }

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_confirm_setpw');
        $block->setSecurityKey($key);
        $block->setCustomerId($id);
        $block->setCustomer($customer);
        $block->setPostUrl('customer/account/changeForgottenPasswordLinkPost');

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

	public function changeForgottenPasswordLinkPostAction () {
        $redirectParent = 'customer/account';
        $redirectThis = 'customer/account/changeForgottenPasswordLink';

		if (    ! $this->_validateFormKey()
		     || ! $this->getRequest()->isPost()
             || Mage::getSingleton('customer/session')->isLoggedIn() ) { // yes, this is only allowed when we are not logged in.
			return $this->_redirect($redirectParent);
		}

        $newPass        = $this->getRequest()->getPost('password');
        $confPass       = $this->getRequest()->getPost('confirmation');
        $customerId     = $this->getRequest()->getPost('customer_id');
        $securityKey    = $this->getRequest()->getPost('security_key');

        $customer = Mage::getModel('schrackcustomer/customer')->load($customerId);
        if ( ! $customer || ! $customer->getId() ) {
            $errors[] = $this->__('Wrong customer account specified.');
        }

        if ( empty($newPass) || empty($confPass) ) {
            $errors[] = $this->__('Password fields can\'t be empty.');
        }

        if ( $msg = Mage::helper('customer')->checkNewPasswordReturningErrorMessage($newPass,$confPass) ) {
            $errors[] = $msg;
        }

        if ( ! $customer->getSchrackChangepwToken() || $customer->getSchrackChangepwToken() !== $securityKey ) {
            $errors[] = $this->__('Wrong confirmation key.');
        }

        if ( empty($errors) ) {
            $customer->setPassword($newPass);
            $customer->setPasswordConfirmation($confPass);
            $customer->setSchrackChangepwToken(null);
            $customerErrors = $customer->validate();
            if ( is_array($customerErrors) ) {
                $errors = array_merge($errors, $customerErrors);
            }
        }

        if ( ! empty($errors) ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ( $errors as $message ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect($redirectThis,array('id' => $customerId, 'key' => $securityKey));
            return $this;
        }
        try {
            $customer->save();
        } catch ( Exception $e ) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Can\'t save customer'));
            return $this->_redirect($redirectThis,array('id' => $customerId, 'key' => $securityKey));
        }
        $this->_getSession()->addSuccess($this->__('Your new password has been saved successfully. Note: please mind that you could possibly have to log in with the new password in other online tools aswell!'));
        $this->_getSession()->setCustomerAsLoggedIn($customer);
        $this->_redirectSuccess(Mage::getUrl($redirectParent));
    }


    /**
     * Payment customer account information page
     */
    public function editpaymentAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            $customer->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }
    /**
     * Pikup customer account information page
     */
    public function editpickupAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            $customer->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

    /**
     * Payment customer account information page
     */
    public function editotheradvisorsAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('customer_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (!empty($data)) {
            $customer->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            // TODO
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

    /**
     * Manage customer account information page
     */
    public function manageaccountAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function vcardAction () {
        $email = $this->getRequest()->getParam('email');
        if ( $email && strpos($email,'@schrack.') !== false ) {
            $contact = Mage::getModel('customer/customer')->loadByEmail($email);
            $orgName = 'SCHRACK TECHNIK';
            $urlArray = explode('/',Mage::getBaseUrl());
            array_pop($urlArray);
            array_pop($urlArray);
            $webSite = implode('/',$urlArray);

            $imageUrl = Mage::getStoreConfig('schrack/general/imageserver') . 'mab58/' . $email . '.jpg';

            // obtaining image from server
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $image = curl_exec($ch);
            curl_close($ch);

            $fp = fopen($ch, "r");
            fread($fp, $image);
            fclose($fp);

            $photo = base64_encode($image);

        } else {
            $id = $this->getRequest()->getParam('id');
            $contact = Mage::getModel('customer/customer')->load($id);
            $account = $contact->getAccount();
            $orgName = $account ? $account->getName(true) : false;
            $photo = $webSite = false;
            if ( $contact->getSchrackWwsCustomerId() != $this->_getSession()->getCustomer()->getSchrackWwsCustomerId() ) {
                $this->_redirect('/');
            }
        }
        $data = $contact->getData();
        if ( $contact->getId()  ) {
            mb_internal_encoding("UTF-8");
            mb_http_output("UTF-8");
            $vcardEol = PHP_EOL;
            $vCard = "BEGIN:VCARD" . $vcardEol
                   . "VERSION:3.0" . $vcardEol
                   . "N:" . $data['lastname'] . ";" . $data['firstname'] . ";" . $data['middlename'] . ";" . $data['prefix'] . ";" . $vcardEol
                   . "FN:". $data['prefix'] . ' ' . $data['firstname'] . (isset($data['middlename']) && $data['middlename'] > ' ' ? ' ' . $data['middlename'] : '')  . ' ' . $data['lastname'] . $vcardEol
                   . (isset($data['schrack_telephone'])    && $data['schrack_telephone'] > ' '    ? "TEL;TYPE=WORK,VOICE:" . $data['schrack_telephone']    . $vcardEol : '')
                   . (isset($data['schrack_mobile_phone']) && $data['schrack_mobile_phone'] > ' ' ? "TEL;TYPE=CELL:"       . $data['schrack_mobile_phone'] . $vcardEol : '')
                   . (isset($data['schrack_fax'])          && $data['schrack_fax'] > ' '          ? "TEL;TYPE=WORK,FAX:"   . $data['schrack_fax']          . $vcardEol : '')
                   . ($orgName ? ("ORG:" . $orgName . $vcardEol) : '')
                   . "EMAIL;TYPE=PREF,INTERNET:" . $contact->getEmailAddress() . $vcardEol
                   . ($webSite ? ("URL:" . $webSite . $vcardEol) : '')
                   #. ($photo ? ("PHOTO;TYPE=JPEG;ENCODING=b:" . $photo . $vcardEol) : '')
                   . "PHOTO;TYPE=JPEG;ENCODING=b:" . $photo . $vcardEol
                   . "REV:" . $data['updated_at'] . 'Z' . $vcardEol
                   . "END:VCARD" . $vcardEol;

            header('Content-Type: text/x-vcard;charset=utf-8;');
            header("Content-Disposition: attachment; filename=vcard.vcf");
            header("Pragma: public");
            echo $vCard;
            die();
        } else {
            $this->_redirect('/');
        }
    }


    protected function _getRefererUrl()
    {
        $refererUrl = parent::_getRefererUrl();
        if ($url = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME)) {
            $refererUrl = Mage::helper('core')->urlDecodeAndEscape($url);
        }
        return $refererUrl;
    }

    private function logAcceptOffer ( $s ) {
        if ( ! self::LOG_ACCEPT_OFFER ) {
            return;
        }
        Mage::log($s,null,self::LOG_ACCEPT_OFFER_FILE);
    }

    //====================================== setActAsCustomerFavouriteListAction
    public function setActAsCustomerFavouriteListAction() {
    //==========================================================================
        $customer = $this->_getSession()->getCustomer();
        if(!$this->getRequest()->getRawBody()){
            $customer->setData('schrack_customer_favorite_list', '{"customers":[]}');
        } else {
            $customer->setData('schrack_customer_favorite_list', json_encode($this->getRequest()->getPost()));
        }
        $customer->save();


    } //========================== setActAsCustomerFavouriteListAction ***END***

    public function getCustomerSearchResultsAction() {
        $customer = $this->_getSession()->getCustomer();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        if ($customer) {
            $customerGroupId = $customer->getGroupId();
            $query = "SELECT customer_group_code FROM customer_group WHERE customer_group_id = " . $customerGroupId;
            $result = $readConnection->query($query);
            if ($result->rowCount() > 0) {
                foreach ($result as $recordset) {
                    if ($recordset['customer_group_code'] != 'Schrack Employee') {
                        die();
                    }
                }
            }
        } else {
            die();
        }


        $userSearchQuery                   = $this->getRequest()->getParam('search_query');
        $userSearchQueryLimit              = intval($this->getRequest()->getParam('search_query_limit'));
        $userSearchQueryOnlyShowMyContacts = $this->getRequest()->getParam('search_only_show_my_contacts');
        $userSearchQueryFiltered = preg_replace('/["\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $userSearchQuery);
        $countResultItems        = 0;
        $emailFromEmployee       = '';
        $emailFromEmployeePrefix = '';
        $queryShowOnlyMyContacts = '';
        //------------ prepare customer_advisor_principal name for cache storage
        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        $EmployeeShortInfo = [];
        $attributes = [];
        $cacheID = 'EmployeeShortInfo'; // Name, email1, principal_user_name
        if ( $cacheRes = $cache->load($cacheID) ) {
            $EmployeeShortInfo = unserialize($cacheRes);
        } else {
            //--------------- get attribute_id for attr. 'firstname' + 'lastname
            $sql = "SELECT attribute_id, attribute_code FROM eav_attribute WHERE attribute_code IN('firstname','lastname') AND entity_type_id=1";
            //------------------------------------------------------------------
            $dbRes = $readConnection->fetchAll($sql);
            //------------------------------------------------------------------
            foreach ( $dbRes as $row ) {
                $attributes[$row['attribute_code']] = $row['attribute_id'];
            }

            //----------------------------------- get 'firstname' + 'lastname of
            //---------------------------- connected principal for user/customer
            $sql = " SELECT CEV.value as firstname, CEV2.value as lastname, CE.entity_id, CE.email, CE.schrack_user_principal_name FROM customer_entity AS CE"
                 . " LEFT JOIN customer_entity_varchar as CEV ON CEV.entity_id = CE.entity_id AND CEV.attribute_id = ".$attributes['firstname']
                 . " LEFT JOIN customer_entity_varchar as CEV2 ON CEV2.entity_id = CE.entity_id AND CEV2.attribute_id = ".$attributes['lastname']
                 . " WHERE group_id = 4";
            //------------------------------------------------------------------
            $dbRes = $readConnection->fetchAll($sql);
            //------------------------------------------------------------------
            foreach ( $dbRes as $row ) {
                $EmployeeShortInfo[$row['email']] = $row['firstname'].' '.$row['lastname'];
                $EmployeeShortInfo[$row['schrack_user_principal_name']] = $row['firstname'].' '.$row['lastname'];
            }
            $cache->save(serialize($EmployeeShortInfo),$cacheID,array(),21600);
        }


        //--------- get 'street','city', 'postcode' for customer billing address
        $billingAdresses = [];
        $cacheID = 'CustomerAdressInfo'; // Name, email1, principal_user_name
        if ( $cacheRes = $cache->load($cacheID) ) {
            $billingAdresses = unserialize($cacheRes);
        } else {
            $sql = "SELECT attribute_id, attribute_code FROM eav_attribute WHERE attribute_code IN('street','city','postcode') AND entity_type_id=2";
            //----------------------------------------------------------------------
            $dbRes = $readConnection->fetchAll($sql);
            //----------------------------------------------------------------------
            foreach ($dbRes as $row) {
                $billingAdresses[$row['attribute_code']] = $row['attribute_id'];
            }
            $cache->save(serialize($billingAdresses),$cacheID,array(),21600);
        }
        if ($userSearchQueryOnlyShowMyContacts == 'on') {
            $customer = $this->_getSession()->getCustomer();
            $emailFromEmployee = $customer->getEmail();
            $emailFromEmployeeExploded = explode('@', $emailFromEmployee);
            $emailFromEmployeePrefix = $emailFromEmployeeExploded[0];
            $queryShowOnlyMyContacts = " AND a.advisor_principal_name LIKE '" . $emailFromEmployeePrefix . "%'";#

        }

        if (is_numeric($userSearchQueryFiltered)) {
            $where = " WHERE a.wws_customer_id LIKE '" . $userSearchQueryFiltered . "%'";
            $orderBy = " ORDER BY a.wws_customer_id";
        } else {
            $where = " WHERE (a.name1 LIKE '%" . $userSearchQueryFiltered . "%'"
                   . " OR a.name2 LIKE '%" . $userSearchQueryFiltered . "%')";
            $orderBy = " ORDER BY a.name1";
        }

        $query = " SELECT a.name1, a.name2, a.advisor_principal_name, a.wws_customer_id, vc1.value AS street, vc2.value AS city, vc3.value AS postcode  FROM account a"
               . " JOIN customer_entity c                   ON a.account_id = c.schrack_account_id AND c.schrack_wws_contact_number = -1"
               . " JOIN customer_address_entity addr        ON addr.parent_id = c.entity_id AND addr.schrack_wws_address_number = 0"
               . " JOIN customer_address_entity_text    vc1 ON vc1.entity_id = addr.entity_id AND vc1.attribute_id = " . $billingAdresses['street']
               . " JOIN customer_address_entity_varchar vc2 ON vc2.entity_id = addr.entity_id AND vc2.attribute_id = " . $billingAdresses['city']
               . " JOIN customer_address_entity_varchar vc3 ON vc3.entity_id = addr.entity_id AND vc3.attribute_id = " . $billingAdresses['postcode']
               . $where
               . $queryShowOnlyMyContacts
               . $orderBy
               ;

        if ($userSearchQueryLimit > 0) $query .= " LIMIT " . $userSearchQueryLimit;

        $queryResult = $readConnection->query($query);

        $responseDataNames     = array();
        $responseDataWWSid     = array();
        $responseEmployeeNames = array();
        $responseEmployeeRealNames = array();

        if ($queryResult->rowCount() > 0) {
            $index = 0;
            foreach ($queryResult as $recordset) {
                if ($recordset['wws_customer_id']) {
                    $responseDataNames[$index] = htmlspecialchars($recordset['name1'].' '.$recordset['name2'], ENT_QUOTES,'UTF-8');
                    $responseDataWWSid[$index] = $recordset['wws_customer_id'];
                    if ($userSearchQueryOnlyShowMyContacts == 'off') {
                        $principalNameFromEmployeeExploded = explode('@', $recordset['advisor_principal_name']);
                        $principalNameFromEmployeePrefix = $principalNameFromEmployeeExploded[0];
                        $responseEmployeeNames[$index] = $principalNameFromEmployeePrefix;
                    } else {
                        $responseEmployeeNames[$index] = '';
                    }
                    //-------- extend result with full name of advisor principal
                    $responseEmployeeRealNames[$index] = $this->__('not supervised');
                    $advisor_principal_mail = explode('/', $recordset['advisor_principal_name']);
                    if(array_key_exists($advisor_principal_mail[0], $EmployeeShortInfo)){
                        $responseEmployeeRealNames[$index] = $EmployeeShortInfo[$advisor_principal_mail[0]];
                    }
                    $responseDataZip[$index] = $recordset['postcode'];
                    $responseDataCity[$index] = $recordset['city'];
                    $responseDataStreet[$index] = $recordset['street'];
                    $responseDataAdress[$index] = $responseDataStreet[$index] . ', ' . $recordset['postcode'] . ' ' . $recordset['city'];

                    $index++;
                }
            }

            $countResultItems = count($responseDataNames);
        }

        echo json_encode(array(
            'list_description' => $responseDataNames,
            'list_wws_ids' => $responseDataWWSid,
            'list_emplyee' => $responseEmployeeNames,
            'list_zip' => $responseDataZip,
            'list_city' => $responseDataCity,
            'list_street'=>$responseDataStreet,
            'results' => $countResultItems,
            'aac_employee_real_name' => $responseEmployeeRealNames,
            'aac_adress' => $responseDataAdress,
            'aac_formkey' => Mage::getSingleton('core/session')->getFormKey()
        ));
    }

    // public function editpickupAction()
    public function customskusAction () {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function customskusDownloadAllProductsCsvAction () {
        Mage::helper('schrack/csv')->createCsvDownloadCustomSKUs(false);
    }

    public function customskusDownloadModifiedProductsCsvAction () {
        Mage::helper('schrack/csv')->createCsvDownloadCustomSKUs(true);
    }

    public function customskusUploadCsvAction () {
        try {
            $cnt = Mage::helper('schrackcustomer/customSkuUpload')->handleCsvUpload();
            Mage::getSingleton('customer/session')->addSuccess($cnt . ' ' . $this->__('individual article number was saved.'));
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            Mage::getSingleton('customer/session')->addError($ex->getMessage());
        }
        $this->_redirect('customer/account/customskus/');
    }
}

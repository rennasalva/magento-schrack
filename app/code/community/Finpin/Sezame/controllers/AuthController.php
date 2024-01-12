<?php

class Finpin_Sezame_AuthController extends Mage_Core_Controller_Front_Action
{

    public function loginAction ()
    {
        Mage::getSingleton('core/session')->setSezameAuthId(null);
        Mage::getSingleton('core/session')->setSezameUsername(null);
        Mage::getSingleton('core/session')->setSezameUsed(true);
        Mage::getSingleton('core/session')->setSezameAuthStarted(time());
        Mage::getSingleton('core/session')->getMessages(true); // clear error messages

        /** @var Finpin_Sezame_Helper_Data $helper */
        $helper = Mage::helper('sezame');

        $requestObj = json_decode(Mage::app()->getRequest()->getRawBody());

        /** @var Finpin_Sezame_Model_Auth $model */
        $model = Mage::getModel('sezame/auth');

        $loginRequest = $model->login($requestObj->auth->username);

        $ret = new stdClass();
        $ret->redirect = null;
        $ret->status = 'initiated';

        try {

            $loginResponse = $loginRequest->send();
            if ($loginResponse->isNotfound())
                $ret->status = 'notfound';

            if ($loginResponse->isOk()) {
                Mage::getSingleton('core/session')->setSezameAuthId($loginResponse->getId());
                Mage::getSingleton('core/session')->setSezameUsername($requestObj->auth->username);
            }

        } catch (\Exception $e) {
            $ret->status = 'error';
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->getResponse()->setBody(json_encode($ret));
            return;
        }

        switch ($ret->status)
        {
            case 'initiated':
                Mage::getSingleton('core/session')->addError($this->__('The request has not been authorized in time.'));
                break;

            case 'notfound':
                Mage::getSingleton('core/session')->addError($this->__('You have to enable sezame in the customer area first.'));
                break;
        }

        $this->getResponse()->setBody(json_encode($ret));
    }

    public function statusAction()
    {
        Mage::getSingleton('core/session')->getMessages(true); // clear error messages

        /** @var Finpin_Sezame_Helper_Data $helper */
        $helper = Mage::helper('sezame');

        $ret = new stdClass();
        $ret->redirect = null;
        $ret->status = 'initiated';

        $authId = Mage::getSingleton('core/session')->getSezameAuthId();
        $username = Mage::getSingleton('core/session')->getSezameUsername();

        /** @var Finpin_Sezame_Model_Auth $model */
        $model = Mage::getModel('sezame/auth');

        $timeout = (int) $model->getConfigParam('settings/timeout');
        if (!$authId || !$username) {
            $ret->status = 'notfound';
        } else if (time() - Mage::getSingleton('core/session')->getSezameAuthStarted() > $timeout) {
            $ret->status = 'timeout';
        } else {

            try {

                $statusResponse = $model->status($authId)->send();
                if ($statusResponse->isAuthorized()) {
                    /** @var Mage_Customer_Model_Customer $customer */
                    $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($username);
                    if ($customer->getId()) {
                        $customer_id = $customer->getId();
                        $this->_loginCustomer($customer_id);
                        $ret->status = 'authorized';
                        $ret->redirect = $this->_getRedirectUrl();
                    }
                    else {
                        $ret->status = 'notfound';
                    }
                }

                if ($statusResponse->isDenied()) {
                    $ret->status = 'denied';
                }

            } catch (\Exception $e) {
                $ret->status = 'error';
                Mage::getSingleton('core/session')->addError($e->getMessage());
            }
        }

        switch ($ret->status)
        {
            case 'error':
                break;

            case 'timeout':
                Mage::getSingleton('core/session')->addError($this->__('The request has not been authorized in time.'));
                break;

            case 'initiated':
                break;

            case 'denied':
                Mage::getSingleton('core/session')->addError($this->__('The authorization has been denied.'));
                break;

            case 'notfound':
                Mage::getSingleton('core/session')->addError($this->__('You have to enable sezame in the customer area first.'));
                break;
        }

        if ($ret->status != 'initiated') {
            Mage::getSingleton('core/session')->setSezameAuthId(null);
            Mage::getSingleton('core/session')->setSezameUsername(null);
        }

        $this->getResponse()->setBody(json_encode($ret));
    }


    private function _loginCustomer($customer_id) {
        $session = Mage::getSingleton('customer/session');
        $customer = Mage::getModel('customer/customer')->load($customer_id);
        if($customer->getId()) {
            $session->setCustomerAsLoggedIn($customer);
        }
    }

    private function _getRedirectUrl()
    {
        $session = Mage::getSingleton('customer/session');

        if ($referer = $this->_getRefererUrl()) {
            if ((strpos($referer, Mage::app()->getStore()->getBaseUrl()) === 0)
                || (strpos($referer, Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)) === 0)) {
                $session->setBeforeAuthUrl($referer);
            } else {
                $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
            }
        } else {
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
        }

        return $session->getBeforeAuthUrl(true);
    }
}
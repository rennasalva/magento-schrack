<?php
/**
 * orcamultimedia
 * http://www.orca-multimedia.de
 * 
 * @author		Thomas Wild
 * @package	Orcamultimedia_Sapoci
 * @copyright	Copyright (c) 2017 orcamultimedia Thomas Wild (http://www.orca-multimedia.de)
 * 
**/

require_once 'Mage/Customer/controllers/AccountController.php';
class Orcamultimedia_Sapoci_AccountController extends Mage_Customer_AccountController
{
	
	protected $_cookieCheckActions = array('createpost');
	
	public function loginPostAction(){

        $session = $this->_getSession();
        $sapoci = $this->_getSapociParams();

        if(!isset($sapoci['HOOK_URL']) || empty($sapoci['HOOK_URL'])) {
            return parent::loginPostAction();
        }

        if ($session->isLoggedIn()) {
            $session->setData('sapoci', $sapoci);

            foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ) {
                    Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
            }
            $this->_ociPostRedirect();

            return;
        }

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
			if(empty($login)) {
				$login = $this->getRequest()->getParam('login');
            }

			$sapoci = $this->_getSapociParams();

            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
					//
					$session->setData('sapoci', $sapoci);
					if(isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL']))
						foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item )
							Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
					//
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        } elseif ($this->getRequest()->isGet()) {
            $login = $this->getRequest()->getParam('login');
			if(empty($login))
				$login = $this->getRequest()->getPost('login');
			//
			$sapoci = $this->_getSapociParams();
			//
            if (!empty($login['username']) && !empty($login['password']) && !empty($sapoci['HOOK_URL'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }

                    try {
                        /*@var $quote Mage_Sales_Model_Quote*/
                        $quote = Mage::getModel('sales/quote');
                        $quote->setStoreId(Mage::app()->getStore()->getId())
                            ->setIsActive(true)
                            ->setIsMultiShipping(false)
                            ->setCustomerId($session->getCustomerId())
                            ->save();
                        Mage::getSingleton('checkout/session')->setQuote($quote);
                    } catch (Mage_Core_Exception $e) {
                        Mage::logException($e);
                    }


					//
					$session->setData('sapoci', $sapoci);
					if(isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL']))
						foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item )
							Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
					//
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('customer')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }

        $this->_ociPostRedirect();
    }
	
	
	
	public function _getSapociParams(){
		
		$sapoci = array();

        if(Mage::getStoreConfig('sapoci/configuration/logging')){
//            Mage::log(array('GET_1', $_GET), null, 'sapoci.log');
            Mage::log(array('GET_1',"username = ". $_GET['login']['username'],"hookurl = ". $_GET['HOOK_URL']),null,'sapoci.log');
            Mage::log(array('POST_1', $_POST), null, 'sapoci.log');
            Mage::log(array('OCI_1', $sapoci), null, 'sapoci.log');
        }

		if((isset($_GET['HOOK_URL']) && !empty($_GET['HOOK_URL']))) {
			$sapoci['HOOK_URL'] = $this->getRequest()->getParam('HOOK_URL');
        } elseif ((isset($_POST['HOOK_URL']) && !empty($_POST['HOOK_URL']))) {
			$sapoci['HOOK_URL'] = $this->getRequest()->getPost('HOOK_URL');
        }

		if((isset($_GET['OCI_VERSION']) && !empty($_GET['OCI_VERSION']))) {
			$sapoci['OCI_VERSION'] = $this->getRequest()->getParam('OCI_VERSION');
        } elseif ((isset($_POST['OCI_VERSION']) && !empty($_POST['OCI_VERSION']))) {
			$sapoci['OCI_VERSION'] = $this->getRequest()->getPost('OCI_VERSION');
        }

		if((isset($_GET['OPI_VERSION']) && !empty($_GET['OPI_VERSION']))) {
			$sapoci['OPI_VERSION'] = $this->getRequest()->getParam('OPI_VERSION');
        } elseif ((isset($_POST['OPI_VERSION']) && !empty($_POST['OPI_VERSION']))) {
			$sapoci['OPI_VERSION'] = $this->getRequest()->getPost('OPI_VERSION');
        }

		if((isset($_GET['returntarget']) && !empty($_GET['returntarget']))) {
			$sapoci['returntarget'] = $this->getRequest()->getParam('returntarget');
        } elseif ((isset($_POST['returntarget']) && !empty($_POST['returntarget']))) {
			$sapoci['returntarget'] = $this->getRequest()->getPost('returntarget');
        }

        if((isset($_GET['FUNCTION']) && !empty($_GET['FUNCTION']))){
            $sapoci['FUNCTION'] = $this->getRequest()->getParam('FUNCTION');
            $sapoci['PRODUCTID'] = $this->getRequest()->getParam('PRODUCTID');
        } elseif ((isset($_POST['FUNCTION']) && !empty($_POST['FUNCTION']))) {
            $sapoci['FUNCTION'] = $this->getRequest()->getPost('FUNCTION');
            $sapoci['PRODUCTID'] = $this->getRequest()->getPost('PRODUCTID');
        }
		
		return $sapoci;
	}


    protected function _ociPostRedirect(){
        $session = $this->_getSession();
        if(isset($session['sapoci']['FUNCTION']) 
            && $session['sapoci']['FUNCTION'] == 'DETAIL' 
                && isset($session['sapoci']['PRODUCTID'])){
            $p = Mage::getModel('catalog/product')->load((int)$session['sapoci']['PRODUCTID']); 
            if(is_object($p) && $p->getId()){
                $this->_redirectUrl($p->getProductUrl());
                return;
            }
        }
        // $this->_redirect('*/*/');
        $this->_redirect('checkout/cart');
    }
}
?>

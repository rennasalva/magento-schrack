<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/

require_once 'Mage/Customer/controllers/AccountController.php';
class Orcamultimedia_Ids_AccountController extends Mage_Customer_AccountController
{
	
	protected $_cookieCheckActions = array('createpost');
	
	public function loginPostAction(){

        $session = $this->_getSession();
        $ids = $this->_getIdsParams();

        if(!isset($ids['hookurl']) || empty($ids['hookurl'])) {
            return parent::loginPostAction();
        }

        if ($session->isLoggedIn()) {
            $session->setData('ids', $ids);

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

			$ids = $this->_getIdsParams();

            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
                    }
					//
					$session->setData('ids', $ids);
					if(isset($session['ids']['hookurl']) && !empty($session['ids']['hookurl']))
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
			$ids = $this->_getIdsParams();
			//
            if (!empty($login['username']) && !empty($login['password']) && !empty($ids['hookurl'])) {
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
					$session->setData('ids', $ids);
					if(isset($session['ids']['hookurl']) && !empty($session['ids']['hookurl']))
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
	
	
	
	public function _getIdsParams(){
		
		$ids = array();

        if(Mage::getStoreConfig('ids/configuration/logging')){
            Mage::log(array('GET_1', $_GET), null, 'ids.log');
            Mage::log(array('POST_1', $_POST), null, 'ids.log');
            Mage::log(array('OCI_1', $ids), null, 'ids.log');
        }

		if((isset($_GET['hookurl']) && !empty($_GET['hookurl']))) {
			$ids['hookurl'] = $this->getRequest()->getParam('hookurl');
        } elseif ((isset($_POST['hookurl']) && !empty($_POST['hookurl']))) {
			$ids['hookurl'] = $this->getRequest()->getPost('hookurl');
        }

		if((isset($_GET['OCI_VERSION']) && !empty($_GET['OCI_VERSION']))) {
			$ids['OCI_VERSION'] = $this->getRequest()->getParam('OCI_VERSION');
        } elseif ((isset($_POST['OCI_VERSION']) && !empty($_POST['OCI_VERSION']))) {
			$ids['OCI_VERSION'] = $this->getRequest()->getPost('OCI_VERSION');
        }

		if((isset($_GET['OPI_VERSION']) && !empty($_GET['OPI_VERSION']))) {
			$ids['OPI_VERSION'] = $this->getRequest()->getParam('OPI_VERSION');
        } elseif ((isset($_POST['OPI_VERSION']) && !empty($_POST['OPI_VERSION']))) {
			$ids['OPI_VERSION'] = $this->getRequest()->getPost('OPI_VERSION');
        }

		if((isset($_GET['returntarget']) && !empty($_GET['returntarget']))) {
			$ids['returntarget'] = $this->getRequest()->getParam('returntarget');
        } elseif ((isset($_POST['returntarget']) && !empty($_POST['returntarget']))) {
			$ids['returntarget'] = $this->getRequest()->getPost('returntarget');
        }

        if((isset($_GET['FUNCTION']) && !empty($_GET['FUNCTION']))){
            $ids['FUNCTION'] = $this->getRequest()->getParam('FUNCTION');
            $ids['PRODUCTID'] = $this->getRequest()->getParam('PRODUCTID');
        } elseif ((isset($_POST['FUNCTION']) && !empty($_POST['FUNCTION']))) {
            $ids['FUNCTION'] = $this->getRequest()->getPost('FUNCTION');
            $ids['PRODUCTID'] = $this->getRequest()->getPost('PRODUCTID');
        }
		
		return $ids;
	}


    protected function _ociPostRedirect(){
        $session = $this->_getSession();
        if(isset($session['ids']['FUNCTION'])
            && $session['ids']['FUNCTION'] == 'DETAIL'
                && isset($session['ids']['PRODUCTID'])){
            $p = Mage::getModel('catalog/product')->load((int)$session['ids']['PRODUCTID']);
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

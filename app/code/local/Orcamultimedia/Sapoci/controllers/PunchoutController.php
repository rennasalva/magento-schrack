<?php 

class Orcamultimedia_Sapoci_PunchoutController extends Mage_Core_Controller_Front_Action {

	public function preDispatch(){
        
        parent::preDispatch();
        return $this;
    }


    public function loginAction(){

        $session = $this->_getSession();
        $sapoci = $this->_setSapociParams();

        if(Mage::getStoreConfig('sapoci/configuration/logging')){
            Mage::log(array('GET',"username = ". $_GET['login']['username'],"hookurl = ". $_GET['HOOK_URL']),null,'sapoci.log');
//            Mage::log(array('GET', $_GET), null, 'sapoci.log');
            Mage::log(array('POST', $_POST), null, 'sapoci.log');
            Mage::log(array('OCI', $sapoci), null, 'sapoci.log');
        }

        if ($session->isLoggedIn()) {
            if(Mage::getStoreConfig('sapoci/configuration/logging'))
                Mage::log('Is already logged in.', null, 'sapoci.log');
            $session->setData('sapoci', $sapoci);

            // if(isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL']))
            //     foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item )
            //         Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
            Mage::getSingleton('checkout/session')->clear();
            $quote = Mage::getModel('sales/quote')->setStore(Mage::app()->getStore());
            $quote->setCustomer(Mage::getSingleton('customer/session')->getCustomer())
                  /*->setIsCheckoutCart(true)*/ //Notwendig!?
                  ->setTotalsCollectedFlag(false)
                  ->collectTotals()
                  ->save();
            Mage::getSingleton('checkout/session')->replaceQuote($quote);
            
            $this->_loginPostRedirect();
            return;
        }

        $login = $this->getRequest()->getPost('login');
        if(empty($login))
			$login = $this->getRequest()->getParam('login');

        if (!empty($login['username']) && !empty($login['password'])) {
            try {
                $session->setData('sapoci', $sapoci);
                $session->login($login['username'], $login['password']);
            } catch (Mage_Core_Exception $e) {
                if(Mage::getStoreConfig('sapoci/configuration/logging'))
                    Mage::log('Login Error.', null, 'sapoci.log');
                switch ($e->getCode()) {
                    case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                        $value = $this->_getHelper('customer')->getEmailConfirmationUrl($login['username']);
                        $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                        break;
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                        $message = $e->getMessage();
                        break;
                    default:
                        $message = $e->getMessage();
                }
                $session->addError($message);
                if(Mage::getStoreConfig('sapoci/configuration/logging'))
                    Mage::log($message, null, 'sapoci.log');
                $session->setUsername($login['username']);
            } catch (Exception $e) {
                // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                if(Mage::getStoreConfig('sapoci/configuration/logging'))
                    Mage::log($e->getMessage(), null, 'sapoci.log');
            }
        } else {
            $session->addError($this->__('Login and password are required.'));
            if(Mage::getStoreConfig('sapoci/configuration/logging'))
                Mage::log('Login and password are required.', null, 'sapoci.log');
        }


        $this->_loginPostRedirect();
    }


    public function validateAction(){
    	$session = $this->_getSession();
    	if (!$session->isLoggedIn() 
    		|| $session['sapoci']['FUNCTION'] != 'VALIDATE' 
    		|| !isset($session['sapoci']['PRODUCTID'])
            || !isset($session['sapoci']['QUANTITY'])) {
            
            $this->_redirect('*/*/');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function searchAction(){
    	$session = $this->_getSession();
    	if (!$session->isLoggedIn() 
    		|| $session['sapoci']['FUNCTION'] != 'BACKGROUND_SEARCH'
    		|| !isset($session['sapoci']['SEARCHSTRING'])) {
            
            $this->_redirect('*/*/');
            return;
        }

        $queryText = Mage::helper('core/string')->cleanString(trim($session['sapoci']['SEARCHSTRING']));

        $query = Mage::getSingleton('catalogsearch/query')->loadByQuery($queryText);
        if (!$query->getId())
            $query->setQueryText($queryText);
        
        $query->setStoreId(Mage::app()->getStore()->getId());

        $this->loadLayout();
        $this->renderLayout();
    }


    protected function _setSapociParams(){
		
		$sapoci = array();
		
		if(isset($_GET['HOOK_URL']) && !empty($_GET['HOOK_URL']))
			$sapoci['HOOK_URL'] = $this->getRequest()->getParam('HOOK_URL');
		elseif(isset($_POST['HOOK_URL']) && !empty($_POST['HOOK_URL']))
			$sapoci['HOOK_URL'] = $this->getRequest()->getPost('HOOK_URL');
			
		if(isset($_GET['OCI_VERSION']) && !empty($_GET['OCI_VERSION']))
			$sapoci['OCI_VERSION'] = $this->getRequest()->getParam('OCI_VERSION');
		elseif(isset($_POST['OCI_VERSION']) && !empty($_POST['OCI_VERSION']))
			$sapoci['OCI_VERSION'] = $this->getRequest()->getPost('OCI_VERSION');
		
		if(isset($_GET['OPI_VERSION']) && !empty($_GET['OPI_VERSION']))
			$sapoci['OPI_VERSION'] = $this->getRequest()->getParam('OPI_VERSION');
		elseif(isset($_POST['OPI_VERSION']) && !empty($_POST['OPI_VERSION']))
			$sapoci['OPI_VERSION'] = $this->getRequest()->getPost('OPI_VERSION');
		
		if(isset($_GET['returntarget']) && !empty($_GET['returntarget']))
			$sapoci['returntarget'] = $this->getRequest()->getParam('returntarget');
		elseif(isset($_POST['returntarget']) && !empty($_POST['returntarget']))
			$sapoci['returntarget'] = $this->getRequest()->getPost('returntarget');

        if(isset($_GET['FUNCTION']) && !empty($_GET['FUNCTION'])){
            $sapoci['FUNCTION'] = $this->getRequest()->getParam('FUNCTION');
            if(isset($_GET['PRODUCTID']) && !empty($_GET['PRODUCTID']))
            	$sapoci['PRODUCTID'] = $this->getRequest()->getParam('PRODUCTID');
            if(isset($_GET['QUANTITY']) && !empty($_GET['QUANTITY']))
            	$sapoci['QUANTITY'] = $this->getRequest()->getParam('QUANTITY');
            if(isset($_GET['SEARCHSTRING']) && !empty($_GET['SEARCHSTRING']))
            	$sapoci['SEARCHSTRING'] = $this->getRequest()->getParam('SEARCHSTRING');
        }elseif(isset($_POST['FUNCTION']) && !empty($_POST['FUNCTION'])){
            $sapoci['FUNCTION'] = $this->getRequest()->getPost('FUNCTION');
            if(isset($_POST['PRODUCTID']) && !empty($_POST['PRODUCTID']))
            	$sapoci['PRODUCTID'] = $this->getRequest()->getPost('PRODUCTID');
            if(isset($_POST['QUANTITY']) && !empty($_POST['QUANTITY']))
            	$sapoci['QUANTITY'] = $this->getRequest()->getPost('QUANTITY');
            if(isset($_POST['SEARCHSTRING']) && !empty($_POST['SEARCHSTRING']))
            	$sapoci['SEARCHSTRING'] = $this->getRequest()->getPost('SEARCHSTRING');
        }
		
		return $sapoci;
	}


    protected function _getSession(){
        return Mage::getSingleton('customer/session');
    }


    protected function _loginPostRedirect(){

        $session = $this->_getSession();
        if(isset($session['sapoci']['FUNCTION'])){
            if($session['sapoci']['FUNCTION'] == 'DETAIL' 
                && isset($session['sapoci']['PRODUCTID'])){

	            $p = Mage::getModel('catalog/product')->load((int)$session['sapoci']['PRODUCTID']); 
	            if(is_object($p) && $p->getId()){
	                $this->_redirectUrl($p->getProductUrl());
	                return;
	            }

	        }elseif($session['sapoci']['FUNCTION'] == 'VALIDATE'){

	        	$this->_redirect('*/*/validate');
	        	return;

	        }elseif($session['sapoci']['FUNCTION'] == 'BACKGROUND_SEARCH'){

	        	$this->_redirect('*/*/search');
	        	return;

	        }
	    }

        if(Mage::getStoreConfig('sapoci/configuration/logging'))
            Mage::log('Redirecting...', null, 'sapoci.log');
        $this->_redirect('/');
    }

}

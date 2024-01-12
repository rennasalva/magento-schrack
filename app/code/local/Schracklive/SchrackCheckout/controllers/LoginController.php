<?php

require_once('app/code/local/Schracklive/SchrackCheckout/controllers/CartController.php');

class Schracklive_SchrackCheckout_LoginController extends Schracklive_SchrackCheckout_CartController {

    public function newcartAction() {
        $isloggedIn = $this->_performLogin();
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        $items = $quote->getAllItems();
        
        if (count($items) > 0) {
            $date = Mage::helper('schrackcore/locale')->getCurrentTimeString();
            if ($isloggedIn)
                $partslist = $this->_createPartslist($this->__('Cart'). ' ' . $this->__('from') . ' ' . $date);
            foreach ($items as $item) {
                $product = $item->getProduct();
                $qty = $item->getQty();
                if ($isloggedIn && $partslist) {
                    $partslist->addNewItem($product, array('qty' => $qty));
                }
                $quote->removeItem($item->getId());
            }

            if ($isloggedIn && $partslist) {
                $message = $this->__('Your cart has been moved to <a href="%s">a new partslist</a>', Mage::getUrl('schrackwishlist/partslist/view', array('id' => $partslist->getId())));
                Mage::getSingleton('core/session')->addSuccess($message);

            }
        }
        $this->_addToCartFromRequest();
        return $this->_redirect('checkout/cart/');
    }
    
    public function existingcartAction() {
        $this->_performLogin();
        $this->_addToCartFromRequest();
        return $this->_redirect('checkout/cart/');
    }
    
    private function _performLogin() {
        try {
            $token = $this->getRequest()->getParam('token');
            if (isset($token)) {
                $helper = Mage::helper('schrackcustomer/loginswitch');
                if (!$helper->loginByToken($token)) {
                    throw new Exception($this->__('Could not authenticate'));
                }
            } else {
                $email = $this->getRequest()->getParam('email');
                $password = $this->getRequest()->getParam('password');
                $customer = Mage::getModel('customer/customer');
                $customer->loadByEmail($email);

                if (!($customer && $customer->getId())) {
                    throw new Exception($this->__('Invalid Username or Password.'));
                }
                if (!$customer->authenticate($email, $password)) {
                    throw new Exception($this->__('Invalid Username or Password.'));
                }
                $session = Mage::getSingleton('customer/session');
                $session->unsetData('sapoci');
                $session->setCustomer($customer)->setCustomerAsLoggedIn($customer);

            }
            return true; /// yes, we did login
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__('Authentication error') . ': ' . $this->__($e->getMessage()));
            return false; // no, we did not login
        }
    }
    
    private function _createPartslist($description, $comment = null) {
         $model = Mage::getModel('schrackwishlist/partslist');                      
         return $model->create(Mage::getSingleton('customer/session')->getCustomer()->getId(), $description, $comment);
    }
    
 }

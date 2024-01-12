<?php

require_once('app/code/local/Schracklive/SchrackCheckout/controllers/CartController.php');

class Schracklive_SchrackCheckout_AnonymousController extends Schracklive_SchrackCheckout_CartController {

    public function newcartAction() {        
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        $items = $quote->getAllItems();

        if (count($items) > 0) {
            foreach ($items as $item) {
                $quote->removeItem($item->getId());
            }
        }
        $this->_addToCartFromRequest();
        return $this->_redirect('checkout/cart/');
    }
    
    public function existingcartAction() {
        $this->_addToCartFromRequest();
        return $this->_redirect('checkout/cart/');
    }
    
 }

<?php

class Schracklive_Promotions_IndexController extends Mage_Core_Controller_Front_Action {

    public function preDispatch () {
        parent::preDispatch();
        if ( ! $this->getRequest()->isDispatched() ) {
            return;
        }
        // ensuring login:
        if ( ! Mage::getSingleton('customer/session')->authenticate($this) ) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }


    public function indexAction () {
        $this->loadLayout();
        $this->renderLayout();
    }
}

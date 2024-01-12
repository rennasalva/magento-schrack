<?php

class Orcamultimedia_Sapoci_Model_Observer {
 
    public function removeCheckout($event) {
 
        $controller = $event->getControllerAction();
        /* @var $controller Mage_Checkout_CartController */
 
        if( $controller->getRequest()->getControllerName() == 'onepage' && Mage::helper('sapoci')->getIsPunchout() ) {
            
            $controller->getResponse()->setRedirect( Mage::getUrl('*/cart') );
            $controller->getResponse()->sendResponse();
 
            $controller->getRequest()->setDispatched( true );
        }
    }


    public function removeCustomerActions($event){

        if(!Mage::helper('sapoci')->getIsPunchout())
            return;
    
        $controller = $event->getControllerAction();
        
        $CustomerActions = array(
            //'index',  // if commented out: SAP_OCI User is allowed to enter MyAccount Area
            'edit',  // forbidden
            'editpost', // forbidden
            'forgotpassword',  // forbidden
            'forgotpasswordpost',  // forbidden
            'resetpassword',  // forbidden
            'resetpasswordpost'  // forbidden
        );
        
        if(in_array($controller->getRequest()->getActionName(), $CustomerActions)){
            $controller->getResponse()->setRedirect(Mage::getUrl('/'));
            $controller->getResponse()->sendResponse();
 
            $controller->getRequest()->setDispatched( true );
        }
    }


    public function customerLogin($event){

        if(Mage::app()->getRequest()->getControllerName() != 'punchout')
            Mage::getSingleton('customer/session')->unsetData('sapoci');

    }
 
}
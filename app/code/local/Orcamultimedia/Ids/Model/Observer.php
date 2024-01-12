<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/


class Orcamultimedia_Ids_Model_Observer {


    public function proceedCheckout($event) {
        $controller = $event->getControllerAction();
        // @var $controller Mage_Checkout_CartController
 
        if ( $controller->getRequest()->getControllerName() == 'onepage' && Mage::helper('ids')->isIdsSession() ) {
            // Allow checkout, if all following lines are commented out:
            // $controller->getResponse()->setRedirect( Mage::getUrl('*/cart') );
            // $controller->getResponse()->sendResponse();
            // $controller->getRequest()->setDispatched( true );
        }
    }


    public function removeCustomerActions($event) {
        if (!Mage::helper('ids')->isIdsSession())
            return;
    
        $controller = $event->getControllerAction();
        
        $CustomerActions = array(
            //'index',  // if commented out: IDS User is allowed to enter MyAccount Area
            'edit',  // forbidden
            'editpost', // forbidden
            'forgotpassword',  // forbidden
            'forgotpasswordpost',  // forbidden
            'resetpassword',  // forbidden
            'resetpasswordpost'  // forbidden
        );
        
        if (in_array($controller->getRequest()->getActionName(), $CustomerActions)) {
            $controller->getResponse()->setRedirect(Mage::getUrl('/'));
            $controller->getResponse()->sendResponse();
 
            $controller->getRequest()->setDispatched( true );
        }
    }


    public function customerLogin($event) {
        if (Mage::app()->getRequest()->getControllerName() != 'punchin')
            Mage::getSingleton('customer/session')->unsetData('ids');
    }
 
}

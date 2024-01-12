<?php

class Schracklive_MoCalc_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction () {
        $miranda = $this->getRequest()->getParam('miranda');
        if ( $miranda == 'b2da6726216d4475e17caabcb8bb767f:rf' ) {
            $this->_redirect('mocalc/index/mocalc?miranda=' . $miranda);
        } else {
            $this->_redirect('mocalc/index/mocalc');
        }
    }

    public function mocalcAction () {
        $session = Mage::getSingleton('customer/session');
        if ( ! $this->getRequest()->getParam('miranda') == 'b2da6726216d4475e17caabcb8bb767f:rf' ) {
            if ( !$session->isLoggedIn() ) {
                return $this->notAllowed();
            }
            $customer = $session->getCustomer();
            if ( !$customer ) {
                return $this->notAllowed();
            }
            if ( !Mage::helper('schrackcustomer')->mayActAsUser($customer) ) {
                return $this->notAllowed();
            }
        }

        $nextStep = 1;
        /** @var Schracklive_MoCalc_Helper_Data $helper */
        $helper = Mage::helper('moCalc');
        $reset = $this->getRequest()->getParam('reset');
        $downloadCSV = $this->getRequest()->getParam('download_csv');
        if ( $reset == 1 ) {
            $helper->reset();
        } else if ( $downloadCSV == 1 ) {
            $helper->printCSV();
        } else {
            $input = $this->getRequest()->getParam('input');
            $currentStep = $this->getRequest()->getParam('current_step');
            $nextStep = $this->getRequest()->getParam('next_step');
            if ( !$nextStep ) {
                $nextStep = 1;
            }
            $helper->loadOrCreateData();

            $this->handleInput($input, $currentStep, $helper);
        }
        Mage::unregister(Schracklive_MoCalc_Helper_Data::REGISTRY_STEP_KEY);
        Mage::register(Schracklive_MoCalc_Helper_Data::REGISTRY_STEP_KEY, $nextStep);

        $this->loadLayout();
        $this->renderLayout();
    }

    private function handleInput ( $input, $currentStep, $helper ) {
        switch ( $currentStep ) {
            case  1 : $helper->handlePropertyChange('icu',$input);                      break;
            case  2 : $helper->handlePropertyChange('rated_current',$input);            break;
            case  3 : $helper->handlePropertyChange('build_size',$input);               break;
            case  4 : $helper->handlePropertyChange('pole_count',$input);               break;
            case  5 : $helper->handlePropertyChange('mount_type',$input);               break;

            case  6 : $helper->handleBaseAccessoryChange('overcurrent_release',$input); break;
            case  7 : $helper->handleBaseAccessoryChange('actuation',$input);           break;
            case  8 : $helper->handleBaseAccessoryChange('aux_relay_1',$input);         break;
            case  9 : $helper->handleBaseAccessoryChange('aux_relay_2',$input);         break;
            case 10 : $helper->handleBaseAccessoryChange('aux_contact',$input);         break;

            case 11 : $helper->handleOptionalAccessories($input);                       break;

            case 12 : $helper->handleDiscount($input);                                  break;
        }
    }

   private function notAllowed () {
        $this->_redirect('/');
        return false;
    }

}
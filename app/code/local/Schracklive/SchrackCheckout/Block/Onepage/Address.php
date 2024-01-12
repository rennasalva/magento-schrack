<?php

class Schracklive_SchrackCheckout_Block_Onepage_Address extends Mage_Checkout_Block_Onepage_Abstract {

    protected function _construct() {
        $this->getCheckout()->setStepData('address', array(
            'label'     => Mage::helper('checkout')->__('Address'),
            'is_show'   => true
        ));
        parent::_construct();
        /*

          if ($this->isCustomerLoggedIn() && ($this->getCustomer()->isContact() || $this->getCustomer()->isProspect())) {
          $this->getCheckout()->setStepData('billing', 'allow', false);
          $this->getCheckout()->setStepData('billing', 'complete', true);
          }
         * 
         */
        
        $this->getCheckout()->setStepData('address', 'allow', true);
    }

    protected function getRequestreceivers() {
        $model = Mage::getModel('schracksales/requestreceiver');
        $coll = $model->getCollection();
        return $coll;
    }

}

?>

<?php

class Schracklive_Schrackcustomer_Model_Selfregistrationcheckoutoptions
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'self_registration_checkout_not_available', 'label'=>Mage::helper('schrackcustomer')->__('Self Registration Not Available')),
            array('value' => 'self_registration_checkout_with_typo', 'label'=>Mage::helper('schrackcustomer')->__('New Self Registration Form With TYPO3 Form')),
            array('value' => 'new_self_registration_checkout', 'label'=>Mage::helper('schrackcustomer')->__('New Self Registration Complete')),
        );
    }
}
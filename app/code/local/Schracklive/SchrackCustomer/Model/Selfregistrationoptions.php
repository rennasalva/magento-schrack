<?php

class Schracklive_Schrackcustomer_Model_Selfregistrationoptions
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'self_registration_not_available', 'label'=>Mage::helper('schrackcustomer')->__('Self Registration Not Available')),
            array('value' => 'old_self_registration_available', 'label'=>Mage::helper('schrackcustomer')->__('Old Self Registration Available')),
            array('value' => 'self_registration_with_typo', 'label'=>Mage::helper('schrackcustomer')->__('New Self Registration Form With TYPO3 Form')),
            array('value' => 'new_self_registration_form', 'label'=>Mage::helper('schrackcustomer')->__('New Self Registration Form Complete')),
            array('value' => 'new_light_self_registration_form', 'label'=>Mage::helper('schrackcustomer')->__('New Self Registration Form (only Light)')),
        );
    }
}
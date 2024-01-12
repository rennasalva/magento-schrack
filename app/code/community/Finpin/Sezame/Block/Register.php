<?php

class Finpin_Sezame_Block_Register extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/sezame/register');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Register')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}

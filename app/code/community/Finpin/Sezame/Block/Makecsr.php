<?php

class Finpin_Sezame_Block_Makecsr extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/sezame/makecsr');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Make CSR')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}

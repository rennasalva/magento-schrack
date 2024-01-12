<?php

class Finpin_Sezame_Block_Cancel extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/sezame/cancel');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setDisabled(strlen(Mage::getStoreConfig('sezame/credentials/certificate')) == 0)
            ->setLabel('Cancel')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}

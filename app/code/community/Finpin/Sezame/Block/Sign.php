<?php

class Finpin_Sezame_Block_Sign extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/sezame/sign');

        $cc = strlen(Mage::getStoreConfig('sezame/credentials/clientcode'));
        $ss = strlen(Mage::getStoreConfig('sezame/credentials/sharedsecret'));

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setDisabled(!$cc || !$ss)
            ->setLabel('Sign')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}

<?php

class Schracklive_SchrackAdminhtml_Block_Cacheflush extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/tools/cacheflush');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Flush Cache')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}

<?php
class Schracklive_SchrackCustomer_Model_System_Config_Source_Daytime extends Mage_Core_Model_Abstract
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray () {
        $helper = Mage::helper('adminhtml');
        $res = array(
            array('value' => '00', 'label' => $helper->__('00')),
            array('value' => '01', 'label' => $helper->__('01')),
            array('value' => '02', 'label' => $helper->__('02')),
            array('value' => '03', 'label' => $helper->__('03')),
            array('value' => '04', 'label' => $helper->__('04')),
            array('value' => '05', 'label' => $helper->__('05')),
            array('value' => '06', 'label' => $helper->__('06')),
        );
        return $res;
    }
}
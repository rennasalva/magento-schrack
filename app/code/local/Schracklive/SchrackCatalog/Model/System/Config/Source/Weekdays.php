<?php

class Schracklive_SchrackCatalog_Model_System_Config_Source_Weekdays extends Mage_Core_Model_Abstract
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray () {
        $helper = Mage::helper('adminhtml');
        $res = array(
            array('value' => 1, 'label' => $helper->__('Monday')),
            array('value' => 2, 'label' => $helper->__('Tuesday')),
            array('value' => 3, 'label' => $helper->__('Wednesday')),
            array('value' => 4, 'label' => $helper->__('Thursday')),
            array('value' => 5, 'label' => $helper->__('Friday')),
            array('value' => 6, 'label' => $helper->__('Saturday')),
            array('value' => 0, 'label' => $helper->__('Sunday')),
        );
        return $res;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray (array $arrAttributes = array()) {
        $tmp = $this->toOptionArray();
        $res = array();
        foreach ( $tmp as $ndx => $vals ) {
            $res[$vals['value']] = $vals['label'];
        }
        // TODO: filter $arrAttributes vals if given
        return $res;
    }

}
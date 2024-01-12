<?php

class Schracklive_Schrackcatalog_Model_Labelformats
{
    public function toOptionArray()
    {
        return array(
            array('value' => 3420, 'label'=>Mage::helper('schrackcatalog')->__('Avery 3420 (1,69 x 7,00; 3x17)')),
            array('value' => 3421, 'label'=>Mage::helper('schrackcatalog')->__('Avery 3421 (2,54 x 7,00; 3x11)')),
            array('value' => 3425, 'label'=>Mage::helper('schrackcatalog')->__('Avery 3425 (5,70 x 10,50; 2x5)')),
            array('value' => 3658, 'label'=>Mage::helper('schrackcatalog')->__('Avery 3658 (3,38 x 6,46; 3x8)')),
            array('value' => 3669, 'label'=>Mage::helper('schrackcatalog')->__('Avery 3669 (5,08 x 7,00; 3x5)')),
            array('value' => 4776, 'label'=>Mage::helper('schrackcatalog')->__('Avery 4776 (4,20 x 9,91; 2x6)')),
        );
    }
}
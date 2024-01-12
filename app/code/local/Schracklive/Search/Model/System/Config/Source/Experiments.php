<?php

class Schracklive_Search_Model_System_Config_Source_Experiments extends Mage_Core_Model_Abstract
{
    public function toOptionArray()
    {
        return [
            ['value' => 'fuzzy_search', 'label' => 'Fuzzy Search']
        ];
    }
}

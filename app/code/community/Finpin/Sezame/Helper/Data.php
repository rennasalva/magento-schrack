<?php

class Finpin_Sezame_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isSezameEnabled()
    {
        return Mage::getStoreConfig('sezame/settings/enabled') == '1';
    }

    /**
     * Translate
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->_getModuleName());
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }
}
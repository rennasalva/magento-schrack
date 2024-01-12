<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Locale
 *
 * @author c.friedl
 */
class Schracklive_SchrackCore_Helper_Locale {
    public function getUserLanguage() {
        list($lang, $ctry) = explode('_', Mage::app()->getLocale()->getLocaleCode());
        return $lang;       
    }
    public function getUserCountry() {
        list($lang, $ctry) = explode('_', Mage::app()->getLocale()->getLocaleCode());
        return $ctry;
    }
    
    public function getCurrentTimeString() {
        $date = new Zend_Date();
        $date->setOptions(array('format_type' => 'php'));
        $tz = Mage::getStoreConfig('general/locale/timezone');
        $date->setTimezone(Mage::getStoreConfig('general/locale/timezone'));
        return $date->toString('d.m.Y G:i:s');
    }
}

?>

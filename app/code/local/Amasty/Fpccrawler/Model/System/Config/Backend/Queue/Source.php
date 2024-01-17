<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpccrawler
 */


class Amasty_Fpccrawler_Model_System_Config_Backend_Queue_Source extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $value = $this->getValue();

        if ($value == 'fpc') {
            $configModule = Mage::getModel('core/config');
            $configModule->saveConfig('amfpc/stats/visits', 1);
            $configModule->cleanCache();
        }
    }
}

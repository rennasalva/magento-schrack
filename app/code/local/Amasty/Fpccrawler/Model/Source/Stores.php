<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpccrawler
 */


/**
 * @author Amasty
 */
class Amasty_Fpccrawler_Model_Source_Stores
{
    public function toOptionArray()
    {
        return Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm();
    }
}
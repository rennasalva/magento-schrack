<?php

/**
 * Wislist model collection
 *
 * @category   Schracklive
 * @package    Schracklive_SchrackWishlist
 * @author      c.friedl
 */
class Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource
     */
    protected function _construct()
    {
        $this->_init('schrackwishlist/partslist');
    }
}

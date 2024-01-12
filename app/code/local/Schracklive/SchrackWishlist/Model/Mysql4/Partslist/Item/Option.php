<?php

/**
 * Item option mysql4 resource model
 *
 * @category    Mage
 * @package    Mage_Wishlist
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Option extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('schrackwishlist/partslist_item_option', 'option_id');
    }
}

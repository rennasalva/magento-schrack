<?php


/**
 * Wishlist item model resource
 *
 * @category    Mage
 * @package    Mage_Wishlist
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize connection and define main table
     *
     */
    protected function _construct()
    {
        $this->_init('schrackwishlist/partslist_item', 'partslist_item_id');
    }

    /**
     * Load item by wishlist, product and shared stores
     *
     * @param Schracklive_SchrackWishlist_Model_Partslist_Item $object
     * @param int $wishlistId
     * @param int $productId
     * @param array $sharedStores
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item
     */
    public function loadByProductPartslist($object, $wishlistId, $productId, $sharedStores)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getMainTable())
            ->where('partslist_id=?', $wishlistId)
            ->where('product_id=?', $productId)
            ->where('store_id IN(?)', $sharedStores);

        $data = $adapter->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $this;
    }
}

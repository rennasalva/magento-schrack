<?php

/**
 * Partslist
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_Model_Mysql4_Partslist extends Mage_Core_Model_Mysql4_Abstract {
    protected $_itemsCount = null;

    protected $_customerIdFieldName = 'customer_id';

    protected function _construct() {
        $this->_init('schrackwishlist/partslist', 'partslist_id');
    }
    
    public function getCustomerIdFieldName()
    {
        return $this->_customerIdFieldName;
    }

    public function setCustomerIdFieldName($fieldName)
    {
        $this->_customerIdFieldName = $fieldName;
        return $this;
    }

    public function fetchItemsCount(Schracklive_SchrackWishlist_Model_Partslist $partslist)
    {
        $collection = $partslist->getItemCollection()
            ->addStoreFilter()
            ->addPartslistFilter($partslist)
            ->setVisibilityFilter();

        $this->_itemsCount = $collection->getSize();

        return $this->_itemsCount;
    }
}
?>

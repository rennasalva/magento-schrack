<?php


/**
 * Partslist item collection
 *
 */
class Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Product Visibility Filter to product collection flag
     *
     * @var bool
     */
    protected $_productVisible = false;

    /**
     * Product Salable Filter to product collection flag
     *
     * @var bool
     */
    protected $_productSalable = false;

    /**
     * If product out of stock, its item will be removed after load
     *
     * @var bool
     */
    protected $_productInStock = false;

    /**
     * Product Ids array
     *
     * @var array
     */
    protected $_productIds = array();

    /**
     * Store Ids array
     *
     * @var array
     */
    protected $_storeIds = array();

    /**
     * Add days in whishlist filter of product collection
     *
     * @var boolean
     */
    protected $_addDaysInPartslist = false;

    /**
     * Sum of items collection qty
     *
     * @var int
     */
    protected $_itemsQty;

    /**
     * Whether product name attribute value table is joined in select
     *
     * @var boolean
     */
    protected $_isProductNameJoined = false;

    /**
     * Initialize resource model for collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('schrackwishlist/partslist_item');
        $this->addFilterToMap('store_id', 'main_table.store_id');
    }

    /**
     * After load processing
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        /**
         * Assign products
         */
        $this->_assignOptions();
        $this->_assignProducts();
        $this->resetItemsDataChanged();

        $this->getPageSize();

        return $this;
    }

    /**
     * Add options to items
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected function _assignOptions()
    {
        $itemIds = array_keys($this->_items);
        $optionCollection = Mage::getModel('schrackwishlist/partslist_item_option')->getCollection()
            ->addItemFilter($itemIds);
        foreach ($this as $item) {
            $item->setOptions($optionCollection->getOptionsByItem($item));
        }
        $productIds = $optionCollection->getProductIds();
        $this->_productIds = array_merge($this->_productIds, $productIds);

        return $this;
    }

    /**
     * Add products to items and item options
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected function _assignProducts()
    {
        Varien_Profiler::start('WISHLIST:'.__METHOD__);
        $productIds = array();
        foreach ($this as $item) {
            $productIds[$item->getProductId()] = 1;
        }
        $this->_productIds = array_merge($this->_productIds, array_keys($productIds));
        $attributes = Mage::getSingleton('wishlist/config')->getProductAttributes();
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addIdFilter($this->_productIds)
            ->addAttributeToSelect($attributes)
            ->addOptionsToResult()
            ->addPriceData()
            ->addUrlRewrite();

        if ($this->_productVisible) {
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($productCollection);
        }
        if ($this->_productSalable) {
            $productCollection = Mage::helper('adminhtml/sales')->applySalableProductTypesFilter($productCollection);
        }

        foreach ($this->_storeIds as $id) {
            $productCollection->addStoreFilter($id);
        }

        Mage::dispatchEvent('wishlist_item_collection_products_after_load', array(
            'product_collection' => $productCollection
        ));

        foreach ($this as $item) {
            $product = $productCollection->getItemById($item->getProductId());
            if ($product) {
                if (!$this->_productInStock &&
                    !$product->isSalable() &&
                    !Mage::helper('cataloginventory')->isShowOutOfStock()) {
                        $this->removeItemByKey($item->getId());
                } else {
                    $product->setCustomOptions(array());
                    $item->setProduct($product);
                    $item->setProductName($product->getName());
                    $item->setName($product->getName());
                    $item->setPrice($product->getPrice());
                }
            } else {
                $item->isDeleted(true);
            }
        }

        Varien_Profiler::stop('WISHLIST:'.__METHOD__);

        return $this;
    }

    /**
     * Add filter by wishlist object
     *
     * @param Mage_Wishlist_Model_Wishlist $partslist
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addPartslistFilter(Schracklive_SchrackWishlist_Model_Partslist $partslist)
    {
        $this->addFieldToFilter('partslist_id', $partslist->getId());
        return $this;
    }

    /**
     * Add filter by shared stores
     *
     * @param int|array $store
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addStoreFilter($store = null)
    {
        if (is_null($store)) {
            $store = Mage::app()->getStore()->getId();
        }

        if (!is_array($store)) {
            $store = array($store);
        }
        $this->_storeIds = $store;

        $this->addFieldToFilter('store_id', $store);
        return $this;
    }

    /**
     * Add items store data to collection
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addStoreData()
    {
        $storeTable = Mage::getSingleton('core/resource')->getTableName('core/store');
        $this->getSelect()->join(array('store'=>$storeTable), 'main_table.store_id=store.store_id', array(
            'store_name'=>'name',
            'item_store_id' => 'store_id'
        ));
        return $this;
    }

    /**
     * Add wishlist sort order
     *
     * @param string $attribute
     * @param string $dir
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addWishListSortOrder($attribute = 'added_at', $dir = 'desc')
    {
        $this->setOrder($attribute, $dir);
        return $this;
    }

    /**
     * Reset sort order
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function resetSortOrder()
    {
        $this->getSelect()->reset(Zend_Db_Select::ORDER);
        return $this;
    }

    /**
     * Set product Visibility Filter to product collection flag
     *
     * @param bool $flag
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function setVisibilityFilter($flag = true)
    {
        $this->_productVisible = (bool)$flag;
        return $this;
    }

    /**
     * Set Salable Filter.
     * This filter apply Salable Product Types Filter to product collection.
     *
     * @param bool $flag
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function setSalableFilter($flag = true)
    {
        $this->_productSalable = (bool)$flag;
        return $this;
    }

    /**
     * Set In Stock Filter.
     * This filter remove items with no salable product.
     *
     * @param bool $flag
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function setInStockFilter($flag = true)
    {
        $this->_productInStock = (bool)$flag;
        return $this;
    }

    /**
     * Set add days in whishlist
     *
     * This method appears in 1.5.0.0 in deprecated state, because:
     * - we need it to make wishlist item collection interface as much as possible compatible with old
     *   wishlist product collection
     * - this method is useless because we can calculate days in php, and don't use MySQL for it
     *
     * @deprecated after 1.4.2.0
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addDaysInWishlist()
    {
        $this->_addDaysInWishlist = true;
        $this->getSelect()->columns(array('days_in_wishlist' =>
            "(TO_DAYS('" . (substr(Mage::getSingleton('core/date')->date(), 0, -2) . '00') . "') ".
            "- TO_DAYS(DATE_ADD(added_at, INTERVAL ".(int) Mage::getSingleton('core/date')->getGmtOffset()." SECOND)))"
        ));
        return $this;
    }

    /**
     * Adds filter on days in wishlist
     *
     * $constraints may contain 'from' and 'to' indexes with number of days to look for items
     *
     * @param array $constraints
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addDaysFilter($constraints)
    {
        if (!is_array($constraints)) {
            return $this;
        }

        $filter = array();

        $now = Mage::getSingleton('core/date')->date();
        $gmtOffset = (int) Mage::getSingleton('core/date')->getGmtOffset();
        if (isset($constraints['from'])) {
            $lastDay = new Zend_Date($now, Varien_Date::DATETIME_INTERNAL_FORMAT);
            $lastDay->subSecond($gmtOffset)
                ->subDay($constraints['from'] - 1);
            $filter['to'] = $lastDay;
        }

        if (isset($constraints['to'])) {
            $firstDay = new Zend_Date($now, Varien_Date::DATETIME_INTERNAL_FORMAT);
            $firstDay->subSecond($gmtOffset)
                ->subDay($constraints['to']);
            $filter['from'] = $firstDay;
        }

        if ($filter) {
            $filter['datetime'] = true;
            $this->addFieldToFilter('added_at', $filter);
        }

        return $this;
    }

    /**
     * Joins product name attribute value to use it in WHERE and ORDER clauses
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected function _joinProductNameTable()
    {
        if (!$this->_isProductNameJoined) {
            $entityTypeId = Mage::getResourceModel('catalog/config')
                    ->getEntityTypeId();
            $attribute = Mage::getModel('catalog/entity_attribute')
                ->loadByCode($entityTypeId, 'name');

            $storeId = Mage::app()->getStore()->getId();

            $this->getSelect()
                ->join(
                    array('product_name_table' => $attribute->getBackendTable()),
                    'product_name_table.entity_id=main_table.product_id' .
                        ' AND product_name_table.store_id=' . $storeId .
                        ' AND product_name_table.attribute_id=' . $attribute->getId().
                        ' AND product_name_table.entity_type_id=' . $entityTypeId,
                    array()
                );

            $this->_isProductNameJoined = true;
        }
        return $this;
    }

    /**
     * Adds filter on product name
     *
     * @param string $productName
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function addProductNameFilter($productName)
    {
        $this->_joinProductNameTable();
        $this->getSelect()
            ->where('INSTR(product_name_table.value, ?)', $productName);

        return $this;
    }

    /**
     * Sets ordering by product name
     *
     * @param string $dir
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function setOrderByProductName($dir)
    {
        $this->_joinProductNameTable();
        $this->getSelect()->order('product_name_table.value ' . $dir);
        return $this;
    }

    /**
     * Get sum of items collection qty
     *
     * @return int
     */
    public function getItemsQty(){
        if (is_null($this->_itemsQty)) {
            $this->_itemsQty = 0;
            foreach ($this as $partslistItem) {
                $qty = $partslistItem->getQty();
                $this->_itemsQty += ($qty === 0) ? 1 : $qty;
            }
        }

        return (int)$this->_itemsQty;
    }
}

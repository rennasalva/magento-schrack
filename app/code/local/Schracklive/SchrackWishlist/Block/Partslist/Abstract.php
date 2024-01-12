<?php


/**
 * Wishlist Product Items abstract Block
 */
abstract class Schracklive_SchrackWishlist_Block_Partslist_Abstract extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Wishlist Product Items Collection
     *
     * @var Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected $_collection;

    /**
     * Wishlist Model
     *
     * @var Schracklive_SckrackWishlist_Model_Partslist
     */
    protected $_partslist;

    /**
     * List of block settings to render prices for different product types
     *
     * @var array
     */
    protected $_itemPriceBlockTypes = array();

    /**
     * List of block instances to render prices for different product types
     *
     * @var array
     */
    protected $_cachedItemPriceBlocks = array();

    /**
     * Internal constructor, that is called from real constructor
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addItemPriceBlockType('default', 'wishlist/render_item_price', 'wishlist/render/item/price.phtml');
    }

    /**
     * Retrieve Wishlist Data Helper
     *
     * @return Schracklive_SchrackWishlist_Helper_Partslist
     */
    protected function _getHelper()
    {
        return Mage::helper('schrackwishlist/partslist');
    }

    /**
     * Retrieve Customer Session instance
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Retrieve Wishlist model
     *
     * @return Schracklive_SchrackWishlist_Model_Partslist
     */
    protected function _getPartslist()
    {
       $this->_partslist = Mage::getModel('schrackwishlist/partslist');
       if (strlen( $this->getRequest()->getParam('id')))
           $this->_partslist->loadByCustomerAndId($this->_getCustomerSession()->getCustomer(), $this->getRequest()->getParam('id'));
       else
           $this->_partslist = Mage::helper('schrackwishlist/partslist')->getActiveOrFirstPartslist();
        return $this->_partslist;
    }

    /**
     * Prepare additional conditions to collection
     *
     * @param Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection $collection
     * @return Mage_Wishlist_Block_Customer_Wishlist
     */
    protected function _prepareCollection($collection)
    {
        return $this;
    }

    /**
     * Retrieve Wishlist Product Items collection
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function getPartslistItems($partslist)
    {
        if (is_null($this->_collection)) {
            $this->_collection = $partslist
                ->getItemCollection()
                ->addStoreFilter();

            $this->_prepareCollection($this->_collection);
        }

        return $this->_collection;
    }

    /**
     * Back compatibility retrieve partslist product items
     *
     * @deprecated after 1.4.2.0
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function getPartslist()
    {
        return $this->_getPartslist();
    }

    /**
     * Retrieve URL for Removing item from partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return string
     */
    public function getItemRemoveUrl($partslist, $product)
    {
        return $this->_getHelper()->getItemRemoveUrl($partslist, $product);
    }

    /**
     * Retrieve Add Item to shopping cart URL
     *
     * @param string|Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return string
     */
    public function getItemAddToCartUrl($item)
    {
        return $this->_getHelper()->getAddToCartUrl($item);
    }
    
    public function getAddAllToCartUrl() {
        return $this->_getHelper()->getAddAllToCartUrl();
    }

    public function getAddSelectedPartlistsToCartUrl() {
        return $this->_getHelper()->getAddSelectedPartlistsToCartUrl();
    }

    /**
     * Retrieve Add Item to shopping cart URL from shared partslist
     *
     * @param string|Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return string
     */
    public function getSharedItemAddToCartUrl($item)
    {
        return $this->_getHelper()->getSharedAddToCartUrl($item);
    }

    /**
     * Retrieve URL for adding Product to partslist
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getAddToWishlistUrl($product)
    {
        return $this->_getHelper()->getAddUrl($product);
    }

     /**
     * Returns item configure url in partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $product
     *
     * @return string
     */
    public function getItemConfigureUrl($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $id = $product->getWishlistItemId();
        } else {
            $id = $product->getId();
        }
        $params = array('id' => $id);

        return $this->getUrl('wishlist/partslist/configure/', $params);
    }


    /**
     * Retrieve Escaped Description for Wishlist Item
     *
     * @param Mage_Catalog_Model_Product $item
     * @return string
     */
    public function getEscapedDescription($item)
    {
        if ($item->getDescription()) {
            return $this->escapeHtml($item->getDescription());
        }
        return '&nbsp;';
    }

    /**
     * Check Wishlist item has description
     *
     * @param Mage_Catalog_Model_Product $item
     * @return bool
     */
    public function hasDescription($item)
    {
        return trim($item->getDescription()) != '';
    }

    /**
     * Retrieve formated Date
     *
     * @param string $date
     * @return string
     */
    public function getFormatedDate($date)
    {
        return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
    }

    /**
     * Check is the partslist has a salable product(s)
     *
     * @return bool
     */
    public function isSaleable($partslist)
    {
        if (!$partslist)
            return false;
        foreach ($this->getPartslistItems($partslist) as $item) {
            if ($item->getProduct()->isSaleable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve partslist loaded items count
     *
     * @return int
     */
    public function getPartslistItemsCount($partslist)
    {
        return $this->getPartslistItems($partslist)->count();
    }

    /**
     * Retrieve Qty from item
     *
     * @param Mage_Wishlist_Model_Item|Mage_Catalog_Model_Product $item
     * @return float
     */
    public function getQty($item)
    {
        $qty = $item->getQty() * 1;
        if (!$qty) {
            $qty = 1;
        }
        return $qty;
    }

    /**
     * Check is the partslist has items
     *
     * @return bool
     */
    public function hasPartslistItems($partslist)
    {
        return $this->getPartslistItemsCount($partslist) > 0;
    }

    /**
     * Adds special block to render price for item with specific product type
     *
     * @param string $type
     * @param string $block
     * @param string $template
     */
    public function addItemPriceBlockType($type, $block = '', $template = '')
    {
        if ($type) {
            $this->_itemPriceBlockTypes[$type] = array(
                'block' => $block,
                'template' => $template
            );
        }
    }

    /**
     * Returns block to render item with some product type
     *
     * @param string $productType
     * @return Mage_Core_Block_Template
     */
    protected function _getItemPriceBlock($productType)
    {
        if (!isset($this->_itemPriceBlockTypes[$productType])) {
            $productType = 'default';
        }

        if (!isset($this->_cachedItemPriceBlocks[$productType])) {
            $blockType = $this->_itemPriceBlockTypes[$productType]['block'];
            $template = $this->_itemPriceBlockTypes[$productType]['template'];
            $block = $this->getLayout()->createBlock($blockType)
                ->setTemplate($template);
            $this->_cachedItemPriceBlocks[$productType] = $block;
        }
        return $this->_cachedItemPriceBlocks[$productType];
    }

    /**
     * Returns product price block html
     * Overwrites parent price html return to be ready to show configured, partially configured and
     * non-configured products
     *
     * @param Mage_Catalog_Model_Product $product
     * @param boolean $displayMinimalPrice
     * @param string $idSuffix
     */
    public function getPriceHtml($product, $displayMinimalPrice = false, $idSuffix = '')
    {
        $type_id = $product->getTypeId();
        /* Start Nagrro Added: */
        if (Mage::helper('catalog')->canApplyMsrp($product)) {
            $realPriceHtml = $this->_preparePriceRenderer($type_id)
                ->setProduct($product)
                ->setDisplayMinimalPrice($displayMinimalPrice)
                ->setIdSuffix($idSuffix)
                ->toHtml();
            $product->setAddToCartUrl($this->getAddToCartUrl($product));
            $product->setRealPriceHtml($realPriceHtml);
            $type_id = $this->_mapRenderer;
        }
        /* End Nagrro Added: */
        return $this->_getItemPriceBlock($type_id)
            ->setCleanRenderer($this->_preparePriceRenderer($type_id))
            ->setProduct($product)
            ->setDisplayMinimalPrice($displayMinimalPrice)
            ->setIdSuffix($idSuffix)
            ->toHtml();
    }

    /**
     * Retrieve URL to item Product
     *
     * @param  Mage_Wishlist_Model_Item $item
     * @param  array $additional
     * @return string
     */
    public function getProductUrl($item, $additional = array())
    {
        $buyRequest = $item->getBuyRequest();
        $product    = $item->getProduct();
        if (is_object($buyRequest)) {
            $config = $buyRequest->getSuperProductConfig();
            if ($config && isset($config['product_id'])) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getStoreId())
                    ->load($config['product_id']);
            }
        }
        return parent::getProductUrl($product, $additional);
    }
}

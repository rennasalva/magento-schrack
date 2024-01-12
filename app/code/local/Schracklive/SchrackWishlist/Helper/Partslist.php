<?php

/**
 * Partslist helper
 *
 * @author c.friedl
 */
class SchrackLive_SchrackWishlist_Helper_Partslist extends Mage_Core_Helper_Abstract {
    
        /**
     * Config key 'Display Wishlist Summary'
     */
    const XML_PATH_WISHLIST_LINK_USE_QTY = 'wishlist/wishlist_link/use_qty';

    /**
     * Config key 'Display Out of Stock Products'
     */
    const XML_PATH_CATALOGINVENTORY_SHOW_OUT_OF_STOCK = 'cataloginventory/options/show_out_of_stock';

    /**
     * Customer Partslist instance
     *
     * @var Schracklive_SchrackWishlist_Model_Partslist
     */
    protected $_partslist = null;

    /**
     * Wishlist Product Items Collection
     *
     * @var Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Product_Collection
     */
    protected $_productCollection = null;

    /**
     * Wishlist Items Collection
     *
     * @var Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    protected $_partslistItemCollection = null;

    /**
     * Retreive customer session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Retrieve customer login status
     *
     * @return bool
     */
    protected function _isCustomerLogIn()
    {
        return $this->_getCustomerSession()->isLoggedIn();
    }

    /**
     * Retrieve logged in customer
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function _getCurrentCustomer()
    {
        return $this->_getCustomerSession()->getCustomer();
    }


    protected function _getUrl($route, $params = array())
    {
        return Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol($route, $params);
    }

    public function getPartslists() {
        return Mage::getModel('schrackwishlist/partslist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
    }
    
    public function getPartslistCount() {
        return Mage::getModel('schrackwishlist/partslist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer())->count();
    }
    
    /**
     * load partslist for the logged-in customer by id from request
     * if user is not logged in, we will return null
     * @return Schracklive_SchrackWishlist_Model_Partslist/null
     */
    public function getPartslist() {
        if (strlen(Mage::app()->getRequest()->getParam('id')))
            $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(),
                 Mage::app()->getRequest()->getParam('id'));
        else
            $partslist = $this->getActiveOrFirstPartslist ();
        return $partslist;
    }
    
    /**
     * load the active partslist for the logged-in customer
     * @return Schracklive_SchrackWishlist_Model_Partslist
     */
     public function getActivePartslist() {
        if (!$this->_isCustomerLogIn())
            return null;
        try {
            $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer(Mage::getSingleton('customer/session')->getCustomer());
        } catch (Exception $e) {
            $partslist = null;
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
        }
        return $partslist;
    }
    
    public function getActiveOrFirstPartslist() {
        if ($this->hasActivePartslist())
            return $this->getActivePartslist();
        else {
            $lists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
            if ($lists->count() > 0)
                return $lists->getFirstItem();
            else
                return null;
        }
    }
    
    public function hasActivePartslist() {
        return Mage::getModel('schrackwishlist/partslist')->hasCustomerActiveList(Mage::getSingleton('customer/session')->getCustomer());
    }
    
    
     /**
     * Retrieve partslist items availability
     *
     * @return bool
     */
    public function hasItems()
    {
        return $this->getItemCount() > 0;
    }

    /**
     * Retrieve partslist item count (inchlude config settings)
     *
     * @return int
     */
    public function getItemCount()
    {
        $storedDisplayType = $this->_getCustomerSession()->getPartslistDisplayType();
        $currentDisplayType = Mage::getStoreConfig(self::XML_PATH_WISHLIST_LINK_USE_QTY);

        $storedDisplayOutOfStockProducts = $this->_getCustomerSession()->getDisplayOutOfStockProducts();
        $currentDisplayOutOfStockProducts = Mage::getStoreConfig(self::XML_PATH_CATALOGINVENTORY_SHOW_OUT_OF_STOCK);
        if (!$this->_getCustomerSession()->hasPartslistItemCount()
                || ($currentDisplayType != $storedDisplayType)
                || $this->_getCustomerSession()->hasDisplayOutOfStockProducts()
                || ($currentDisplayOutOfStockProducts != $storedDisplayOutOfStockProducts)) {
            $this->calculate();
        }

        return $this->_getCustomerSession()->getPartslistItemCount();
    }

    /**
     * Retrieve partslist product items collection
     *
     * alias for getProductCollection
     *
     * @deprecated after 1.4.2.0
     * @see Schracklive_SchrackWishlist_Model_Partslist::getItemCollection()
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Product_Collection
     */
    public function getItemCollection()
    {
        return $this->getProductCollection();
    }


    /**
     * Retrieve parstlist items collection
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function getPartslistItemCollection($partslist = null)
    {
        if (is_null($partslist))
            $partslist = Mage::getModel ('schrackwishlist/partslist')->loadActiveListByCustomer(Mage::getSingleton('customer/session')->getCustomer());        
        if (is_null($this->_partslistItemCollection)) {
            $this->_partslistItemCollection = $partslist->getItemCollection();
        }
        return $this->_partslistItemCollection;
    }


    /**
     * Retrieve partslist product items collection
     *
     * @deprecated after 1.4.2.0
     * @see Schracklive_SchrackWishlist_Model_Partslist::getItemCollection()
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Product_Collection
     */
    public function getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $this->_productCollection = $this->getPartslist()
                ->getProductCollection();

            Mage::getSingleton('catalog/product_visibility')
                ->addVisibleInSiteFilterToCollection($this->_productCollection);
        }
        return $this->_productCollection;
    }
    
    /**
     * Retrieve URL for removing item from partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return string
     */
    public function getItemRemoveUrl($partslist, $item)
    {
        return $this->_getUrl('wishlist/partslist/remove', array(
            'id' => $partslist->getId(),
            'item' => $item->getPartslistItemId()
        ));
    }

    /**
     * Retrieve URL for removing item from partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return string
     */
    public function getConfigureUrl($item)
    {
        return $this->_getUrl('wishlist/partslist/configure', array(
            'id' => $this->getPartslist()->getId(),
            'item' => $item->getPartslistItemId()
        ));
    }

    /**
     * Retrieve url for adding product to partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $product
     * @return  string|boolean
     */
    public function getAddUrl($item)
    {
        return $this->getAddUrlWithParams($item);
    }
    
    /**
     * retrieve url for adding, but without any item yet
     */
    public function getAddBaseUrl() {
        return $this->_getUrl('wishlist/partslist/add');
    }
    
    public function getBaseUrl() {
        return $this->_getUrl('wishlist/partslist');
    }

    /**
     * retrieve url for adding, but without any item yet
     */
    public function getAddToNewListBaseUrl() {
        return $this->_getUrl('wishlist/partslist/add');
    }
    
    /**
     * retrieve url for adding, but without any item yet
     */
    public function getRemoveBaseUrl() {
        return $this->_getUrl('wishlist/partslist/remove');
    }
    
    /**
     * Retrieve base url for adding product to partslist (so that js can 
     * create its url on the fly)
     *
     * @param Schracklive_SchrackWishlist_Partslist $partslist
     * @return  string|boolean
     */
    public function getAddBaseUrlWithPartslist($partslist)
    {
        return $this->_getUrl('wishlist/partslist/add', array('id' => $partslist->getId()));
    }
    
    public function getAddBaseUrlWithNewPartslist()
    {
        return $this->_getUrl('wishlist/partslist/add');
    }
    
    /**
     * Retrieve base url for adding product to partslist (so that js can 
     * create its url on the fly)
     *
     * @param Schracklive_SchrackWishlist_Partslist $partslist
     * @return  string|boolean
     */
    public function getAddBaseUrlWithActivePartslist()
    {
        return $this->getAddBaseUrlWithPartslist($this->getActivePartslist());
    }
    
    /**
     * Retrieve base url for removeing product to partslist (so that js can 
     * create its url on the fly)
     *
     * @param Schracklive_SchrackWishlist_Partslist $partslist
     * @return  string|boolean
     */
    public function getRemoveBaseUrlWithPartslist($partslist)
    {
        return $this->_getUrl('wishlist/partslist/remove', array('id' => $partslist->getId()));
    }
    
    /**
     * Retrieve base url for removeing product to partslist (so that js can 
     * create its url on the fly)
     *
     * @param Schracklive_SchrackWishlist_Partslist $partslist
     * @return  string|boolean
     */
    public function getRemoveBaseUrlWithActivePartslist()
    {
        return $this->getRemoveBaseUrlWithPartslist($this->getActivePartslist());
    }
    
    /**
     * Retrieve url for updating product in partslist
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $product
     * @return  string|boolean
     */
    public function getUpdateUrl($item)
    {
        $itemId = null;
        if ($item instanceof Mage_Catalog_Model_Product) {
            $itemId = $item->getPartslistItemId();
        }
        if ($item instanceof Mage_Wishlist_Model_Item) {
            $itemId = $item->getId();
        }

        if ($itemId) {
            $params['id'] = $itemId;
            return $this->_getUrlStore($item)->getUrl('wishlist/partslist/updateItemOptions', $params);
        }

        return false;
    }   

    /**
     * Retrieve URL for adding item to shoping cart
     *
     * @param string|Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return  string
     */
    public function geAddToCartUrl($item)
    {
        $continueUrl  = Mage::helper('core')->urlEncode(Mage::getUrl('*/*/*', array(
            '_current'      => true,
            '_use_rewrite'  => true,
            '_store_to_url' => true,
        )));

        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $params = array(
            'id' => $this->getPartslist()->getId(),
            'item' => is_string($item) ? $item : $item->getPartslistItemId(),
            $urlParamName => $continueUrl
        );
        return $this->_getUrlStore($item)->getUrl('wishlist/partslist/cart', $params);
    }
    
    public function getAddAllToCartUrl() {
        $continueUrl  = Mage::helper('core')->urlEncode(Mage::getUrl('*/*/*', array(
            '_current'      => true,
            '_use_rewrite'  => true,
            '_store_to_url' => true,
        )));

        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $params = array(
            'id' => $this->getPartslist()->getId(),            
            $urlParamName => $continueUrl
        );
        return $this->_getUrl('wishlist/partslist/allcart', $params);
    }

    public function getAddSelectedPartlistsToCartUrl () {
        $continueUrl  = Mage::helper('core')->urlEncode(Mage::getUrl('*/*/*', array(
            '_current'      => true,
            '_use_rewrite'  => true,
            '_store_to_url' => true,
        )));

        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $params = array(
            $urlParamName => $continueUrl
        );
        return $this->_getUrl('wishlist/partslist/selectedplcart', $params);
    }
    
    /**
     * Retrieve URL for adding item to shoping cart from shared partslist
     *
     * @param string|Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return  string
     */
    public function getSharedAddToCartUrl($item)
    {
        $continueUrl  = Mage::helper('core')->urlEncode(Mage::getUrl('*/*/*', array(
            '_current'      => true,
            '_use_rewrite'  => true,
            '_store_to_url' => true,
        )));

        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $params = array(
            'id' => $this->getPartslist()->getId(),
            'item' => is_string($item) ? $item : $item->getPartslistItemId(),
            $urlParamName => $continueUrl
        );
        return $this->_getUrlStore($item)->getUrl('wishlist/partslist/shared/cart', $params);
    }

    /**
     * Retrieve url for adding item to shoping cart with b64 referer
     *
     * @deprecated
     * @param   Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return  string
     */
    public function getAddToCartUrlBase64($item)
    {
        return $this->getAddToCartUrl($item);
    }

    /**
     * Retrieve customer partslist url
     *
     * @return string
     */
    public function getListUrl()
    {
        return $this->_getUrl('partslist', array(
            'id' => $this->getPartslist()->getId()
        ));
    }

    /**
     * Check is allow partslist module
     *
     * @return bool
     */
    public function isAllow()
    {
        if ($this->isModuleOutputEnabled() && Mage::getStoreConfig('wishlist/partslist/general/active')) {
            return true;
        }
        return false;
    }

    /**
     * Check is allow partslist action in shopping cart
     *
     * @return bool
     */
    public function isAllowInCart()
    {
        return $this->isAllow() && $this->_isCustomerLogIn();
    }

    /**
     * Retrieve customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        return $this->_getCurrentCustomer()->getName();
    }
   
    /**
     * Is allow RSS
     *
     * @return bool
     */
    public function isRssAllow()
    {
        if (Mage::getStoreConfig('rss/wishlist/partslist/active')) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve default empty comment message
     *
     * @return string
     */
    public function defaultCommentString()
    {
        return $this->__('Please, enter your comments...');
    }

   
    
    
    public function getAddUrlWithPartslist($partslist, $item) {
        return $this->getAddUrlWithParams($item, array('id' => $partslist->getId()));
    }
    
    public function getAddUrlWithNewPartslist($item) {
        return $this->getAddUrlWithParams($item);
    }
    
    public function getRemoveUrlWithPartslist($partslist, $item) {
        return $this->getRemoveUrlWithParams($item, array('id' => $partslist->getId()));
    }

    /**
     * Retrieve RSS URL
     *
     * @return string
     */
    public function getRssUrl()
    {
        $customer = $this->_getCurrentCustomer();
        $key = $customer->getId().','.$customer->getEmail();
        return $this->_getUrl(
            'rss/index/wishlist/partslist',
            array(
                'data' => Mage::helper('core')->urlEncode($key),
                '_secure' => false
            )
        );
    }
    
    /**
     * Retrieve url for adding product to partslist with params
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $product
     * @param array $param
     * @return  string|boolean
     */
    public function getAddUrlWithParams($item, array $params = array())
    {
        $productId = null;
        if ($item instanceof Mage_Catalog_Model_Product) {
            $productId = $item->getEntityId();
        }
        if ($item instanceof Mage_Wishlist_Model_Item) {
            $productId = $item->getProductId();
        }

        if ($productId) {
            $params['product'] = $productId;
            return $this->_getUrlStore($item)->getUrl('wishlist/partslist/add', $params);
        }

        return false;
    }
    /**
     * Retrieve url for adding product to partslist with params
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $product
     * @param array $param
     * @return  string|boolean
     */
    
    public function getRemoveUrlWithParams($item, array $params = array())
    {
        $productId = null;
        if ($item instanceof Mage_Catalog_Model_Product) {
            $productId = $item->getEntityId();
        }
        if ($item instanceof Mage_Wishlist_Model_Item) {
            $productId = $item->getProductId();
        }

        if ($productId) {
            $params['product'] = $productId;
            return $this->_getUrlStore($item)->getUrl('wishlist/partslist/remove', $params);
        }

        return false;
    }
    
    /**
     * Retrieve Item Store for URL
     *
     * @param Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @return Mage_Core_Model_Store
     */
    protected function _getUrlStore($item)
    {
        $storeId = null;
        $product = null;
        if ($item instanceof Mage_Wishlist_Model_Item) {
            $product = $item->getProduct();
        } elseif ($item instanceof Mage_Catalog_Model_Product) {
            $product = $item;
        }
        if ($product) {
            if ($product->isVisibleInSiteVisibility()) {
                $storeId = $product->getStoreId();
            }
            else if ($product->hasUrlDataObject()) {
                $storeId = $product->getUrlDataObject()->getStoreId();
            }
        }
        return Mage::app()->getStore($storeId);
    }
    
    /**
     * Calculate count of partslist items and put value to customer session.
     * Method called after partslist modifications and trigger 'wishlist_items_renewed' event.
     * Depends from configuration.
     *
     * @return Mage_Wishlist_Helper_Data
     */
    public function calculate($partslist = null)
    {
        $session = $this->_getCustomerSession();
        if (!$this->_isCustomerLogIn()) {
            $count = 0;
        } else {
            if (Mage::getStoreConfig(self::XML_PATH_WISHLIST_LINK_USE_QTY)) {
                $count = $this->getPartslistItemCollection($partslist)
                    ->setInStockFilter(true)
                    ->getItemsQty();
            } else {
                $count = count($this->getPartslistItemCollection($partslist)->setInStockFilter(true));
            }
            $session->setWishlistDisplayType(Mage::getStoreConfig(self::XML_PATH_WISHLIST_LINK_USE_QTY));
            $session->setDisplayOutOfStockProducts(
                Mage::getStoreConfig(self::XML_PATH_CATALOGINVENTORY_SHOW_OUT_OF_STOCK)
            );
        }
        $session->setPartslistItemCount($count);
        Mage::dispatchEvent('wishlist_items_renewed');
        return $this;
    }  
  
    
    public function isProductOnList($partslist, $product) {
        try {
            $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(),
                 $partslist->getId());
            return $partslist->getIsProductOnList($product);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            return false;
        }        
    }
    
    public function addProduct($partslist, $product, &$jsonResponse, $forceToSession = false) {
        try {
            $partslistCnt = $partslist->getItemsCount();
            $maxAmount = Mage::helper('schrack')->getMaximumOrderAmount();
            if ( $partslistCnt >= $maxAmount ) {
                $msg = $this->__('Product cannot be added to parts list because maximum item count %d has already been reached.',$maxAmount);
                $this->addError($msg,$jsonResponse,$forceToSession);
                $jsonResponse['listId'] = $partslist->getId();
                return;
            }

            $buyRequest = new Varien_Object(Mage::app()->getRequest()->getParams());

            $result = $partslist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                Mage::throwException($result);
            }
            $partslist->activate();
            $partslist->save();

            Mage::dispatchEvent(
                'partslist_add_product',
                array(
                    'partslist' => $partslist,
                    'product'   => $product,
                    'item'      => $result
                )
            );


            $this->calculate($partslist);

            $message = $this->__('%1$s has been added to your partslist.', $product->getName());
            $this->addSuccess($message, $jsonResponse, $forceToSession);
        }
        catch (Mage_Core_Exception $e) {
            $this->addError($this->__('An error occurred while adding item to partslist: %s', $e->getMessage()), $josnResponse, $forceToSession);
        }
        catch (Exception $e) {
            $this->addError($this->__('An error occurred while adding item to partslist.'), $jsonResponse, $forceToSession);
        }
        $jsonResponse['listId'] = $partslist->getId();
    }
  
    public function addPartlistItemsToCart ( $items, &$successMessage, &$errorMessages, &$addedItems, &$notSalableItems, &$hasOptionsItems ) {
        $successMessage = null;
        if ( ! is_array($errorMessages)   ) $errorMessages      = array();
        if ( ! is_array($addedItems)      ) $addedItems         = array();
        if ( ! is_array($notSalableItems) ) $notSalableItems    = array();
        if ( ! is_array($hasOptionsItems) ) $hasOptionsItems    = array();
        $cart = Mage::getSingleton('checkout/cart');
        
        foreach ($items as $item) {
            /** @var $item Schracklive_SchrackWishlist_Model_Partslist_Item */
            try {
                $item->unsProduct(); // ### DL: ???

                // Before adding to card, make sure, that we have seleceted the correct quantity:
                $product = $item->getProduct();
                $qty = intval($item->getQty());
                $resultQuantityData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity5');
                if ($resultQuantityData && array_key_exists('invalidQuantity', $resultQuantityData) && $resultQuantityData['invalidQuantity'] == true) {
                    if (array_key_exists('closestHigherQuantity', $resultQuantityData) && $resultQuantityData['closestHigherQuantity'] != $qty) {
                    $minimumQuantity = $product->calculateMinimumQuantityPackage();

                        $notValidAmountMessage = $this->__("AMOUNT: The entered amount for the article %s1 is not a multiple of the sales unit. Please enter a multiple of %s2.");
                        $errorMessages[] = str_replace('%s2', $minimumQuantity, str_replace('%s1', $product->getName(), $notValidAmountMessage));
                        $errorMessages[] = $this->__('Unable to add the following product(s) to shopping cart: %s.', $product->getName());
                    }
                } else {
                    // Add to cart
                    if ($item->addToCart($cart, false)) {
                        $addedItems[] = $item;
                    }
                }
            } catch (Mage_Core_Exception $e) {
                if ($e->getCode() == Schracklive_SchrackWishlist_Model_Partslist_Item::EXCEPTION_CODE_NOT_SALABLE) {
                    $notSalableItems[] = $item;
                } else if ($e->getCode() == Schracklive_SchrackWishlist_Model_Partslist_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                    $hasOptionsItems[] = $item;
                } else {
                    $errorMessages[] = trim($e->getMessage() . ' ') . $this->__('for') . ' ' . $item->getProduct()->getName() . '.';
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $errorMessages[] = $this->__('Cannot add the item to shopping cart.');
            }
        }

        if ($notSalableItems) {
            $products = array();
            foreach ($notSalableItems as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $errorMessages[] = $this->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $products));
        }

        if ($addedItems) {
            $products = array();
            foreach ($addedItems as $item) {
                $product = $item->GetProduct();
                $products[] = '"' . $product->getName() . '"';
            }

            $successMessage = $this->__('%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $products));
        }
        // save cart and collect totals
        $cart->save()->getQuote()->collectTotals();

        $this->calculate();

        return $addedItems;
    }
    
    private function addError($msg, &$jsonResponse, $forceToSession) {
        $msg = $this->__($msg);
        if (Mage::app()->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['errors']) || !is_array($jsonResponse['errors']))
                $jsonResponse['errors'] = array();
            array_push($jsonResponse['errors'], $msg);
        } else
            Mage::getSingleton('core/session')->addError($msg);        
    }
    
    private function addSuccess($msg, &$jsonResponse, $forceToSession) {
        $msg = $this->__($msg);
        if (Mage::app()->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['messages']) || !is_array($jsonResponse['messages']))
                $jsonResponse['messages'] = array();
            array_push($jsonResponse['messages'], $msg);
        } else
            Mage::getSingleton('core/session')->addSuccess($msg);
    }
    
    private function removeSuccessMessages(&$jsonResponse) {
        $jsonResponse['messages'] = array();
    }
}

?>
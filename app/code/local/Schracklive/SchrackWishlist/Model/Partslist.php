<?php

/**
 * Wishlist
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_Model_Partslist extends Mage_Core_Model_Abstract {

    const HASH_SEED='xA';
    const FIXED_DEBUG_CC = 'p.trummer@schrack.com';
    
    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schrackwishlist/partslist');
    }
    
    /**
     * Set date of last update for wishlist
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _beforeSave()
    {
        // Fetch the partslist-id and try to find out, if there is a change on a shared partslist (-> then update the changed-flag):
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $customerEmail = $sessionLoggedInCustomer->getEmail();
        $partslistId = $this->getId();

        if ($partslistId) {
            $query  = "UPDATE partslist_sharing_map SET last_update_notification_flag = 1, last_update_notification_at = '" . date('Y-m-d H:i:s') . "', updated_at = '" . date('Y-m-d H:i:s') . "'";
            $query .= " WHERE shared_partslist_id = " . $partslistId . " AND email_sharer LIKE '" . $customerEmail . "'";

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->query($query);
        }
        parent::_beforeSave();

        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        
        return $this;
    }

    protected function _afterDelete() {
        // Fetch the partslist-id and try to find out, if there is a change on a shared partslist (-> then update the changed-flag):
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $customerEmail = $sessionLoggedInCustomer->getEmail();
        $partslistId = $this->getId();

        $query  = "DELETE FROM partslist_sharing_map";
        $query .= " WHERE shared_partslist_id = " . $partslistId . " AND email_sharer LIKE '" . $customerEmail . "'";

        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $writeConnection->query($query);

        parent::_afterDelete();
    }

    private function _loadByCustomer($customerId) {
        return Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $customerId))
                ->addOrder('is_active', 'DESC')
                ->addOrder('description', 'ASC');
    }
    
    public function loadByCustomer($customer) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }
                
        $partslists = $this->_loadByCustomer($customer);            
        
        return $partslists;
    }
    
    /**
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @return Schracklive_SchrackWishlist_Model_Partslist
     * @throws Exception
     */
    public function loadActiveListByCustomer($customer, $create = false) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }
        
        $partslists = Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $customer))
                ->addFieldToFilter('is_active', array('=' => 1))
                ->setOrder('description', 'ASC');
         
        $count = $partslists->count();
        if ($count === 0 && $create) {
            $partslists = $this->_loadByCustomer($customer);
        
            if ($partslists->count() === 0) { // count again to see if there are any lists by this customer, including non-active
                $this->create($customer, null, null);
            }
        } else if ($count === 1) {
            $this->_getResource()->load($this, $partslists->getFirstItem()->getId());
        }
        return $this;
    }
    public function loadByCustomerAndHashedId($customer, $hashedId) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }

        $partslists = Mage::getModel('schrackwishlist/partslist')->getCollection()
            ->addFieldToFilter('customer_id', array('=' => $customer));

        foreach ( $partslists as $partslist ) {
            if ( $partslist->getHashedId() === $hashedId ) {
                $this->_getResource()->load($this, $partslist->getId());
                return $this;
            }
        }

        return $this;
    }

    public function getHashedId() {
        return sha1(self::HASH_SEED . $this->getId());
    }

    /**
     * 
     * @param Mage_Customer_Model_Customer $customer
     * @return bool
     * @throws Exception
     */
    public function hasCustomerActiveList($customer) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }

        
        $partslists = Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $customer))
                ->addFieldToFilter('is_active', array('=' => 1)); 
        
         return ($partslists->count() === 1);
    }
    
    /**
     * Create new partslist for customer
     *
     * @param mixed $customer
     * @param string $description description of new partslist (with a default)
     * @return Schracklive_SchrackWishlist_Model_Partslist
     */
    public function create($customer, $description = null, $comment = null, $isEndcustomer = 0)
    {        
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }
        if ($description === null)
            $description = Mage::helper('adminhtml')->__('My Partslist');
        
        if (!$this->hasCustomerActiveList($customer))
            $this->setIsActive(1);
        $this->setCustomerId($customer);
        $this->setDescription($description);
        $this->setComment($comment);
        $this->setIsEndcustomer($isEndcustomer);
        if ( $isEndcustomer ) {
            $this->setIsVisible(0);
        } else {
            $this->setIsVisible(1);
        }
        $this->save();        
        return $this;
    }
    
    public function activate() {
        $partslists =  Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $this->getCustomerId()))
                ->addFieldToFilter('is_active', array('=' => 1));
        foreach($partslists as $partslist)
            $partslist->deactivate();
        $this->setIsActive(1);
        $this->save();
    }
    
    public function deactivate() {
        $this->setIsActive(0);
        $this->save();        
    }
    /**
     * 
     * @return Schracklive_SchrackWishlist_Model_Partslist
     */
    public function save() {
        return parent::save();
    }
    
    /**
     * 
     * @param mixed $customer
     * @param int $partslistId
     * @return type
     */
     public function loadByCustomerAndId($customer, $partslistId) {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customer = $customer->getId();
        }        
        
        $count = $this->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => $customer))
                ->addFieldToFilter('partslist_id', array('eq' => $partslistId))
                ->count();
        
        if ($count === 1) {
            $this->_getResource()->load($this, $partslistId); // TODO there surely is a way to do this without data access, but I didn't find it yet...
        } else {
            throw new Exception('partslist could not be found');
        }
        return $this;
    }
    
    public function hasPartslistItems() {
        return ($this->getItemsCount() > 0);
    }
    
    public function validate() {
        if ($this->getIsActive()) {
            $partslists =  Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('eq' => $this->getCustomerId()))
                ->addFieldToFilter('partslist_id', array('neq' => $this->getId()))
                ->addFieldToFilter('is_active', array('eq' => 1));            
            if ($partslists->count() !== 0)
                throw new Exception(Mage::helper('core')->__('There can only be one active partslist.'));
        }
    }
    
    public function getDescription() {
        $d = parent::getDescription();
        if (!strlen($d))
            $d = Mage::helper('core')->__('My Partslist');
        return $d;
    }
    
    /**
     * Adding item to partslist
     *
     * @param   Schracklive_SchrackWishlist_Model_Partslist_Item $item
     * @return  Schracklive_SchrackWishlist_Model_Partslist
     */
    public function addItem(Schracklive_SchrackWishlist_Model_Partslist_Item $item)
    {
        $item->setPartslist($this);
        if (!$item->getId()) {
            $this->getItemCollection()->addItem($item);
            Mage::dispatchEvent('partslist_add_item', array('item' => $item));
        }
        return $this;
    }

    /**
     * Adds new product to partslist.
     * Returns new item or string on error.
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @param mixed $buyRequest
     * @param bool $forciblySetQty
     * @return Schracklive_SchrackWishlist_Model_Partslist_Item|string
     */
    public function addNewItem($product, $buyRequest = null, $forciblySetQty = true, $referrerUrl = null)
    {
        /*
         * Always load product, to ensure:
         * a) we have new instance and do not interfere with other products in partslist
         * b) product has full set of attributes
         */
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
            // Maybe force some store by partslist internal properties
            $storeId = $product->hasPartslistStoreId() ? $product->getPartslistStoreId() : $product->getStoreId();
        } else {
            $productId = (int) $product;
            if ($buyRequest->getStoreId()) {
                $storeId = $buyRequest->getStoreId();
            } else {
                $storeId = Mage::app()->getStore()->getId();
            }
        }

        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->load($productId);

        if ($buyRequest instanceof Varien_Object) {
            $_buyRequest = $buyRequest;
        } elseif (is_string($buyRequest)) {
            $_buyRequest = new Varien_Object(unserialize($buyRequest));
        } elseif (is_array($buyRequest)) {
            $_buyRequest = new Varien_Object($buyRequest);
        } else {
            $_buyRequest = new Varien_Object();
        }

        $cartCandidates = $product->getTypeInstance(true)
            ->processConfiguration($_buyRequest, $product);

        /**
         * Error message
         */
        if (is_string($cartCandidates)) {
            return $cartCandidates;
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $errors = array();
        $items = array();

        foreach ($cartCandidates as $candidate) {
            if ($candidate->getParentProductId()) {
                continue;
            }
            $candidate->setPartslistStoreId($storeId);
            $item = $this->_addCatalogProduct($candidate, $candidate->getQty(), $forciblySetQty, $referrerUrl);
            $items[] = $item;

            // Collect errors instead of throwing first one
            if ($item->getHasError()) {
                $errors[] = $item->getMessage();
            }
        }

        Mage::dispatchEvent('partslist_product_add_after', array('items' => $items));

        return $item;
    }
    
    /**
     * Retrieve wishlist items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        return $this->_getResource()->fetchItemsCount($this);
    }
    
    /**
     * Adding catalog product object data to wishlist
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   int $qty
     * @param   bool $forciblySetQty
     * @return  Schracklive_SchrackWishlist_Model_Partslist_Item
     */
    protected function _addCatalogProduct(Mage_Catalog_Model_Product $product, $qty = 1, $forciblySetQty = true, $referrerUrl = null)
    {
        if ($forciblySetQty && strlen($qty) === 0) {
            $qty = 1;
        }
        
        $item = null;
        foreach ($this->getItemCollection() as $_item) {
            if ($_item->representProduct($product)) {
                $item = $_item;
                break;
            }
        }

        if ($item === null) {
            $storeId = $product->hasPartslistStoreId() ? $product->getPartslistStoreId() : $this->getStore()->getId();
            $item = Mage::getModel('schrackwishlist/partslist_item');
            $item->setProductId($product->getId())
                ->setPartslistId($this->getId())
                ->setAddedAt(now())
                ->setStoreId($storeId)
                ->setOptions($product->getCustomOptions())
                ->setProduct($product)
                ->setQty($qty)
                ->setReferrerUrl($referrerUrl)
                ->save();
        } else {
            $qty = $forciblySetQty ? $qty : $item->getQty() + $qty;
            $item->setQty($qty)
                ->save();
        }

        $this->addItem($item);

        return $item;
    }
    
    
    /**
     * Retrieve wishlist item collection
     *
     * @return Schracklive_SchrackWishlist_Model_Mysql4_Partslist_Item_Collection
     */
    public function getItemCollection()
    {
        if (is_null($this->_itemCollection)) {
            $this->_itemCollection =  Mage::getResourceModel('schrackwishlist/partslist_item_collection')
                ->addPartslistFilter($this);
        }

        return $this->_itemCollection;
    }

    /**
     * Retrieve wishlist item collection
     *
     * @param int $itemId
     * @return Schracklive_SchrackWishlist_Model_Partslist_Item
     */
    public function getItem($itemId)
    {
        if (!$itemId) {
            return false;
        }
        return $this->getItemCollection()->getItemById($itemId);
    }
    
    public function getItemByProduct($product) {        
        $this->_itemCollection = null;            
        return $this->getItemCollection()
                ->addFieldToFilter('partslist_id', $this->getId())
                ->addFieldToFilter('product_id', $product->getId())
                ->getFirstItem();
    }

    /**
     * Retrieve Product collection
     *
     * @deprecated after 1.4.2.0
     * @see Mage_Wishlist_Model_Wishlist::getItemCollection()
     *
     * @return Mage_Wishlist_Model_Mysql4_Product_Collection
     */
    public function getProductCollection()
    {
        $collection = $this->getData('product_collection');
        if (is_null($collection)) {
            $collection = Mage::getResourceModel('schrackwishlist/partslist/product_collection');
            $this->setData('product_collection', $collection);
        }
        return $collection;
    }

    public function getIsProductOnList($product) {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        } else {
            $productId = (int) $product;      
        }
        $count = $this->getItemCollection()->addFieldToFilter('partslist_id', $this->getId())
                ->addFieldToFilter('product_id', $productId)
                ->count();
        return ($count === 1);
    }
    
    public function truncate()
    {
        foreach ($this->getItemCollection() as $item) {
            $item->delete();
            $item->isDeleted(true);
        }
        $this->save();
    }        
    
    public function delete() {
        if ($this->getIsActive()) {
            $partslists = Mage::getModel('schrackwishlist/partslist')->getCollection()
                ->addFieldToFilter('customer_id', array('=' => $this->getCustomerId()))
                ->addFieldToFilter('is_active', array('=' => 0))
                ->setOrder('description', 'ASC')
                ->setPageSize(1)
                ->setCurPage(0);
            if ($partslists->count() === 1) {
                foreach ($partslists as $list) {
                    $list->activate();
                    break;
                }
            } else if ($partslists->count() === 0) {
                throw new Exception('cannot delete the only remaining partslist');
            } else {
                throw new Exception('unable to set a new active partslist');
            }
        }
        parent::delete();
    }
    
    public function getCustomer() {
        $customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
        return $customer;
    }

    public function sendRequestOfferEmails($ecplCustomer, $params) {
        $this->_sendRequestOfferSchrackcustomerEmail($ecplCustomer, $params);
        $this->_sendRequestOfferEndcustomerEmail($ecplCustomer, $params);
    }

    public function setEcplValues($params) {
        $this->setIsVisible(1);
        $this->setDescription($params['name']);
        $helper = Mage::helper('core');
        $comment = <<<EOT
{$helper->__('Name ')}: {$params['name']}
{$helper->__('Email')}: {$params['email']}
{$helper->__('Address')}: {$params['address']}
{$helper->__('Phone')}: {$params['phone']}
{$helper->__('Message')}: {$params['message']}
EOT;
        $this->setComment($comment);
        $this->save();
    }


    private function _sendRequestOfferSchrackcustomerEmail($ecplCustomer, $params) {
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('wishlist/endcustomerpartslist/email_requestoffer_schrackcustomer.phtml');
        $block->assign('params', $params);
        $block->assign('partslist', $this);
        $block->assign('ecplCustomer', $ecplCustomer);
        $html = $block->toHtml();
        $mailHelper = Mage::helper('wws/mailer');
        $toAddress = $ecplCustomer->getEmail();
        if (isset($toAddress)) {
            Mage::log("_sendRequestOfferEmail sending to $toAddress", null, 'ecpl.log');
            Mage::log($html, null, 'ecpl.log');
            $args = array('subject' => Mage::helper('wishlist')->__('Kunden Online-Schauraum Anfrage'),
                'to' => $toAddress,
                'cc' => null,
                'bcc' => null,
                'body' => $html,
                'templateVars' => array()
            );
            if ( self::FIXED_DEBUG_CC ) {
                $args['cc'] = self::FIXED_DEBUG_CC;
            }
            $mailHelper->send($args);
        } else {
            throw new Exception('no receiver for checkout request email given');
        }
    }
    private function _sendRequestOfferEndcustomerEmail($ecplCustomer, $params) {
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('wishlist/endcustomerpartslist/email_requestoffer_endcustomer.phtml');
        $block->assign('params', $params);
        $block->assign('partslist', $this);
        $block->assign('ecplCustomer', $ecplCustomer);
        $html = $block->toHtml();
        $mailHelper = Mage::helper('wws/mailer');
        $toAddress = $params['email'];
        if (isset($toAddress)) {
            Mage::log("_sendRequestOfferEndcustomerEmail sending to $toAddress", null, 'ecpl.log');
            Mage::log($html, null, 'ecpl.log');
            $args = array('subject' => Mage::helper('wishlist')->__('Your Request In The Online Showroom'),
                'to' => $toAddress,
                'cc' => null,
                'bcc' => null,
                'body' => $html,
                'templateVars' => array()
            );
            if ( self::FIXED_DEBUG_CC ) {
                $args['cc'] = self::FIXED_DEBUG_CC;
            }
            $mailHelper->send($args);
        } else {
            throw new Exception('no receiver for checkout request email given');
        }
    }
    public function sendShareEmail($ecplCustomer, $params) {
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('wishlist/endcustomerpartslist/email_share.phtml');
        $block->assign('params', $params);
        $block->assign('partslist', $this);
        $html = $block->toHtml();
        $mailHelper = Mage::helper('wws/mailer');
        $toAddress = $params['email'];
        Mage::log($html, null, 'xian.log');
        if (isset($toAddress)) {
            $args = array('subject' => Mage::helper('wishlist')->__('Endcustomer partslist'),
                'to' => $toAddress,
                'cc' => null,
                'bcc' => null,
                'body' => $html,
                'templateVars' => array()
            );
            $mailHelper->send($args);
        } else {
            throw new Exception('no receiver for email given');
        }
    }
}

?>
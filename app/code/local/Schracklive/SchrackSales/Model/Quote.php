<?php

class Schracklive_SchrackSales_Model_Quote extends Mage_Sales_Model_Quote {
    private $_qtySums = array();

	public function loadByCustomer($customer) {
		parent::loadByCustomer($customer);
		$this->setIsSuperMode(true);
		return $this;
	}

	public function getIsSuperMode() {
		return true;
	}

    public function removeItem($itemId) {
        try {
            throw new Exception();
        } catch ( Exception $ex ) {
            $session = Mage::getSingleton('customer/session');
            $schrackWwsCustomerId = $session->getCustomer()->getSchrackWwsCustomerId();
            $quoteId = $this->getId();
            $msg = "Quote->removeItem():  schrackWwsCustomerId = $schrackWwsCustomerId , quoteId = $quoteId ".PHP_EOL.$ex->getTraceAsString();
            Mage::log($msg,null,"del_quote_item.log");
        }
        parent::removeItem($itemId);
    }

    /**
     *
     * c.friedl: copied from parent, and amended
     * Adding catalog product object data to quote
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Sales_Model_Quote_Item
     */
    protected function _addCatalogProduct(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $newItem = false;
        $item = $this->getItemByProduct($product);
        if ( !$item || $product->getSchrackIsCable() ) { // don't add qty to existing product if cable
            $oldItem = $item;
            $item = Mage::getModel('sales/quote_item');
            if ( $oldItem && $oldItem->getSchrackOfferReference() > '' ) {
                $item->setSchrackOfferUnit($oldItem->getSchrackOfferUnit());
                $item->setSchrackOfferPricePerUnit($oldItem->getSchrackOfferPricePerUnit());
                $item->setSchrackOfferTax($oldItem->getSchrackOfferTax());
                $item->setSchrackOfferSurcharge($oldItem->getSchrackOfferSurcharge());
                $item->setSchrackOfferReference($oldItem->getSchrackOfferReference());
                $item->setSchrackOfferNumber($oldItem->getSchrackOfferNumber());
            }
            $item->setQuote($this);
            if (Mage::app()->getStore()->isAdmin()) {
                $item->setStoreId($this->getStore()->getId());
            }
            else {
                $item->setStoreId(Mage::app()->getStore()->getId());
            }
            $newItem = true;
        }

        /**
         * We can't modify existing child items
         */
        if ($item->getId() && $product->getParentProductId()) {
            return $item;
        }

        $item->setOptions($product->getCustomOptions())
            ->setProduct($product);

        // Add only item that is not in quote already (there can be other new or already saved item
        if ($newItem) {
            $this->addItem($item);
        }

        return $item;
    }


    /**
     * Advanced func to add product to quote - processing mode can be specified there.
     * Returns error message if product type instance can't prepare product.
     *
     * @param mixed $product
     * @param null|float|Varien_Object $request
     * @param null|string $processMode
     * @return Mage_Sales_Model_Quote_Item|string
     */
    public function addProductAdvanced(Mage_Catalog_Model_Product $product, $request = null, $processMode = null)
    {
        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = new Varien_Object(array('qty'=>$request));
        }
        if (!($request instanceof Varien_Object)) {
            Mage::throwException(Mage::helper('sales')->__('Invalid request for adding product to quote.'));
        }

        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced($request, $product, $processMode);

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

        $parentItem = null;
        $errors = array();
        $items = array();
        foreach ($cartCandidates as $candidate) {
            // Child items can be sticked together only within their parent
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);
            $item = $this->_addCatalogProduct($candidate, $candidate->getCartQty());
            $items[] = $item;

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getId()) {
                $item->setParentItem($parentItem);
            }

            /**
             * We specify qty after we know about parent (for stock)
             */
            $item->addQty($candidate->getCartQty());

            // BEGIN schracklive
            if ( $product->getSchrackIsCable() === '1' ) {
                if ( strlen($request->getSchrackDrumNumber()) ) {
                    $item->setSchrackDetailviewDrumNumber($request->getSchrackDrumNumber() . '-' . $request->getQty());
                } else {
                    $item->setSchrackDetailviewDrumNumber(null);
                }
            }
            // END schracklive

            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                $errors[] = $item->getMessage();
            }
        }
        if (!empty($errors)) {
            Mage::throwException(implode("\n", $errors));
        }

        Mage::dispatchEvent('sales_quote_product_add_after', array('items' => $items));

        return $item;
    }

    public function resetQtySumCache () {
        $this->_qtySums = array();
    }

    public function getSummarizedQtyForProduct(Mage_Catalog_Model_Product $product) {
        $prodId = $product->getId();
        
        if ( !isset($this->_qtySums[$prodId]) ) {
            $this->_qtySums[$prodId] = 0;
            $items = Mage::getModel('sales/quote_item')->getCollection()
                ->addFieldToFilter('product_id', $prodId)
                ->setQuote($this);
            foreach ($items as $item) {
                $this->_qtySums[$prodId] += $item->getQty();
            }

        }
        return $this->_qtySums[$prodId];
    }
}

?>
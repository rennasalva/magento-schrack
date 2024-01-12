<?php

class Schracklive_SchrackCheckout_Helper_Cart extends Mage_Core_Helper_Abstract {
    public function suggestQtyForDrums($product, $qty) {        
        if (Mage::helper('schrackcatalog/product')->hasDrums($product)) {
            $params = array('qty' => $qty);
            $suggestion = $this->getQtyAndDrumToBeSuggested($product, $params);
            if (isset($suggestion['newQty']))
                return $suggestion['newQty'];
            else
                return $qty;
        } else
            return $qty;
    }
    
    public function getSuggestionForProductAndQty($product, $qty) {
        if (Mage::helper('schrackcatalog/product')->hasDrums($product)) {
            $params = array('qty' => $qty);
            $suggestion = $this->getQtyAndDrumToBeSuggested($product, $params);
            return $suggestion;
        } else
            return array();
    }
    
    public function getQtyAndDrumToBeSuggested($product, $params) {
		if (array_key_exists('qty', $params) && !empty($params['qty'])) {
            $qty = $params['qty'];
        } else {
            $qty = 1;
        }

        $qty = str_replace(array(','), array('.'), $qty);

        if (stristr($qty, '.') || stristr($qty, ',')) {
            $qty = (float) $qty;
        }
        $drumNumber = Mage::helper('schrackcatalog/drum')->getDrumNumberFromQuery($params);

		$salesUnit = Mage::helper('schrackcatalog/product')->getSalesUnit($product); // gets delivery sales unit

		if (Mage::helper('schrackcatalog/product')->hasDrums($product)) { // checks for delivery stock
			$warehouseId = Mage::helper('schrackshipping/delivery')->getWarehouseId();
			$availableDrums = Mage::helper('schrackcatalog/drum')->getDrumsForWarehouse($product, $warehouseId, $qty);
//            unset($availableDrums['13|0']); DLA20171120: don't know why that one was removed, leaded to wrong qtys in cart
		} else {
			$drumNumber = -1;
			$availableDrums = array();
		}

		if (!Mage::helper('schrackcheckout/product')->isQtyAndDrumAllowed($qty, $drumNumber, $salesUnit, $availableDrums)) {
            if ( !isset($warehouseId) ) {
                $warehouseId = Mage::helper('schrackshipping/delivery')->getWarehouseId();
            }
			$possibleDrums = Mage::helper('schrackcatalog/drum')->getPossibleDrumsForWarehouse($product, $warehouseId, $qty);
            unset($possibleDrums[5]);

			$suggestion = Mage::helper('schrackcheckout/product')->suggestQtyAndDrum($product, $qty, $drumNumber, $salesUnit, $possibleDrums);

			if ($suggestion['newQty'] || $suggestion['newDrum']) {
				return $suggestion;
			}
		}

		return array();
	}


    public function deleteItems($params)
    {
        $idsOfItemsToDelete = $params['idsOfItemsToDelete'];
        $cartHelper = Mage::helper('checkout/cart');

        foreach($idsOfItemsToDelete as $deleteItemId){
            $cartHelper->getCart()->removeItem($deleteItemId)->save();
        }

        return array();
    }

    public function detectAvailabilityProblemAndReturnPopupHtml ( $product, $qty, $mode = 'normal' ) {
        $productHelper = Mage::helper('schrackcatalog/product');
        $available = 0;
        $qty = intval($qty);

        // Availability in stock:
        $available = intval($productHelper->getSummarizedStockQuantities($product));

        // Already in cart, if the affected product is a "Auslaufartikel":
        if ($product->isDiscontinuation() && $available > 0 && $mode == 'normal') {
            $sku = $product->getSku();
            $session = Mage::getSingleton('customer/session');
            $quote = $session->getQuote();
            if (!$quote) {
                $cart = Mage::getSingleton('checkout/cart');
                $quote = $cart->getQuote();
            }
            if ($quote) {
                foreach ($quote->getAllVisibleItems() as $item) {
                    $cartItemSku = $item->getSku();
                    if ($cartItemSku == $sku) {
                        $cartQty = intval($item->getQty());
                        $qty = $qty + $cartQty;
                    }
                }
            }
        }

        if ( $product->isRestricted() ) {
            $ok = false;
        } else if ( $product->isDiscontinuation() ) {
            $ok = $qty <= $available;
        } else {
            $ok = true;
        }

        if ( ! $ok ) {
            $block = Mage::app()->getLayout()->createBlock('Schracklive_SchrackCatalog_Block_Quantitywarningpopup');
            $block->setIsRestricted($product->isRestricted());
            $block->setQty($productHelper->formatQty($product,$available));
            $block->setAvailableQty($available);
            $block->setSku($product->getSku());
            $block->setCustomerQty($qty);
            $replacementProduct = $product->getLastReplacementProduct();
            if ( $replacementProduct ) {
                $block->setFolloupProduct($replacementProduct);
            }
            $block->setTemplate('catalog/quantitywarningpopup.phtml');
            $html = $block->toHtml();
            return $html;
        }
        return false;
    }

    public function checkAddToCart ( $product, $params ) {
        $addQty             = intval($params['qty']);
        $isCableLeaving     = isset($params['leaving']) ? $params['leaving'] : false;
        $cartQty            = intval($this->getCartQty($product,$params));
        $overallQty         = $addQty + $cartQty;
        $sku                = $product->getSku();
        $calcMinPkgQty      = intval($product->calculateMinimumQuantityPackage());
        $cumStockQties      = $product->getCumulatedPickupableAndDeliverableQuantities();
        $warningMessageText = false;
        $result             = array( 'messages' => array(), 'abortAddToCart' => false );

        // general check if product is saleable:
        if ( ! $product->isSaleable() || ! $product->isWebshopsaleable() ) {
            $msg = str_replace('%s', $product->getSku(), $this->__('Product %s currently not available.'));
            $result['messages'][] = $msg;
            $result['abortAddToCart'] = true;
            return $result;
        }

        // first special case bestellarticle without stock quantitites or order qty greater than stock qty (just look at suppliers batch size and min qty, ignore vpes):
        if ( $product->isBestellartikel() && ($cumStockQties <= 0 || $overallQty > $cumStockQties) ) {
            // we don't use calculateClosestHigherQuantityAndDifference() here because we need to check 2 different scenarios:
            $supplierMinQty = $product->getMinQtyFromSupplier();
            $supplierBatchSize = $product->getBatchSizeFromSupplier();
            if ( $overallQty < $supplierMinQty ) {
                $result['newQty'] = $supplierMinQty - $cartQty;
                $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'),
                                                        $sku, $result['newQty']);
                if ( $cartQty > 0 ) {
                    $warningMessageText .= '<br>' . sprintf($this->__('You already have %1$d of %2$s in your cart.'), $cartQty, $sku);
                }
            } else if ( $overallQty % $supplierBatchSize > 0 ) {
                $result['newQty'] = ((intval($overallQty / $supplierBatchSize) + 1) * $supplierBatchSize) - $cartQty;
                $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'),
                                                        $sku, $result['newQty'], $supplierBatchSize);
                if ( $cartQty > 0 ) {
                    $warningMessageText .= '<br>' . sprintf($this->__('You already have %1$d of %2$s in your cart.'), $cartQty, $sku);
                }
            }
            // else everything is ok for bestellartikel without stock quantities or order qty greater than stock qty
        } else if ( $overallQty % $calcMinPkgQty > 0 ) {
            // let's do calculateClosestHigherQuantityAndDifference() all the work:
            $resultQtyData = $product->calculateClosestHigherQuantityAndDifference($addQty, true, array(), 'checkAddToCart1');
            $result['newQty'] = $resultQtyData['closestHigherQuantity'];
            // just set the user messages correctly:
            if ( $product->isBestellartikel() ) { // remember: we are having stock quantities anyhow!
                // can different things go wrong:
                if ( $resultQtyData['previouslyExistingQuantity'] >= $resultQtyData['totalStockQuantity'] ) {
                    $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to next package unit of %2$d.'), $sku, $resultQtyData['closestHigherQuantity']);
                } else {
                    if ( $resultQtyData['availableStockQuantity'] < $resultQtyData['closestHigherQuantity'] ) {
                        $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to stock quantity of %2$d or next package unit of %3$d.'), $sku, $resultQtyData['availableStockQuantity'], $resultQtyData['closestHigherQuantity']);
                    } else {
                        $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to next package unit of %2$d.'), $sku, $resultQtyData['closestHigherQuantity']);
                    }
                }
                if ( $resultQtyData['previouslyExistingQuantity'] > 0 ) {
                    $warningMessageText .= '<br>' . sprintf($this->__('You already have %1$d of %2$s in your cart.'), $resultQtyData['previouslyExistingQuantity'], $sku);
                }
            } else { // on-stock article, use just vpe's
                $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'),
                                                        $sku,$result['newQty'],$product->calculateMinimumQuantityPackage());
                if ( isset($resultQtyData['previouslyExistingQuantity']) && $resultQtyData['previouslyExistingQuantity'] > 0 ) {
                    $warningMessageText .= '<br>' . sprintf($this->__('You already have %1$d of %2$s in your cart.'), $resultQtyData['previouslyExistingQuantity'], $sku);
                }
            }
        }

        if ( $warningMessageText ) {
            $result['messages'][] = $warningMessageText;
        }

        if ( ! $params['forceAdd'] ) {
            if ( $result && isset($result['newQty']) && $result['newQty'] != $params['qty'] && !$params['forceAdd'] ) {
                $result['abortAddToCart'] = true;
            }
            if ( $mainProduct = $product->getSaleableMainArticle() ) {
                $result['messages'][] = $this->__('Alternatively, you can order the desired length of the sliceable main article %s, which may incur cutting costs.',
                                                  sprintf('<a href="%1$s">%2$s</a>',$mainProduct->getProductUrl(),$mainProduct->getSku()));
            }
        }

        // if is main article (cable)
        if ( ! $result['abortAddToCart'] && $product->hasSubProducts() ) {
            $maxSize = intval($product->getGreatestSubArticleSize());
            // check if entered length exeeds size of biggest sub article
            if ( $maxSize > 0 && $addQty > $maxSize ) {
                $result['abortAddToCart'] = true;
                $result['newQty'] = $maxSize;
                $val = $maxSize . $product->getSchrackQtyunit();
                $result['messages'][] = $this->__("The entered length exceeds the maximum possible standard length of %s and has been reduced to this. For ordering special lengths, please contact your account manager.", $val);
            } else {
                $altProduct = $product->checkQtyForMatchingSubarticle($addQty);
                if ( $altProduct ) {
                    $result['messages'][] = $this->__("Instead of cutting the article %1s to length %2s, we have added the identical article %3s with batch '%4s' in the shopping cart, thereby cutting costs are avoided.",
                                                      $product->getSku(),
                                                      $params['qty'] . $product->getSchrackQtyunit(),
                                                      $altProduct->getSku(),
                                                      $this->__($altProduct->getSchrackStsMainVpeType()) );
                    $product = $result['newProduct'] = $altProduct;
                } else if ( ! $isCableLeaving ) {
                    $msg = $this->__("Attention: buying the article can cause cutting costs. Other batches without cutting costs are:") . ' ';
                    $add = '';
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $sql = "SELECT entity_id, sku, schrack_sts_main_vpe_type AS vpe, url.request_path AS url FROM catalog_product_entity prod"
                         . " JOIN core_url_rewrite url ON (url.product_id = prod.entity_id AND url.category_id IS NULL)"
                         . " WHERE schrack_sts_main_article_sku = ? AND schrack_sts_statuslocal NOT IN ('tot','strategic_no','unsaleable') AND url.store_id = ?"
                         . " ORDER BY sku";
                    $dbRes = $connection->fetchAll($sql,array($product->getSku(),Mage::app()->getStore()->getId()));
                    foreach ( $dbRes as $row ) {
                        $messagePartial = $this->mkDrumLinkHtml($row);
                        if ( $add != '' ) {
                            $add .= ', ';
                        }
                        $add .= $messagePartial;
                    }
                    $msg .= $add;
                    $result['messages'][] = $msg;
                }
            }
        }

        $msg = sprintf($this->__('%s was added to your shopping cart.'), Mage::helper('core')->escapeHtml($product->getName()));
        if ( ! $params['forceAdd'] && $result['abortAddToCart'] ) {
            $msg = $this->__('The item has not been added to shopping cart. Please check quantity and packaging.');
        }
        $result['messages'][] = $msg;
        return $result;
    }

    private function mkDrumLinkHtml ( $row ) {
        return '<a href="' . Mage::getUrl($row['url']) .'">'. $row['sku'] . '</a> <span white-space: nowrap;">' . $this->__($row['vpe']) . '</span>';
    }

    private function getCartQty ( $product, $params ) {
        if ( isset($params['ignoreCartQty']) && $params['ignoreCartQty'] ) {
            return 0;
        }
        $cart = Mage::getSingleton('checkout/cart');
        $cartQty = $cart->getQuote()->getSummarizedQtyForProduct($product);
        return $cartQty;
    }

    /**
     * returns a message (or false) for customer to upgrade to the next possible packing unit
     * @note: this works subtly different for the cart (with listId) than for the product detail view (no listId)
     *
     * @param $cart
     * @param $product
     * @param $qtyParam qty from web request
     * @param $listId
     */
    public function getPossiblePackingUnitUpgradeMessage ( $cart, $product, $qtyParam, $listId = null ) {
        $cartQty = $cart->getQuote()->getSummarizedQtyForProduct($product);

        // This function should return anything we need (triggers dialog message : true/false, returns difference, returns closest full package, etc.):
        $result = $product->calculateClosestHigherQuantityAndDifference($cartQty, false, array(), 'ignoreCartQuantity');

        $isCable = $product->getSchrackIsCable();
        $isBestellartikel = $product->isBestellArtikel();

        if ($product->isQtyInsidePackingunitLimit($cartQty) || $result['showHigherQuantityMessage']) {
            $beforeAddCartQty = doubleval($cartQty) - doubleval($qtyParam);

            $factor = $product->getPackingunitFactor($cartQty);
            if ($beforeAddCartQty == 0) {
                if ( $listId !== null ) { // cart!!!
                    $msg = $this->__('A full packing unit of \'%s\' contains %d %s. Do you want to upgrade your input to the next full packing unit? ' .
                        '<a href="javascript:setQty(\'%s\', %d); cartUpdate();">Yes</a>',
                        $product->getName(),
                        $result['closestHigherQuantity'],
                        $product->getSchrackQtyunit(),
                        $listId,
                        $result['closestHigherQuantity']
                    );
                } else {
                    $msg = $this->__('A full packing unit contains %d %s. Do you want to upgrade your input by %d %s to the next full packing unit? ' .
                        '<a href="javascript:setQty(\'%s\', %d);">Yes</a>',
                        $result['closestHigherQuantity'],
                        $product->getSchrackQtyunit(),
                        $result['differenceQuantity'],
                        $product->getSchrackQtyunit(),
                        $product->getId(),
                        $result['differenceQuantity']
                    );
                }
            } else {
                if ( $listId !== null ) { // cart!!!
                    if ($product->getName()) {
                        $productName = $product->getName();
                    } else {
                        $productName = '';
                    }
                    Mage::log('ProductName: ' . $product->getName());
                    $msg = $this->__('You now have %d %s of \'%s\' in your cart. ' .
                        'A full packing unit contains %d %s. Do you want to upgrade your input to the next full packing unit? ' .
                        '<a id="%s" href="javascript:jQuery(\'#%s\').parent().parent().parent().parent().remove(); setQty(\'%s\', %d); updateCart(\'upgrade\', \'%s\');">Yes</a>',
                        $cartQty,
                        $product->getSchrackQtyunit(),
                        $productName,
                        $result['closestHigherQuantity'],
                        $product->getSchrackQtyunit(),
                        $listId,
                        $listId,
                        $listId,
                        $product->getSchrackPackingunit() * $factor,
                        $listId
                    );
                } else {
                    $msg = $this->__('You currently have %d %s in your cart. ' .
                        'A full packing unit contains %d %s. Do you want to upgrade your input by %d %s to the next full packing unit? ' .
                        '<a href="javascript:setQty(\'%s\', %d);">Yes</a>',
                        $cartQty,
                        $product->getSchrackQtyunit(),
                        $result['closestHigherQuantity'],
                        $product->getSchrackQtyunit(),
                        $result['differenceQuantity'],
                        $product->getSchrackQtyunit(),
                        $product->getId(),
                        $result['differenceQuantity']
                    );
                }
            }

            if (!$isCable && !$isBestellartikel) {
                return $msg;
            }
        }
        return false;
    }

    public function getNumerbOfDifferentItemsInCart() {
        $cart = Mage::getSingleton('checkout/cart');
        $quoteItems = $cart->getQuote()->getItemsCollection();
        $counter = 0;
        foreach ($quoteItems as $item) {
            $counter++;
            $item->getId();
        }

        return $counter;
    }

}

?>

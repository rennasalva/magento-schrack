<?php

require_once('app/code/core/Mage/Checkout/controllers/CartController.php');

class Schracklive_SchrackCheckout_CartController extends Mage_Checkout_CartController {

    private $cartArticles = array();

    public function indexAction() {
        $cart = $this->_getCart();
        $quote = $cart->getQuote();
        Mage::register('quote_tricky',$quote);
        if ($quote->getItemsCount()) {
            $delFlag = false;
            foreach ( $quote->getItemsCollection() as $item ) {
                $product = $item->getProduct();
                if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                    Mage::helper('schrackcatalog/preparator')->prepareProduct($product);
                }
                if ( $product->isRestricted() || $product->isDead() || $product->isWebshopsaleable() == false ) {
                    $item->isDeleted(true);
                    $delFlag = true;
                    if ( $product->isDead() ) {
                        $msg = $this->__('Product %s is not longer available and was removed from your shopping cart.');
                    } else {
                        $msg = $this->__('Product %s is currently not available and was removed from your shopping cart.');
                    }
                    $msg = sprintf($msg, $product->getSku());
                    $cart->getCheckoutSession()->addNotice($msg);
                }
            }
            if ( $delFlag ) {
                $quote->collectTotals();
                $quote->save();
            }
/* ###################################################
            $cart->init();
            $cart->save();
   ################################################### */
            if (!$this->_getQuote()->validateMinimumAmount()) {
                $minimumAmount = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                    ->toCurrency(Mage::getStoreConfig('sales/minimum_order/amount'));

                $warning = Mage::getStoreConfig('sales/minimum_order/description')
                    ? Mage::getStoreConfig('sales/minimum_order/description')
                    : Mage::helper('checkout')->__('Minimum order amount is %s', $minimumAmount);
                
                $cart->getCheckoutSession()->addNotice($warning);
            }
        }

        // Compose array of messages to add
        $messages = array();
        foreach ($cart->getQuote()->getMessages() as $message) {
            if ($message) {
                // Escape HTML entities in quote message to prevent XSS
                $message->setCode(Mage::helper('core')->escapeHtml($message->getCode()));
                $messages[] = $message;
            }
        }
        $cart->getCheckoutSession()->addUniqueMessages($messages);

        /**
         * if customer enteres shopping cart we should mark quote
         * as modified bc he can has checkout page in another window.
         */
        $this->_getSession()->setCartWasUpdated(true);

        Varien_Profiler::start(__METHOD__ . 'cart_display');
        $this
            ->loadLayout()
            ->_initLayoutMessages('checkout/session')
            ->_initLayoutMessages('catalog/session')
            ->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));
        $leftBlock = $this->getLayout()->createBlock('Mage_Core_Block_Text_List', 'left');
        $this->getLayout()->getBlock('root')->append($leftBlock);
        $menuBlock = $this->getLayout()->createBlock('Schracklive_SchrackCustomer_Block_Account_Menu', 'customer_account_menu', array('template' => 'customer/account/menu.phtml'));
        $this->getLayout()->getBlock('left')->append($menuBlock);
        $this->renderLayout();
        Varien_Profiler::stop(__METHOD__ . 'cart_display');
        
    }
    
    public function setpickupAction() {
        $this->_getCart()->getQuote()->setIsPickup(true);
        $this->_getCart()->getQuote()->save();
        $this->_redirect('checkout/cart/');
    }
    
    public function setdeliveryAction() {
        $this->_getCart()->getQuote()->setIsPickup(false);
        $this->_getCart()->getQuote()->save();
        $this->_redirect('checkout/cart/');
    }
    
	public function makeofferAction() {
        $customerNo = $this->getRequest()->getParam('customerNo');
        $orderNo = $this->getRequest()->getParam('orderNo');
		$this->loadLayout();
		$block = $this->getLayout()->getBlock('makeoffer');
		if ( $customerNo && $orderNo ) {
		    $block->setCustomerNo($customerNo);
		    $block->setOrderNo($orderNo);
        }
		$this->renderLayout();
    }
    
    public function requestofferAction() {
        if ($this->getRequest()->isPost()) {
            $reqest = $this->getRequest();
            $eMail = $reqest->getPost('email');
            $schrackCustomOrderNumber = $reqest->getPost('schrack-custom-order-number');
            $customerNo = $reqest->getPost('customerNo');
            $printOffer = $reqest->getPost('printOffer') == 'yes';
            $realContact = null;
            try {
                if ( $customerNo ) {
                    $sql = "SELECT count(*) FROM account WHERE wws_customer_id = ?";
                    $dbRes = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql,$customerNo);
                    if ( $dbRes != 1 ) {
                        Mage::getSingleton('core/session')->addError($this->__('Wrong Customer ID') . " '" . $customerNo . "'");
                        $this->_redirect('checkout/cart/makeoffer');
                        return;
                    }
                    Mage::register('real_user_type_offer_cart', 'employee');
                    $session = Mage::getSingleton('customer/session');
                    $realContact = $session->getCustomer();
                    $offerContact = Mage::helper('account')->getSystemContactByWwsCustomerId($customerNo);
                    $session->setLoggedInCustomer($realContact);
                    $session->setCustomer($offerContact);
                    $quote = Mage::getSingleton('checkout/cart')->getQuote();
                    $quote->setCustomer($offerContact);
                    $quote->getShippingAddress()->setData($offerContact->getPrimaryBillingAddress()->getData());
                    $quote->save();
                }
                $helper = Mage::helper('schrackcheckout/offer');
                $orderNumber = $helper->doRequestOffer($eMail, $schrackCustomOrderNumber,$printOffer);
            } finally  {
                if ( $realContact ) {
                    $session->setCustomer($realContact);
                    $session->setLoggedInCustomer(null);
                }
            }
        }
        $urlArgs = $customerNo ? array('customerNo' => $customerNo, 'orderNo' =>  $orderNumber) : array();
		$this->_redirect('checkout/cart/makeoffer',$urlArgs);
    }
    
	/**
	 * Removes all items from current cart.
	 */
	public function emptyAction() {
		$this->_getCart()->truncate();
        $this->_getCart()->getItems()->save();
        $this->_getCart()->save();
		$this->_redirect('checkout/cart');
	}

	/**
	 * Deactivates current quote.
	 * Next access will create a new quote.
	 */
	public function renewAction() {
		$this->_getCart()->getQuote()->setIsActive(false);
		$this->_getCart()->getQuote()->save();

		$this->_getSession()->unsetAll();
		$this->_getSession()->clear();

		$this->_redirect('checkout/cart');
	}

	/**
	 * Add product to shopping cart action
	 * @see Mage_Checkout_CartController
	 */
	public function addAction() {
        if (!$this->_validateFormKey()) {
            $this->_goBack();
            return;
        }
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
        $listIndexQty = "";

        $numberOfItemsInCart = Mage::helper('checkout/cart')->getCart()->getItemsCount();
        if ($numberOfItemsInCart > Mage::getStoreConfig('sales/maximum_order/amount')) {
            // Too many items in cart: exceeded predefined limit! :
            Mage::getSingleton('core/session')->addNotice($this->__('Too Many Items In Your Cart'));
            return;
        }

        try {
            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                    $this->_goBack();
                    return;
            }

            $checkAddToCartRersult = Mage::helper('schrackcheckout/cart')->checkAddToCart($product,$params);

            if ( $checkAddToCartRersult['abortAddToCart'] ) {
                foreach ($checkAddToCartRersult['messages'] as $message) {
                    Mage::getSingleton('core/session')->addNotice($message);
                }
                $this->_addQtyAndDrumToReturnUrl($params,$checkAddToCartRersult,$this->_getProductUrl($product));
                $this->_goBack();
                return;
            }

			$cart->addProduct($product, $params);
			if (!empty($related)) {
				$cart->addProductsByIds(explode(',', $related));
			}

			$cart->save();

			$this->_getSession()->setCartWasUpdated(true);

			/**
			 * @todo remove wishlist observer processAddToCart
			 */
			Mage::dispatchEvent('checkout_cart_add_product_complete', array('product' => $product,
                                                                            'request' => $this->getRequest(),
                                                                            'response' => $this->getResponse())
			);

			if (!$this->_getSession()->getNoCartRedirect(true)) {
				if (!$cart->getQuote()->getHasError()) {
					$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
					$this->_getSession()->addSuccess($message);
				}
				$this->_goBack();
			}

			// Schracklive: remove quantity and drum number presets
			foreach ($this->getResponse()->getHeaders() as $header) {
				if ($header['name'] == 'Location') {
					$this->getResponse()->setHeader('Location', $this->_removeIgnoredParamsFromUrl($header['value']), true);
					break;
				}
			}

			if ( $msg = Mage::helper('schrackcheckout/cart')->getPossiblePackingUnitUpgradeMessage($cart, $product, $params['qty']) ) {
                $this->_getSession()->addSuccess($msg);
            }

        } catch (Mage_Core_Exception $e) {
			if ($this->_getSession()->getUseNotice(true)) {
				$this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
			} else {
				$messages = array_unique(explode("\n", $e->getMessage()));
				foreach ($messages as $message) {
					$this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
				}
			}

			$url = $this->_getSession()->getRedirectUrl(true);
			if ($url) {
				$this->getResponse()->setRedirect($url);
			} else {
				$this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
			}
		} catch (Exception $e) {
			$this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
			Mage::logException($e);
			$this->_goBack();
		}
	}

	

	protected function _getProductUrl($product) {
		$additionalUrlParams = array();

		if (Mage::getSingleton('catalog/session')->getLastVisitedCategoryId()) {
			$additionalUrlParams['category'] = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
		}
		return $product->getUrlModel()->getUrl($product, $additionalUrlParams);
	}

	protected function _addQtyAndDrumToReturnUrl(array $params, array $suggestions, $fallbackUrl = '') {
        $listIndexQty = false;
        if ( isset($suggestions['newQty']) ) {
            // Request from product category list view:
            if (    array_key_exists('request-source', $params) && array_key_exists('request-source-list-index', $params)
                 && $params['request-source'] == 'product-category-list-view' && $params['request-source-list-index'] > 0
            ) {
                $listIndexQty = 'changeqtylistindex=' . $params['request-source-list-index'] . '_' . $suggestions['newQty'];
            } // Request from product compare list view:
            else if (    array_key_exists('request-source', $params) && array_key_exists('request-source-compare-list-index', $params)
                      && $params['request-source'] == 'product-compare-list-view' && $params['request-source-compare-list-index'] > 0
            ) {
                $listIndexQty = 'changeqtylistindex=' . $params['request-source-compare-list-index'] . '_' . $suggestions['newQty'];
            }
        }

		$returnUrl = $this->_getCleanReturnUrl($fallbackUrl);
		if (strpos($returnUrl, '?') === false) {
			$returnUrl .= '?';
		} else {
			$returnUrl .= '&';
		}
		$returnParams = $this->_getNewReturnParams($params, $suggestions);
		$returnUrl .= implode('&', $returnParams);

        if ( $listIndexQty ) {
            $returnUrl .= '&' . $listIndexQty;
        }

		$this->getRequest()->setParam('return_url', $returnUrl);
	}

	protected function _getCleanReturnUrl($fallbackUrl = '') {
		$returnUrl = $this->getRequest()->getParam('return_url');
		if (!$returnUrl) {
			$returnUrl = $fallbackUrl;
		}
		if (!$returnUrl) {
			$returnUrl = $this->_getRefererUrl();
		}
		return $this->_removeIgnoredParamsFromUrl($returnUrl); // old query arguments
	}

	protected function _getNewReturnParams(array $params, array $suggestions) {
		$qty = $suggestions['newQty'] ? $suggestions['newQty'] : (isset($params['qty']) ? $params['qty'] : 0);
		if ($suggestions['newDrum'] && $suggestions['newDrum']->getWwsNumber()) {
			$drumNumber = $suggestions['newDrum']->getWwsNumber().'|'.$suggestions['newDrum']->getSize().'|'.($suggestions['newDrum']->getLessenDelivery() ? 1 : 0);
		} else {
			$drumNumber = isset($params['schrack_drum_number']) ? $params['schrack_drum_number'] : '';
		}

		$returnParams = array();
		if ($qty) {
			$returnParams[] = 'qty='.$qty;
		}
		if ($drumNumber) {
			$returnParams[] = 'schrack_drum_number='.$drumNumber;
		}
		return $returnParams;
	}

    /**
     * Remove qty, schrack_drum_number and list params
     *
     * @param $url
     * @return mixed
     */
	protected function _removeIgnoredParamsFromUrl($url) {
		$url = preg_replace('/(\?|&)(?:qty|schrack_drum_number|list)=[^&]*/', '$1', $url);

        if (stristr($url, '&changeqtylistindex=')) {
            $url = preg_replace('/&changeqtylistindex=\d+_\d+/', '', $url);
        }
        if (stristr($url, '&amp;changeqtylistindex=')) {
            $url = preg_replace('/\&amp;changeqtylistindex=\d+_\d+/', '', $url);
        }
        if (stristr($url, '?changeqtylistindex=')) {
            $url = preg_replace('/\?changeqtylistindex=\d+_\d+/', '', $url);
        }

		// remove remaining separators: change ?& into ? and && into &
		$url = preg_replace('/(\?|&)&+/', '$1', $url);
		$url = preg_replace('/\?$/', '', $url);
        $url = preg_replace('/&$/', '', $url);
		return $url;
	}
	
	public function quickaddAction() {
        $message = '';
        $warningMessageText = '';
		try {
			$queryname = Mage::helper('schrackcheckout/quickadd')->getQueryParamName();
            
			$sku = $this->getRequest()->getParam($queryname);
			$qty = $this->getRequest()->getParam('qty');

            $target = (strlen($this->getRequest()->getParam('target')) > 0) ? $this->getRequest()->getParam('target') : 'cart';

			if( $sku && $qty && is_numeric($qty) && (strval(intval($qty)) == strval($qty)) ) {

				$product_helper = Mage::getModel('schrackcatalog/product');
				$productId = $product_helper->getIdBySku($sku);
				
				if( $productId ) {
					$product = $product_helper->load($productId);
					if($product) {
                        if ($product->getData('schrack_sts_is_download')) {
                            Mage::getSingleton('core/session')->addError(str_replace('%s', $sku, $this->__('Product %s not found.', $sku)));
                            $product->setIsSalable('0');
                        }
                        switch ($target) {
                            case 'active-partslist':
                                $customer = Mage::getSingleton('customer/session')->getCustomer();
                                $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
                                try {
                                    $buyRequest = new Varien_Object($this->getRequest()->getParams());
                                    $result = $partslist->addNewItem($product, $buyRequest);
                                    if (is_string($result)) {
                                        Mage::throwException($result);
                                    }
                                    $partslist->save();
                                } catch (Exception $e) {
                                    Mage::getSingleton('core/session')->addError($e->getMessage());
                                }
                                break;
                            case 'current-partslist':
                                $customer = Mage::getSingleton('customer/session')->getCustomer();
                                $partslist = Mage::helper('schrackwishlist/partslist')->getActiveOrFirstPartslist();
                                try {
                                    $buyRequest = new Varien_Object($this->getRequest()->getParams());
                                    $suggestion = Mage::helper('schrackcheckout/cart')->getSuggestionForProductAndQty($product, $buyRequest['qty']);
                                    if (isset($suggestion['newQty']))
                                        $buyRequest['qty'] = $suggestion['newQty'];
                                    if (isset($suggestion['messages'])) {
                                        Mage::helper('schrackcore/array')->addSuccessesFromStrings(Mage::getSingleton('core/session'), $suggestion['messages']);
                                    }
                                    $result = $partslist->addNewItem($product, $buyRequest);
                                    if (is_string($result)) {
                                        Mage::throwException($result);
                                    }
                                    $partslist->save();
                                    $message = $this->__('%1$s has been added to your partslist.', $product->getName());
                                    Mage::getSingleton('core/session')->addSuccess($message);

                                    return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                                } catch (Exception $e) {
                                    Mage::getSingleton('core/session')->addError($e->getMessage());
                                }
                                break;
                            case 'wishlist':
                                $customer = Mage::getSingleton('customer/session')->getCustomer();  
                                $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer);
                                try {
                                    $buyRequest = new Varien_Object($this->getRequest()->getParams());
                                    $buyRequest['qty'] = Mage::helper('schrackcheckout/cart')->suggestQtyForDrums($product, $buyRequest['qty']);
                                    $result = $wishlist->addNewItem($product, $buyRequest);
                                    if (is_string($result)) {
                                        Mage::throwException($result);
                                    }
                                    $wishlist->save();
                                } catch (Exception $e) {
                                    Mage::getSingleton('core/session')->addError($e->getMessage());
                                }
                                break;
                            case 'cart':
                            default:
                                if ( $product->isSalable() ) {
                                    $cart = $this->_getCart();
                                    if ($product->isBestellartikel()) {
                                        $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty), true, array(), 'addCartQuantity11');
                                        $sku = $product->getSku();
                                        if (is_array($resultQtyData) && !empty($resultQtyData)) {
                                            $productMinQtyFromSupplier  = $resultQtyData['minQtyFromSupplier'];
                                            $batchSizeFromSupplier      = $resultQtyData['batchSizeFromSupplier'];
                                            $totalStockQuantity         = $resultQtyData['totalStockQuantity'];
                                            $availableStockQuantity     = $resultQtyData['availableStockQuantity'];
                                            $selectedQuantity           = intval($qty);
                                            $closestHigherQuantity      = $resultQtyData['closestHigherQuantity'];
                                            $differenceQuantity         = $resultQtyData['differenceQuantity'];
                                            $showBothLimitMessage       = $resultQtyData['showBothLimitMessage'];
                                            $previouslyExistingQuantity = intval($resultQtyData['previouslyExistingQuantity']);

                                            // Check, if there is a difference quantity. If not, than everything is okay, and bestellartikel has correct quantity:
                                            if ($differenceQuantity == 0 && $showBothLimitMessage == false) {
                                                $overrideIntensiveCheckForBestellArtikel = true;
                                            }
                                            if ($showBothLimitMessage == true) {
                                                $calculatedMinimumQuantity = $closestHigherQuantity;
                                            }
                                        }

                                        if ($product->getCumulatedPickupableAndDeliverableQuantities() <= 0) {
                                            if ($qty < $productMinQtyFromSupplier) {
                                                if ($resultQtyData['selectedQuantity'] > $productMinQtyFromSupplier) {
                                                    $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                                } else {
                                                    $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'), $sku, $productMinQtyFromSupplier);
                                                }
                                            } else {
                                                $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                            }
                                        } else {
                                            if ($previouslyExistingQuantity >= $totalStockQuantity) {
                                                $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                            } else {
                                                if (($previouslyExistingQuantity + $selectedQuantity) > $totalStockQuantity && ($previouslyExistingQuantity + $selectedQuantity) < $productMinQtyFromSupplier) {
                                                    $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to stock quantity of %2$d or next package unit of %3$d.'), $sku, ($availableStockQuantity + $previouslyExistingQuantity), ($closestHigherQuantity + $previouslyExistingQuantity));
                                                } else {
                                                    $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                                }
                                            }
                                        }

                                        if ($warningMessageText) {
                                            Mage::getSingleton('core/session')->addNotice($warningMessageText);
                                            Mage::getSingleton('core/session')->addNotice($this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName())));
                                        }

                                        // Finally set correctly calculated quantity:
                                        $qty = $closestHigherQuantity;
                                    } else {
                                        $qty = Mage::helper('schrackcheckout/cart')->suggestQtyForDrums($product, $qty);
                                    }

                                    $cart->addProduct($product, array('qty' => $qty));
                                    $cart->save();
                                    $this->_getSession()->setCartWasUpdated(true);
                                    if ($warningMessageText == '') {
                                        $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                                    }

                                    if ($message != '') {
                                        $this->_getSession()->addNotice($message);
                                    }
                                    Mage::getSingleton('core/session')->addNotice('<span style="font-weight: bold;">' . $this->__('Cart was updated') . '</span>');

                                } else {
                                    Mage::getSingleton('core/session')->addError(str_replace('%s', $sku, $this->__('Product %s currently not available.')));
                                    if ($target == 'current-partslist') {
                                        return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                                    }
                                }
                        }
                    }
					else {
                        Mage::getSingleton('core/session')->addError(str_replace('%s', $productId, $this->__('Product number %s not found.') ));
                        if ($target == 'current-partslist') {
                            return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                        }
					}	
				} else {
                    if (($productId = $this->_getIdForPartialSku( $sku )) !== null) {
                        if ($target == 'current-partslist') {
                            Mage::getSingleton('core/session')->addError(str_replace('%s', $sku, $this->__('Product %s not found.', $sku)));
                            return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                        }
                        $dummyJr = array();
                        $this->_addProductToCartById($product_helper, $productId, $qty, $dummyJr);
                    } else {
                        Mage::getSingleton('core/session')->addError(str_replace('%s', $sku, $this->__('Product %s not found.', $sku)));
                        if ($target == 'current-partslist') {
                            return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                        }
                    }
				}
			} else {
			    Mage::getSingleton('core/session')->addError($this->__('The entered values are not correct and/or not a number.'));
                if ($target == 'current-partslist') {
                    return $this->_redirect('wishlist/partslist/view', array('id' => $this->getRequest()->getParam('id')));
                }
              }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
        }

        $this->_goBack();
	}
    
    /**
     * 
     * @param type $product_helper
     * @param type $productId
     * @param type $qty
     */

    protected function _addProductToCartById( $product_helper, $productId, $qty, &$jsonResponse, $dontUpdateCart = false ) {
        $product = $product_helper->load( $productId );
        if ($product) {
            if ( $product->isSalable() ) {
                $cart = $this->_getCart();
                $cart->addProduct($product, array('qty' => $qty));
                if ( !$dontUpdateCart ) {
                    $cart->getQuote()->setDataChanges(true);
                    // DLA, 20180111: unexplainable, somtimes after empty cart and filling again, the values of
                    // quote.item_count and quote.item_qty are 0, what leads to a seemingly empty cart (what isn't true).
                    // Following workaround seems to fix that:
                    $allCnt = 0;
                    $allQty = 0.0;
                    foreach ( $cart->getQuote()->getAllItems() as $item ) {
                        $allCnt++;
                        $allQty += floatval($item->getQty());
                    }
                    $cart->getQuote()->setItemsCount($allCnt);
                    $cart->getQuote()->setItemsQty($allQty);
                    // end of workaround
                    $cart->save();
                    $this->_getSession()->setCartWasUpdated(true);
                }
                $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                $this->addSuccess($message, $jsonResponse);
            } else {
                Mage::getSingleton('core/session')->addError(str_replace('%s', $product->getSku(), $this->__('Product %s currently not available.')));
            }
        } else {
            Mage::getSingleton('core/session')->addError(str_replace('%s', $product->getSku(), $this->__('Product number %s not found.')));
        }
    }
	
    /**
     * assuming that $sku is a partial sku with only one match, return the id for that match
     * @param string $sku
     * @return productId / null
     */
    protected function _getIdForPartialSku( $sku ) {
        $queryvarname = Mage::helper('schrackcheckout/quickadd')->getQueryParamName();
        
		$productCollection = Mage::getModel('schrackcatalog/product')->getCollection();
        $productCollection = $productCollection->addAttributeToFilter( $queryvarname, array( 'like' => $sku .'%' ))->addAttributeToSort( $queryvarname, 'ASC' )->addStoreFilter();
        
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
		
        if ($productCollection->getSize() === 1)
            return $productCollection->getFirstItem()->getId();
        else
            return null;
    }
    
	public function suggestQuickaddAction() {
        $queryname = Mage::helper('schrackcheckout/quickadd')->getQueryParamName();
        
        if (!$this->getRequest()->getParam($queryname, false)) {
            $this->getResponse()->setRedirect(Mage::getSingleton('core/url')->getBaseUrl());
        }
        $this->getResponse()->setBody($this->getLayout()->createBlock('schrackcheckout/cart_quickadd')->toHtml());
	}

        
    /**
     * add multiple products from a csv file whose 1st column contains the sku, 
     * and the 2nd column the qty
     * 
     * @throws Exception
     */
    public function addCsvAction() {
        $failCount = 0;
        try {
            if(isset($_FILES['csv']['name']) && $_FILES['csv']['name'] != '') {            
                $tmpDir = sys_get_temp_dir();
                $fileName = $this->_storeUploadedFile('csv', $tmpDir, array('csv', 'txt'));
                $lines = file($fileName);
                unlink($fileName);
                $lines = Mage::helper('schrack/csv')->removeEmptyCsvLines($lines);

                if (count($lines) > intval(Mage::getStoreConfig('sales/maximum_order/amount'))) {
                    // Too many items in cart: exceeded predefined limit! :
                    $warningMessageText = $this->__('Too Many Items In Your File');
                    Mage::getSingleton('core/session')->addError($warningMessageText);
                } else if (count($lines) > 0) {
                    $delim = Mage::helper('schrackcore/csv')->determineDelimiter($lines[0]);
                    $successCount = 0;
                    foreach ($lines as $line) {
                        try {
                            while ( strlen($line) > 0 && (ord($line) < ord(' ') || ord($line) > ord('Z')) ) {
                                $line = substr($line,1); // remove UTF-8 BOM and other possible non-printable stuff
                            }
                            if ( $this->_csvLineContainsData($line, $delim) ) {
                                if ( $delim && strchr($line, $delim) ) {
                                    list($artNo, $qty) = str_getcsv($line, $delim);
                                } else {
                                    $artNo = trim($line);
                                    $qty = 0;
                                }

                                $product = Mage::getModel('schrackcatalog/product')->loadBySku($artNo);
                                if ( !($product && $product->getId()) ) {
                                    $product = Mage::getModel('schrackcatalog/product')->loadByAttribute('schrack_ean', $artNo);
                                }
                                if ( !($product && $product->getId()) ) {
                                    throw new Schracklive_SchrackCatalog_Model_NoSuchProductException($artNo);
                                }
                                $sku = $product->getSku();


                                if ( $product->isBestellartikel() ) {
                                    $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty), true, array(), 'addCartQuantity12');
                                    if ( is_array($resultQtyData) && !empty($resultQtyData) ) {
                                        $productMinQtyFromSupplier = $resultQtyData['minQtyFromSupplier'];
                                        $batchSizeFromSupplier = $resultQtyData['batchSizeFromSupplier'];
                                        $totalStockQuantity = $resultQtyData['totalStockQuantity'];
                                        $availableStockQuantity = $resultQtyData['availableStockQuantity'];
                                        $selectedQuantity = intval($qty);
                                        $closestHigherQuantity = $resultQtyData['closestHigherQuantity'];
                                        $differenceQuantity = $resultQtyData['differenceQuantity'];
                                        $showBothLimitMessage = $resultQtyData['showBothLimitMessage'];
                                        $previouslyExistingQuantity = intval($resultQtyData['previouslyExistingQuantity']);

                                        // Check, if there is a difference quantity. If not, than everything is okay, and bestellartikel has correct quantity:
                                        if ( $differenceQuantity == 0 && $showBothLimitMessage == false ) {
                                            $overrideIntensiveCheckForBestellArtikel = true;
                                        }
                                        if ( $showBothLimitMessage == true ) {
                                            $calculatedMinimumQuantity = $closestHigherQuantity;
                                        }
                                    }

                                    if ( $product->getCumulatedPickupableAndDeliverableQuantities() <= 0 ) {
                                        if ( $qty < $productMinQtyFromSupplier ) {
                                            $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'), $product->getSku(), $productMinQtyFromSupplier);
                                        } else {
                                            $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                        }
                                    } else {
                                        if ( $previouslyExistingQuantity >= $totalStockQuantity ) {
                                            $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                        } else {
                                            if ( ($previouslyExistingQuantity + $selectedQuantity) > $totalStockQuantity && ($previouslyExistingQuantity + $selectedQuantity) < $productMinQtyFromSupplier ) {
                                                $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to stock quantity of %2$d or next package unit of %3$d.'), $sku, ($availableStockQuantity + $previouslyExistingQuantity), ($closestHigherQuantity + $previouslyExistingQuantity));
                                            } else {
                                                $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                            }
                                        }
                                    }

                                    if ( $warningMessageText ) {
                                        Mage::getSingleton('core/session')->addNotice($warningMessageText);
                                        Mage::getSingleton('core/session')->addNotice($this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName())));
                                    }

                                    // Finally set correctly calculated quantity:
                                    $qty = $closestHigherQuantity;
                                } else {
                                    $resultQtyData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity3');
                                    if ( $resultQtyData['invalidQuantity'] == true ) {
                                        $qty = $resultQtyData['closestHigherQuantity'];
                                        $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $qty);
                                        Mage::getSingleton('core/session')->addNotice($warningMessageText);
                                    }
                                    $qty = Mage::helper('schrackcheckout/cart')->suggestQtyForDrums($product, $qty);
                                }

                                $dummyJr = array();
                                $this->_addProductToCartBySku($sku, $qty, $dummyJr, true);
                                $successCount++;
                            }
                        } catch ( Schracklive_SchrackCatalog_Model_NoSuchProductException $nspEx ) {
                            Mage::getSingleton('core/session')->addError($this->__($nspEx->getMessageFormat(),$nspEx->getSku()));
                            $failCount++;
                        } catch ( Exception $e ) {
                            Mage::getSingleton('core/session')->addError($this->__('Could not read CSV line %s', $line));
                            $failCount++;
                        }
                    }
                    if ( $successCount > 0 ) {
                        $cart = $this->_getCart();
                        $cart->getQuote()->setDataChanges(true);
                        $cart->save();
                        $this->_getSession()->setCartWasUpdated(true);
                        Mage::getSingleton('core/session')->addSuccess($this->__('%d products where added to cart.', $successCount));
                    }
                } else {
                    Mage::getSingleton('core/session')->addError($this->__('CSV File was empty.'));
                }
            }
        
            if ($failCount > 0) {
                $message = $this->__('%d product(s) could not be added to your shopping cart.', $failCount); 
                Mage::getSingleton('core/session')->addError($message);

            }
        } catch(Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
        }
        $this->_redirect('checkout/cart/');
    }

    
    /**
     * add multiple items to cart by sku. This is a pure ajax action that 
     * returns a summed-up ok message, or an error string via json
     */
    public function batchAddAction() {
        $jsonResponse = array();
        $params = $this->getRequest()->getParams();
        $successCount = 0;
        $successAction = true;

        if (isset($params['products'])) {
            $products = explode(';', $params['products']);

            $numberOfItemsInCart = Mage::helper('checkout/cart')->getCart()->getItemsCount();
            if ((intval($numberOfItemsInCart) + count($products)) > intval(Mage::getStoreConfig('sales/maximum_order/amount'))) {
                // Too many items in cart: exceeded predefined limit! :
                $warningMessageText = $this->__('Too Many Items In Your Cart');
                Mage::getSingleton('core/session')->addError($warningMessageText);
                $successAction = false;
            } else {
                foreach ($products as $product) {
                    list($sku, $qty) = explode(':', $product);
                    $this->_addProductToCartBySku($sku, $qty, $jsonResponse);
                    $successCount++;
                }
            }
        }
        if ($successCount > 1) {
            $this->removeSuccessMessages($jsonResponse);        
            $this->addSuccess($this->__('%d products where added to cart.', $successCount), $jsonResponse);        
        }
            
        $jsonResponse['ok'] = $successAction;
        $numberOfDifferentItemsInCart = intval(Mage::helper('checkout/cart')->getSummaryCount());
        $jsonResponse['numberOfDifferentItemsInCart'] = $numberOfDifferentItemsInCart;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
    }
    
     public function batchAddDocumentsAction() {
        $successCount = 0;
        if ($this->getRequest()->isAjax()) {
            $jsonResponse = array();
            $paramDocuments = $this->getRequest()->getParam('documents');
            if (isset($paramDocuments)) { 
               $documents = explode(';', $paramDocuments);
                foreach ($documents as $document) {
                    list ($docId, $type) = explode(':', $document);
                    $this->_addDocument($type, $docId);                    
                    $successCount++;
                }
            }
            if ($successCount > 0) {
                $this->removeSuccessMessages($jsonResponse);
                if ($successCount > 1)
                    $this->addSuccess($this->__('%d documents where added to cart.', $successCount), $jsonResponse);
                else
                    $this->addSuccess($this->__('1 document was added to cart.', $successCount), $jsonResponse);
            }


            $jsonResponse['ok'] = true;
            $this->getREsponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
        }
        else
            throw new Exception('invalid action for non-ajax request');
    }
     
    public function downloadCsvAction() {
        Mage::helper('schrack/csv')->createCsvDownloadFromCart($this->_getCart());
    }
    
    public function teststartmenuAction() {        
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function testfetchallAction() {
        $model = Mage::getModel('schrackcatalog/category_api_v2');
        $x = $model->fetchall();
        header('Content-type: text/plain');
        die($x);
    }


    /**
     * Update shoping cart data action
     */
    public function updatePostAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        } //Nagarro : Added form key
        try {
            $cartData = $this->getRequest()->getParam('cart');

            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    $warningMessageText = '';
                    if (isset($data['qty'])) {
                        if (stristr($data['qty'], '.') || stristr($data['qty'], ',')) {
                            $data['qty'] = (float) $data['qty'];
                        }

                        // Cable identifier: drum = 22 (boxed cable):
                        // Wird aufgrund der Komplexit�t evtl sp�ter noch verwendet:
                        /*
                        if ($data['drum'] == 22 && $data['sku'] && $data['qty']) {
                            $product = Mage::getModel('catalog/product')->loadBySku($data['sku']);
                            $salesUnit = Mage::helper('schrackcatalog/product')->getSalesUnit($product);
                            if (fmod($data['qty'], $salesUnit) != 0) {
                                $qtyAndDrumSuggestions = Mage::helper('schrackcheckout/cart')->getQtyAndDrumToBeSuggested($product, array('qty' => $data['qty']));
                            }

                            if ($qtyAndDrumSuggestions && ($qtyAndDrumSuggestions['newQty'] != $data['qty'])) {
                                $data['qty'] = $qtyAndDrumSuggestions['newQty'];
                            }
                        }
                        */
                        $product = Mage::getModel('catalog/product')->loadBySku($data['sku']);
                        if (!$product) {
                            throw new Exception($this->__('Product not found by sku.'));
                        }

                        $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($data['qty']), true, array());
                        $closestHigherQuantity = $resultQtyData['closestHigherQuantity'];
                        $calculatedMinimumQuantity = $product->calculateMinimumQuantityPackage();

                        if (is_array($resultQtyData) && !empty($resultQtyData) && $product->isBestellartikel()) {
                            //var_dump($resultQtyData); die();
                            // Check, if there is a difference quantity. If not, than everything is okay, and bestellartikel has correct quantity:
                            if ($resultQtyData['differenceQuantity'] == 0 && $resultQtyData['showBothLimitMessage'] == false) {
                                $overrideIntensiveCheckForBestellArtikel = true;
                            }
                            if ($resultQtyData['showBothLimitMessage'] == true) {
                                $calculatedMinimumQuantity = $closestHigherQuantity;
                            }
                        }

                        // Normal product (no cable!) available (salable and deliverable) quantity check:
                        if (!$data['drum'] && fmod($data['qty'], $calculatedMinimumQuantity) != 0) {
                            if ($product->isBestellartikel()) {
                                $productMinQtyFromSupplier = $resultQtyData['minQtyFromSupplier'];
                                $batchSizeFromSupplier     = $resultQtyData['batchSizeFromSupplier'];
                                $totalStockQuantity        = $resultQtyData['totalStockQuantity'];
                                $availableStockQuantity    = $resultQtyData['availableStockQuantity'];
                                $selectedQuantity          = $resultQtyData['selectedQuantity'];

                                if ($product->getCumulatedPickupableAndDeliverableQuantities() <= 0) {
                                    if ($data['qty'] < $productMinQtyFromSupplier) {
                                        $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'), $product->getSku(), $productMinQtyFromSupplier);
                                    } else {
                                        $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), $product->calculateMinimumQuantityPackage(), $batchSizeFromSupplier);
                                    }
                                } else {
                                    if ($selectedQuantity > $totalStockQuantity) {
                                        if ($selectedQuantity > $product->calculateMinimumQuantityPackage()) {
                                            if ($selectedQuantity > ($closestHigherQuantity - $batchSizeFromSupplier + $availableStockQuantity)) {
                                                $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to next package unit of %2$d.'), $product->getSku(), $closestHigherQuantity);
                                            } else {
                                                $closestHigherQuantity = $selectedQuantity;
                                            }
                                        }

                                        if ($selectedQuantity < $productMinQtyFromSupplier) {
                                            $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to next package unit of %2$d.'), $product->getSku(), $productMinQtyFromSupplier);
                                            $closestHigherQuantity = $productMinQtyFromSupplier;
                                        }
                                    }
                                }
                            } else {
                                $showResultQuantity = $product->calculateMinimumQuantityPackage();
                                if ($closestHigherQuantity != intval($data['qty'])) {//var_dump($showResultQuantity); die();
                                    // Only show message, if the quantity has really changed by the system because of valid recalculation:
                                    $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), '', $showResultQuantity);
                                }
                            }
                            if ($warningMessageText != '') {
                                Mage::getSingleton('core/session')->addNotice($warningMessageText);
                            }

                            $data['qty'] = $closestHigherQuantity;
                        }
                        $newqty = $filter->filter($data['qty']);
                        if ( ! is_array($newqty) ) { // DLA, 20160826: if result is an array, then that bloody zend filter returns trash...
                            $cartData[$index]['qty'] = $newqty;
                        }
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                     ->save();
                foreach ( $cartData as $index => $data ) {
                    $product = Mage::getModel('catalog/product')->loadBySku($data['sku']);
                    if ( !$product ) {
                        throw new Exception($this->__('Product not found by sku.'));
                    }

                    // Add accumulation action as done to memory-variable:
                    if (!in_array($data['sku'], $this->cartArticles)) {
                        array_push($this->cartArticles, $data['sku']);
                        if ( $msg = Mage::helper('schrackcheckout/cart')->getPossiblePackingUnitUpgradeMessage($cart,$data['qty'],$index) ) {
                            $this->_getSession()->addSuccess($msg);
                        }
                    }
                }
            }
            Mage::getSingleton('core/session')->addNotice('<span style="font-weight: bold;">' . $this->__('Cart was updated') . '</span>');
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
        $this->_goBack();
    }

    
    private function _addDocument($type, $documentId) {
        $helper = Mage::helper('schracksales/order');
        $document = $helper->getFullDocument($documentId,$type);
        $successCount = 0;
        $items = $document->getItemsCollection();
        foreach ($items as $item) {
            if (in_array($type, array('offer', 'order'))) $qty = $item->getQtyOrdered();
            else $qty = $item->getQty();
            $dummyJr = array();
            $this->_addProductToCartBySku($item->getSku(), $qty, $dummyJr);
            $successCount++;
        }
        return $successCount;
    }

    /**
     * 
     * @param string $inputName
     * @param string $subdirName
     * @param array $allowedExtensions
     * @return file name
     * 
     */
    protected function _storeUploadedFile($inputName, $dirName, array $allowedExtensions) {
        $path = $dirName . DS;  //desitnation directory     
        $fname = $_FILES[$inputName]['name']; //file name                        
        $uploader = new Varien_File_Uploader($inputName); //load class
        $uploader->setAllowedExtensions($allowedExtensions); //Allowed extension for file
        $uploader->setAllowCreateFolders(true); //for creating the directory if not exists
        $uploader->setAllowRenameFiles(true); //if true, uploaded file's name will be changed, if file with the same name already exists directory.
        $uploader->setFilesDispersion(false);
        $uploader->save($path, $fname); //save the file on the specified path
        return $path . $uploader->getUploadedFileName();
    }

    /**
     * heuristically try to determine whether the given text might be a csv line
     * we can use
     * 
     * @param string $line
     */
    protected function _csvLineContainsData($line,$delim) {
        // return (preg_match('/^"?\w+[\w\-]*"?[,;\\t]"?\d+((.|,)\d+)?"?/', $line) === 1);
        if ( ! $line ) {
            return false;
        }
        $line = trim($line);
        if ( strlen($line) < 1 ) {
            return false;
        }
        if ( $delim ) {
            $word = explode($delim,$line)[0];
        } else {
            $word = $line;
        }
        $l = strlen($word);
        if ( $l < 10 || $l > 15 ) {
            return false;
        }
        if ( preg_match('/.*[a-z].*/', $word) === 1 ) {
            return false;
        }
        return true;
    }
    /**
     * 
     * @param string $sku
     * @param float $qty
     */
    protected function _addProductToCartBySku($sku, $qty, &$jsonResponse, $dontUpdateCart = false) {
        $product_helper = Mage::getModel('schrackcatalog/product');
        $productId = $product_helper->getIdBySku($sku);

        if ($productId) {
            $this->_addProductToCartById($product_helper, $productId, $qty, $jsonResponse, $dontUpdateCart);
        }
        else
            throw new Exception($this->__('Unable to find product for sku %s', $sku));
    }
    
    /**
     * check that the unique identifier sent with the data is really unique, so we don't inadvertently re-add the items
     * (NOT a security measure, just a precaution against user error!!!)
     * 
     * @param string $uid
     * @throws Exception
     */
    protected function _checkUniqueBatchIdentifier($uid) {
        if (!isset($uid))
            throw new Exception('Unique batch identifier must be set.');
        if (strlen($uid) !== 4)
            throw new Exception('Invalid batch identifier.');
        $sessionUid = Mage::getSingleton('core/session')->getCartUniqueBatchIdentifier();
        if ($sessionUid === $uid)
            throw new Exception('Unique batch identifier has been used before.');
        Mage::getSingleton('core/session')->setCartUniqueBatchIdentifier($uid);
    }
    
    private function addError($msg, &$jsonResponse) {
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax()) {
            if (!isset($jsonResponse['errors']) || !is_array($jsonResponse['errors']))
                $jsonResponse['errors'] = array();
            array_push($jsonResponse['errors'], $msg);
        } else
            Mage::getSingleton('core/session')->addError($msg);        
    }

    private $msgMap = []; // to avoid doubled success messages

    private function addSuccess($msg, &$jsonResponse) {
        if ( isset($this->msgMap[$msg]) ) {
            return;
        }
        $this->msgMap[$msg] = true;
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax()) {
            if (!isset($jsonResponse['messages']) || !is_array($jsonResponse['messages']))
                $jsonResponse['messages'] = array();
            array_push($jsonResponse['messages'], $msg);
        } else
            Mage::getSingleton('core/session')->addSuccess($msg);
    }
    
    private function removeSuccessMessages(&$jsonResponse) {
        $jsonResponse['messages'] = array();
    }
    
    public function addProductToPartslistAction() {
        $jsonResponse = array();        
        if (!strlen(Mage::app()->getRequest()->getParam('id'))) {
            $model = Mage::getModel('schrackwishlist/partslist');                      
            $partslist = $model->create(Mage::getSingleton('customer/session')->getCustomer()->getId(), $this->getRequest()->getParam('description'));
            $jsonResponse['isNew'] = true;
            $name = Mage::app()->getRequest()->getParam('name');
            if (strlen($name)) {
                $partslist->setDescription($name);
            }
            $comment = Mage::app()->getRequest()->getParam('comment');
            if (strlen($comment)) {
                $partslist->setComment($comment);
            }
        } else {
            $partslist = Mage::getModel('schrackwishlist/partslist')
                ->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(), Mage::app()->getRequest()->getParam('id'));
        }
        if (!$partslist) {
            $this->_redirectNoAjax('*/*');
            return;
        }

        $productId = (int) $this->getRequest()->getParam('product');
        

        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId()) {
            $productId = $this->getRequest()->getParam('product');        
            $product = Mage::getModel('catalog/product')->loadBySku($productId);
        }
        if (!$product->getId()) {
            $this->addError($this->__('Cannot specify product.'),$jsonResponse);
            $this->_redirectNoAjax('*/');
            return;
        }
        
        Mage::helper('schrackwishlist/partslist')->addProduct($partslist, $product, $jsonResponse);

        $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
    }
    
    
    protected function _redirectRefererNoAjax($body = null, $defaultUrl = null) {
        if ($this->getRequest()->isAjax())
            $this->getResponse()->setBody ($body);
        else
            $this->_redirectReferer($defaultUrl);        
    }

	/**
	 * @return string
	 * @see Mage_Core_Controller_Varien_Action::_getRefererUrl
	 */
	protected function _getRefererUrl()	{
		$refererUrl = $this->getRequest()->getServer('HTTP_REFERER');
		if ($url = $this->getRequest()->getParam(self::PARAM_NAME_REFERER_URL)) {
			$refererUrl = $url;
		}
		if ($url = $this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
			$refererUrl = Mage::helper('core')->urlDecodeAndEscape($url);
		}
		if ($url = $this->getRequest()->getParam(self::PARAM_NAME_URL_ENCODED)) {
			$refererUrl = Mage::helper('core')->urlDecodeAndEscape($url);
		}

		if (!$this->_isDomainInternal($refererUrl)) {
			$refererUrl = Mage::app()->getStore()->getBaseUrl();
		}

		return $refererUrl;
	}

	/**
	 * Check domain to be used as internal
	 *
	 * @param   string $url
	 * @return  bool
	 */
	protected function _isDomainInternal($url) {
		if (strpos($url, 'http') !== false) {
			$source = array();
			if (Mage::app()->getStore()->getBaseUrl()) {
				$source = parse_url(Mage::app()->getStore()->getBaseUrl());
			}
			$target = parse_url($url);

			if ($source['host'] && $target['host'] && $source['host'] == $target['host']) {
				return true;
			}
		}
		return false;
	}

    public function deleteItemsAction(){
        $idsOfItemsToDelete = $this->getRequest()->getParam('deleteItems');
        Mage::helper('schrackcheckout/cart')->deleteItems(array('idsOfItemsToDelete' => $idsOfItemsToDelete));
        return 'done';
    }

    /*
    Share Shoping Cart
    */
    public function shareshoppingcartAction() {
		$this->loadLayout();
		$block = $this->getLayout()->getBlock('shareshoppingcart');
		$this->renderLayout();
    }

    protected function _addToCartFromRequest() {
        $checkoutHelper = Mage::helper('schrackcheckout/cart');
        $failCount = 0;
        try {
            $productsReq = $this->getRequest()->getParam('products');
            $products = explode(';', $productsReq);
            if (isset($products) && count($products) > 0) {
                $successCount = 0;
                foreach ( $products as $product ) {
                    try {
                        list($searchValue, $qty) = explode(':', $product);

                        $product = Mage::getModel('schrackcatalog/product')->loadByAttribute('sku',$searchValue,Schracklive_SchrackCatalog_Model_Product::getAdditionalEavAttributeCodesForLists());
                        if (!($product && $product->getId())) {
                            $product = Mage::getModel('schrackcatalog/product')->loadByAttribute('schrack_ean',$searchValue,Schracklive_SchrackCatalog_Model_Product::getAdditionalEavAttributeCodesForLists());
                        }
                        if (!($product && $product->getId())) {
                            throw new Exception($this->__('No such product as %s', $searchValue));
                        }

                        $params = array('qty' => $qty, 'sku' => $product->getSku(),  'forceAdd' => true);
                        $checkAddToCartResult = $checkoutHelper->checkAddToCart($product, $params);
                        if ( $checkAddToCartResult['abortAddToCart'] ) {
                            foreach ( $checkAddToCartResult["messages"] as $msg ) {
                                Mage::getSingleton('core/session')->addError($msg);
                            }
                            $failCount++;
                            continue;
                        }
                        foreach ( $checkAddToCartResult["messages"] as $msg ) {
                            $dummyJson = [];
                            $this->addSuccess($msg, $dummyJson);
                        }
                        if ( isset($checkAddToCartResult['newQty']) ) {
                            $qty = $checkAddToCartResult['newQty'];
                        }
                        if ( isset($checkAddToCartResult['newProduct']) ) {
                            $product = $checkAddToCartResult['newProduct'];
                        }

                        $this->_addProductToCartBySku($product->getSku(), $qty, $dummyJr);
                        $successCount++;
                    } catch (Exception $e) {
                        Mage::logException($e);
                        Mage::getSingleton('core/session')->addError($this->__('Could not read CSV line %s', $product));
                        $failCount++;
                    }
                }
                Mage::getSingleton('core/session')->addSuccess($this->__('%d products where added to cart.', $successCount));
            } else {
                Mage::getSingleton('core/session')->addError($this->__('CSV File was empty.'));
            }

            if ($failCount > 0) {
                $message = $this->__('%d product(s) could not be added to your shopping cart.', $failCount);
                Mage::getSingleton('core/session')->addError($message);
            }
        } catch(Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
        }

    }


    public function getProductslistAsSkulistByDocumentAction () {
        if ($this->_validateFormKey()) {
            $type       = $this->getRequest()->getParam('type');
            $documentId = $this->getRequest()->getParam('documentId');

            if ($type && $documentId) {
                try {
                    $helper = Mage::helper('schracksales/order');
                    $document = $helper->getFullDocument($documentId,$type);
                    if ($document) {
                        $items = $document->getItemsCollection();
                    } else {
                        echo $this->__('No such dcument with ID = ' . $documentId);
                        die();
                    }
                    $listProducts = array();
                    if (is_array($items) && !empty($items)) {
                        foreach ($items as $item) {
                            try {
                                $sku = $item->getSku();
                                if (!in_array($sku, array('TRANSPORT-','MANIPULAT-','VERPACKUNG'))) {
                                    $name = $item->getName();
                                    $price = $item->getPrice();
                                    $product = $item->getProduct();
                                    if ($product) {
                                        $category = $product->getCategoryId4googleTagManager();
                                    } else {
                                        $product = Mage::getModel('schrackcatalog/product')->loadBySku($sku);                                    ;
                                        if ($product) {
                                            $category = $product->getCategoryId4googleTagManager();
                                        } else {
                                            Mage::log('Tracking Problem in CartController.php -> ' . $sku, null, 'google_tracking_problem.log');
                                        }
                                    }
                                    $qty = $item->getQtyOrdered();
                                    if (intval($qty) == 0) {
                                        $qty = $item->getQty();
                                        if (intval($qty) == 0) {
                                            $qty = 1;
                                        }
                                    }
                                    $articleDataObject = new stdClass();

                                    if (intval(Mage::getStoreConfig('ec/config/active')) == 1) {
                                        $trackingEnabled = 'enabled';
                                    } else {
                                        $trackingEnabled = 'disabled';
                                    }
                                    $articleDataObject->trackingEnabled = $trackingEnabled;
                                    $articleDataObject->sku             = $sku;
                                    $articleDataObject->name            = $name;
                                    $articleDataObject->price           = number_format((float) str_replace(',', '.', $price), 2, '.', '');
                                    $articleDataObject->category        = $category;
                                    $articleDataObject->currencyCode    = Mage::app()->getStore()->getCurrentCurrencyCode();
                                    $articleDataObject->quantity        = (string) intval($qty);
                                    $articleDataObject->pagetype        = 'document';

                                    $listProducts[] = $articleDataObject;
                                }
                            } catch (Exception $e) {
                                echo json_encode(array('error' => 'An error occurred while fetching a product-sku from a document: ' .  $e->getMessage()));
                                die();
                            }
                        }
                    } else {
                        echo json_encode(array('error' => 'No Items found for dcoument-id (#1) (CartController) : ' .  $documentId));
                        die();
                    }
                    if (is_array($listProducts) && !empty($listProducts)) {
                        echo json_encode($listProducts);
                    } else {
                        echo json_encode(array('error' => 'No Items found for dcoument-id (CartController) (#2) : ' .  $documentId));
                        die();
                    }
                } catch (Exception $e) {
                    echo json_encode(array('error' => 'An error occurred while adding a document to cart: ' .  $e->getMessage()));
                    die();
                }
            } else {
                echo json_encode(array('error' => 'No Document-Id Or Type Given In Request'));
            }
        } else {
            echo json_encode(array('error' => 'invalid form_key'));
            die();
        }
    }

}

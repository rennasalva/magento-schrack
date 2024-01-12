<?php

/**
 * needs to be manually required...
 */
require_once('app/code/core/Mage/Wishlist/controllers/IndexController.php');

/**
 * IndexController
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_IndexController extends Mage_Wishlist_IndexController {
    
    /**
     * Adding new item
     *
     * @return Mage_Core_Controller_Varien_Action|void
     */
    public function addAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*');
        }
        $this->_addItemToWishList();
    }

    /**
     * Add the item to wish list
     *
     * @return Mage_Core_Controller_Varien_Action|void
     */
    protected function _addItemToWishList()
    {
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            return $this->norouteAction();
        }

        $session = Mage::getSingleton('customer/session');

        $productId = (int)$this->getRequest()->getParam('product');
        if (!$productId) {
            $this->_redirect('*/');
            return;
        }

        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            $session->addError($this->__('Cannot specify product.'));
            $this->_redirect('*/');
            return;
        }

        try {
            $requestParams = $this->getRequest()->getParams();
            if ($session->getBeforeWishlistRequest()) {
                $requestParams = $session->getBeforeWishlistRequest();
                $session->unsBeforeWishlistRequest();
            }
            $buyRequest = new Varien_Object($requestParams);

            $result = $wishlist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                Mage::throwException($result);
            }
            $wishlist->save();

            Mage::dispatchEvent(
                'wishlist_add_product',
                array(
                    'wishlist' => $wishlist,
                    'product' => $product,
                    'item' => $result
                )
            );

            $referer = $session->getBeforeWishlistUrl();
            if ($referer) {
                $session->setBeforeWishlistUrl(null);
            } else {
                $referer = $this->_getRefererUrl();
            }

            /**
             *  Set referer to avoid referring to the compare popup window
             */
            $session->setAddActionReferer($referer);

            Mage::helper('wishlist')->calculate();

            $message = $this->__('%1$s has been added to your wishlist. Click <a href="%2$s">here</a> to continue shopping.',
                $product->getName(), Mage::helper('core')->escapeUrl($referer));
            $session->addSuccess($message);
        } catch (Mage_Core_Exception $e) {
            $session->addError($this->__('An error occurred while adding item to wishlist: %s', $e->getMessage()));
        }
        catch (Exception $e) {
            $session->addError($this->__('An error occurred while adding item to wishlist.'));
        }

        //$this->_redirect('*', array('wishlist_id' => $wishlist->getId()));
        if ($this->getRequest()->isAjax()) {
            // $block = $this->getLayout()->getBlock('wishlist/links'); // @TODO, yes i know... this does not work, so using it manually...
            $block = new Schracklive_SchrackWishlist_Block_Links();
            $json = array('replaceHtml' => array('id' => 'li-link-wishlist',
                'html' => $block->toHtml()));
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
        } else
            $this->_redirect('*');
    }

    /**
     * add multiple products from a csv file whose 1st column contains the sku, 
     * and the 2nd column the qty
     * 
     * @throws Exception
     */
    public function addCsvAction() {
        $failCount = 0;
        if(isset($_FILES['csv']['name']) && $_FILES['csv']['name'] != '') {
            $tmpDir = sys_get_temp_dir();
            $fileName = $this->_storeUploadedFile('csv', $tmpDir, array('csv', 'txt'));
            $lines = file($fileName);
            unlink($fileName);
            $lines = Mage::helper('schrack/csv')->removeEmptyCsvLines($lines);

            if (count($lines) > Mage::getStoreConfig('sales/maximum_order/amount')) {
                // Too many items in cart: exceeded predefined limit! :
                $warningMessageText = $this->__('Too Many Items In Your File');
                Mage::getSingleton('wishlist/session')->addError($warningMessageText);
            } else if (count($lines) > 0) {
                $delim = Mage::helper('schrackcore/csv')->determineDelimiter($lines[0]);
                foreach ($lines as $line) {
                    try {
                        if ($this->_csvLineContainsData($line)) {
                            list($sku, $qty) = str_getcsv($line, $delim);
                                            
                            $this->_addProductToWishlistBySku($sku, $qty);
                        }
                    } catch (Exception $e) {
                        Mage::getSingleton('core/session')->addError($this->__('Could not read CSV line \'%s\'', $line));
                        $failCount++;
                    }
                }
            } else {
                Mage::getSingleton('core/session')->addError($this->__('CSV File was empty.'));
            }
        }
        
        if ($failCount) {
            $message = $this->__('%d product(s) could not be added to your wishlist.', $failCount); 
            Mage::getSingleton('wishlist/session')->addError($message);

        }
        $this->_redirect('schrackwishlist/index/index');
    }
    
    
    

     /**
     * Add wishlist item to shopping cart (do NOT remove from wishlist)
     *
     * If Product has required options - item removed from wishlist and redirect
     * to product view page with message about needed defined required options
     * 
     * @override
     *
     */
    public function cartAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*');
        } 
        $itemId = (int) $this->getRequest()->getParam('item');

        /* @var $item Mage_Wishlist_Model_Item */
        $item = Mage::getModel('wishlist/item')->load($itemId);
        if (!$item->getId()) {
            return $this->_redirect('*/*');
        }
        $wishlist = $this->_getWishlist($item->getWishlistId());
        if (!$wishlist) {
            return $this->_redirect('*/*');
        }

        // Set qty
        $qty = $this->getRequest()->getParam('qty');
        if (is_array($qty)) {
            if (isset($qty[$itemId])) {
                $qty = $qty[$itemId];
            } else {
                $qty = 1;
            }
        }
        $qty = $this->_processLocalizedQty($qty);
        if ($qty) {
            $item->setQty($qty);
            $item->save();
        }

        /* @var $session Mage_Wishlist_Model_Session */
        $session    = Mage::getSingleton('wishlist/session');
        $cart       = Mage::getSingleton('checkout/cart');

        $redirectUrl = Mage::getUrl('*/*');

        try {
            $options = Mage::getModel('wishlist/item_option')->getCollection()
                    ->addItemFilter(array($itemId));
            $item->setOptions($options->getOptionsByItem($itemId));
            
            $buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest(
                $this->getRequest()->getParams(),
                array('current_config' => $item->getBuyRequest())
            );

            $item->mergeBuyRequest($buyRequest);
            if ($item->addToCart($cart, false)) {
                $cart->save()->getQuote()->collectTotals();
            }

            $wishlist->save();
            Mage::helper('wishlist')->calculate();

            if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
                $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
            } else if ($this->_getRefererUrl()) {
                $redirectUrl = $this->_getRefererUrl();
            }
            Mage::helper('wishlist')->calculate();
            /* Start Nagarro : Added */
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($item->getProductId());
            $productName = Mage::helper('core')->escapeHtml($product->getName());
            $message = $this->__('%s was added to your shopping cart.', $productName);
            Mage::getSingleton('catalog/session')->addSuccess($message);
            /* End Nagarro : Added */
        } catch (Mage_Core_Exception $e) {
            if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                $session->addError($this->__('This product(s) is currently out of stock'));
            } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
            } else {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addException($e, $this->__('Cannot add item to shopping cart'));
        }

        Mage::helper('wishlist')->calculate();
        
        return $this->_redirectUrl($redirectUrl);
    }
    
    
    /**
     * Add all items from wishlist to shopping cart (do NOT remove from wishlist)
     *
     * @override
     */
    public function allcartAction()
    {
        if ($this->_isCheckFormKey && !$this->_validateFormKey()) {
            $this->_forward('noRoute');
            return;
        }
        
        $wishlist   = $this->_getWishlist();
        if (!$wishlist) {
            $this->_forward('noRoute');
            return ;
        }
        $isOwner    = $wishlist->isOwner(Mage::getSingleton('customer/session')->getCustomerId());

        $messages   = array();
        $addedItems = array();
        $notSalable = array();
        $hasOptions = array();

        $cart       = Mage::getSingleton('checkout/cart');
        $collection = $wishlist->getItemCollection()
                ->setVisibilityFilter();

        $qtysString = $this->getRequest()->getParam('qty');
        if (isset($qtysString)) {
            $qtys = array_filter(json_decode($qtysString), 'strlen');
        } // nagarro : Added
        foreach ($collection as $item) {
            /** @var Mage_Wishlist_Model_Item */
            try {
                $disableAddToCart = $item->getProduct()->getDisableAddToCart(); // Nagarro : Added
                $item->unsProduct();

                // Set qty
                if (isset($qtys[$item->getId()])) {
                    $qty = $this->_processLocalizedQty($qtys[$item->getId()]);
                    if ($qty) {
                        $item->setQty($qty);
                    }
                }                
                $item->getProduct()->setDisableAddToCart($disableAddToCart); // Nagarro : Added
                // Add to cart
                if ($item->addToCart($cart, false)) {
                    $addedItems[] = $item->getProduct();
                }

            } catch (Mage_Core_Exception $e) {
                if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                    $notSalable[] = $item;
                } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                    $hasOptions[] = $item;
                } else {
                    $messages[] = $this->__('%s for "%s".', trim($e->getMessage(), '.'), $item->getProduct()->getName());
                }
                
                $cartItem = $cart->getQuote()->getItemByProduct($item->getProduct());
                if ($cartItem) {
                    $cart->getQuote()->deleteItem($cartItem);
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $messages[] = Mage::helper('wishlist')->__('Cannot add the item to shopping cart.');
            }
        }

        if ($isOwner) {
            $indexUrl = Mage::helper('wishlist')->getListUrl($wishlist->getId());
        } else {
            $indexUrl = Mage::getUrl('wishlist/shared', array('code' => $wishlist->getSharingCode()));
        }
        if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
            $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
        } else if ($this->_getRefererUrl()) {
            $redirectUrl = $this->_getRefererUrl();
        } else {
            $redirectUrl = $indexUrl;
        }

        if ($notSalable) {
            $products = array();
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $products));
        }

        if ($hasOptions) {
            $products = array();
            foreach ($hasOptions as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__('Product(s) %s have required options. Each of them can be added to cart separately only.', join(', ', $products));
        }

        if ($messages) {
            $isMessageSole = (count($messages) == 1);
            if ($isMessageSole && count($hasOptions) == 1) {
                $item = $hasOptions[0];                
                $redirectUrl = $item->getProductUrl();
            } else {
                $wishlistSession = Mage::getSingleton('wishlist/session');
                foreach ($messages as $message) {
                    $wishlistSession->addError($message);
                }
                $redirectUrl = $indexUrl;
            }
        }

        if ($addedItems) {
            // save wishlist model for setting date of last update
            try {
                $wishlist->save();
            }
            catch (Exception $e) {
                Mage::getSingleton('wishlist/session')->addError($this->__('Cannot update wishlist'));
                $redirectUrl = $indexUrl;
            }

            $products = array();
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            Mage::getSingleton('checkout/session')->addSuccess(
                Mage::helper('wishlist')->__('%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $products))
            );
            
            // save cart and collect totals
            $cart->save()->getQuote()->collectTotals();
        }

        Mage::helper('wishlist')->calculate();

        $this->_redirectUrl($redirectUrl);
    }
    
    /**
     * Remove item
     */
    public function removeAction()
    {
        if (strlen($this->getRequest()->getParam('product'))) {
            $productId = $this->getRequest()->getParam('product');
            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product->getId() || !$product->isVisibleInCatalog()) {
                throw new Exception($this->__('Cannot specify product.'));
            }
            $wishlist = $this->_getWishlist();
            if (!$wishlist) {
                $this->_forward('noRoute');
                return ;
            }
            $item = $wishlist->getItemByProduct($product);
        } else {
            $id = (int) $this->getRequest()->getParam('item');
            $item = Mage::getModel('wishlist/item')->load($id);
        }        
        if (!$item->getId()) {
            return $this->norouteAction();
        } // Nagarro : Added
        $wishlist = $this->_getWishlist($item->getWishlistId());
        if (!$wishlist) {
            return $this->norouteAction();
        }        
        try {
            $item->delete();
            $wishlist->save();
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('An error occurred while deleting the item from wishlist: %s', $e->getMessage())
            );
        }
        catch(Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                $this->__('An error occurred while deleting the item from wishlist.')
            );
        }        

        Mage::helper('wishlist')->calculate();

        
        if ($this->getRequest()->isAjax()) {
            // $block = $this->getLayout()->getBlock('wishlist/links'); // @TODO, yes i know... this does not work, so using it manually...
            $block = new Schracklive_SchrackWishlist_Block_Links();
            $json = array('replaceHtml' => array('id' => 'li-link-wishlist',
                'html' => $block->toHtml()));
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
        } else
            $this->_redirect('*/*');
    }
    
    public function getLiLinkHtmlAction() {
        // $block = $this->getLayout()->getBlock('wishlist/links'); // @TODO, yes i know... this does not work, so using it manually...
        $block = new Schracklive_SchrackWishlist_Block_Links();
        echo $block->toHtml();
    }


    protected function _redirectNoAjax($url, $body = null) {
        if ($this->getRequest()->isAjax())
            $this->getResponse()->setBody ($body);
        else
            $this->_redirect($url);
    }
    
    protected function _redirectRefererNoAjax($body = null, $defaultUrl = null) {
        if ($this->getRequest()->isAjax())
            $this->getResponse()->setBody ($body);
        else
            $this->_redirectReferer($defaultUrl);        
    }    
    /**
	 * Removes all items from current list.
	 */
	public function emptyAction() {
        $wishlist = $this->_getWishlist();
		$wishlist->truncate();

	    return $this->_redirect('*/*');
    }

    /**
     * Retrieve wishlist object
     * @override
     *
     * @return Schracklive_SchrackWishlist_Model_Wishlist|false
     */
    protected function _getWishlist($wishlistId = null)
    {
        $wishlist = Mage::registry('wishlist');
        if ($wishlist) {
            return $wishlist;
        }

        try {
            if (!$wishlistId) {
                $wishlistId = $this->getRequest()->getParam('wishlist_id');
            }
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            /* @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlist = Mage::getModel('wishlist/wishlist');
            if ($wishlistId) {
                $wishlist->load($wishlistId);
            } else {
                $wishlist->loadByCustomer($customerId, true);
            }

            if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
                $wishlist = null;
                Mage::throwException(
                    Mage::helper('wishlist')->__("Requested wishlist doesn't exist")
                );
            }
            if(!Mage::register('wishlist'))
            Mage::register('wishlist', $wishlist);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('wishlist/session')->addError($e->getMessage());
            return false;
        } catch (Exception $e) {
            Mage::getSingleton('wishlist/session')->addException($e,
                Mage::helper('wishlist')->__('Wishlist could not be created.')
            );
            return false;
        }

        return $wishlist;
    }
    
    /**
     * 
     * @param type $product_helper
     * @param type $productId
     * @param type $qty
     */
    protected function _addProductToWishlistById( $product_helper, $productId, $qty ) {
        $product = $product_helper->load( $productId );
        if ($product) {
            $wishlist = $this->_getWishlist();
            $wishlist->addNewItem($product, array('qty' => $qty));
            $message = $this->__('%s was added to your wishlist.', Mage::helper('core')->escapeHtml($product->getName()));
            Mage::getSingleton('wishlist/session')->addSuccess($message);
        } else {
            Mage::getSingleton('core/session')->addError(str_replace('%s', $productId, $this->__('Product number %s not found.')));
        }
    }
    
    
    /**
     * 
     * @param string $sku
     * @param float $qty
     */
    protected function _addProductToWishlistBySku($sku, $qty) {
        $product_helper = Mage::getModel('schrackcatalog/product');
        $productId = $product_helper->getIdBySku($sku);

        if ($productId) {
            $this->_addProductToWishlistById($product_helper, $productId, $qty);
        }
        else
            throw new Exception('Unable to find product for sku ' . $sku);
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
        try {
            $path = $dirName . DS;  //desitnation directory     
            $fname = $_FILES[$inputName]['name']; //file name                        
            $uploader = new Varien_File_Uploader($inputName); //load class
            $uploader->setAllowedExtensions($allowedExtensions); //Allowed extension for file
            $uploader->setAllowCreateFolders(true); //for creating the directory if not exists
            $uploader->setAllowRenameFiles(true); //if true, uploaded file's name will be changed, if file with the same name already exists directory.
            $uploader->setFilesDispersion(false);
            $uploader->save($path, $fname); //save the file on the specified path
            return $path . $uploader->getUploadedFileName();
        } catch (Exception $e) {
            echo 'Error Message: ' . $e->getMessage();
        }
    }

    
    /**
     * heuristically try to determine whether the given text might be a csv line
     * we can use
     * 
     * @param string $line
     */
    protected function _csvLineContainsData($line) {
        return (preg_match('/^"?\w+[\w\-]*"?[,;\\t]"?\d+((.|,)\d+)?"?/', $line) === 1);
    }
}

?>

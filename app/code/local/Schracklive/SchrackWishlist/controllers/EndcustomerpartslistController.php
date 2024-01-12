<?php

class Schracklive_SchrackWishlist_EndcustomerPartslistController extends Schracklive_SchrackWishlist_Controller_Partslist_Abstract {
    private $_json;
    private $_ecplHelper;

    public function preDispatch() {
        parent::preDispatch();
    }
    public function _construct() {
        parent::_construct();
        $this->_json = Mage::getModel('schrackcore/jsonresponse');
        $this->_ecplHelper = Mage::helper('schrackwishlist/endcustomerpartslist');
    }

    public function indexAction() {
        $this->loadLayout();
        try {
            $customerId = $this->_getCustomerId();
            if ( isset($customerId) ) {
                $customer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer')->loadByCustomerId($customerId);
                $this->getLayout()->getBlock('head')->setTitle($this->__('Online Showroom %s', $customer->getCompanyName()));
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->renderLayout();
    }

    public function showroomAction() {
        $this->loadLayout();
        try {
            $customerId = $this->_getCustomerId();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->renderLayout();
    }

    public function dataAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function viewAction() {
        $this->loadLayout();

        $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
        $block->setTemplate('wishlist/endcustomerpartslist/view.phtml');
        $html = $block->toHtml();

        $this->_json->setHtml($html);
        $this->_json->encodeAndDie();
    }

    public function printAction() {
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
        $block->setTemplate('wishlist/endcustomerpartslist/print.phtml');
        $html = $block->toHtml();
        die($html);
    }

    public function getPartslistItemsCountAction() {
        $count = $this->_getPartslist()->getItemsCount();
        if ($count) {
            $html = "<span class=\"partslist-items-count\">($count)</span>";
        } else {
            $html = "";
        }
        $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $this->_json->setHtml($html);
        $this->_json->encodeAndDie();
    }

    public function productDetailAction() {
        $sku = $this->getRequest()->getParam('sku');
        $product = Mage::getModel('schrackcatalog/product')->loadBySku($sku);

        $id = $product->getId();


        $product2 = Mage::getModel('catalog/product')->load($id);


        Mage::register('product', $product2);

        // code copied from SchrackCatalog/ProductController
        //  @todo @refactor, put this in central location
        if (Mage::getSingleton('catalog/layer')) {
            $cc = Mage::getSingleton('catalog/layer')->getCurrentCategory();
            if (!$cc || intval($cc->getId()) === 2) {
                $catIds = $product->getCategoryIds();
                if (count($catIds) > 0) {
                    $cc = Mage::getModel('catalog/category')->load($catIds[0]);
                    if ($cc && $cc->getId()) {
                        Mage::getSingleton('catalog/layer')->setCurrentCategory($cc);
                    }
                }
            }
        }
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_productdetail');
        $block->setTemplate('wishlist/endcustomerpartslist/productdetail.phtml');

        $mediaBlock = $this->getLayout()->createBlock('catalog/product_view_media');
        $mediaBlock->setTemplate('catalog/product/view/media.phtml');
        $mediaBlock->setProduct($product);
        $block->setChild('media', $mediaBlock);
        $attributesBlock = $this->getLayout()->createBlock('catalog/product_view_attributes');
        $attributesBlock->setTemplate('catalog/product/view/attributes.phtml');
        $attributesBlock->setProduct($product);
        $block->setChild('attributes', $attributesBlock);

        $html = $block->toHtml();

        $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $this->_json->setHtml($html);
        $this->_json->encodeAndDie();
    }


    public function catalogsAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function addAction() {
        try {
            if (false && !$this->getRequest()->isAjax()) {
                throw new Exception('Wrong request method');
            }

            $partslist = $this->_getPartslist();

            if (!$partslist) {
                throw new Exception('Partslist not found.');
            }

            $sku = $this->getRequest()->getParam('sku');
            if ( strlen($sku) < 10 ) {
                $sku = str_pad($sku, 10, '-', STR_PAD_RIGHT);
            }
            $product = Mage::getModel('catalog/product')->loadBySku($sku);

            if ( !($product && $product->getId()) ) {
                throw new Exception('Cannot specify product.' . $sku);
            }

            $buyRequest = new Varien_Object(Mage::app()->getRequest()->getParams());

            $referrerUrl = urldecode(base64_decode($this->getRequest()->getParam('ref')));

            $result = $partslist->addNewItem($product, $buyRequest, true, $referrerUrl);

            if (is_string($result)) {
                Mage::throwException($result);
            }

            $partslist->save();

            Mage::dispatchEvent(
                'partslist_add_product',
                array(
                    'partslist' => $partslist,
                    'product' => $product,
                    'item' => $result
                )
            );

            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
            $message = $this->__('%1$s has been added to your partslist.', $product->getName());
            $this->_json->addMessage($message);
            $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
            $block->setTemplate('wishlist/endcustomerpartslist/view.phtml');
            $html = $block->toHtml();

            $this->_json->setHtml($html);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $this->_json->addError($this->__($e->getMessage()));
        }
        $this->_json->encodeAndDie();
    }

    public function removeAction() {
        try {
            if (!$this->getRequest()->isAjax() && false) {
                throw new Exception('Wrong request method');
            }

            $partslist = $this->_getPartslist();

            if (strlen($this->getRequest()->getParam('product'))) {
                $productId = $this->getRequest()->getParam('product');
                $product = Mage::getModel('catalog/product')->load($productId);
                if (!$product->getId() || !$product->isVisibleInCatalog()) {
                    throw new Exception($this->__('Cannot specify product.'));
                }
                $item = $partslist->getItemByProduct($product);
            } else {
                $id = (int) $this->getRequest()->getParam('item');
                $item = Mage::getModel('schrackwishlist/partslist_item')->load($id);
            }


            $item->delete();
            $partslist->save();


            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);

            $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
            $block->setTemplate('wishlist/endcustomerpartslist/view.phtml');
            $html = $block->toHtml();
            $this->_json->setHtml($html);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $this->_json->addError($this->__('An error occurred while deleting the item from partslist: %s', $e->getMessage()));
        }

        $this->_json->encodeAndDie();
    }

    public function truncateAction() {
        try {
            if (!$this->getRequest()->isAjax()) {
                throw new Exception('Wrong request method');
            }


            $partslist = $this->_getPartslist();
            $partslist->truncate();

            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);

            $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
            $block->setTemplate('wishlist/endcustomerpartslist/view.phtml');
            $html = $block->toHtml();
            $this->_json->setHtml($html);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $this->_json->addError($this->__('An error occurred while truncating the partslist: %s', $e->getMessage()));
        }

        $this->_json->encodeAndDie();
    }

    public function sendrequestAction() {
        try {
            if (!$this->getRequest()->isAjax()) {
                throw new Exception('Wrong request method');
            }

            $ecplCustomer = $this->_ecplHelper->getEndcustomerCustomer();
            $partslist = $this->_getPartslist();
            $params = $this->getRequest()->getParams();
            $partslist->sendRequestOfferEmails($ecplCustomer, $params);
            $partslist->setEcplValues($params);

            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);

            $block = $this->getLayout()->createBlock('wishlist/endcustomerpartslist_view');
            $block->setTemplate('wishlist/endcustomerpartslist/request_success.phtml');
            $html = $block->toHtml();
            $this->_json->setHtml($html);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $this->_json->addError($this->__('An error occurred while sending the request: %s', $e->getMessage()));
        }

        $this->_json->encodeAndDie();
    }


    /**
     * Update partslist item comments
     * @Override
     */
    public function updateAction()
    {
        $post = $this->getRequest()->getPost();
        if( $post && (isset($post['qty']) && is_array($post['qty'])) ) {
            $partslist = $this->_getPartslist();
            $updatedItems = 0;

            foreach ($post['qty'] as $itemId => $qty) {
                $item = Mage::getModel('schrackwishlist/partslist_item')->load($itemId);
                if ($item->getPartslistId() != $partslist->getId()) {
                    continue;
                }


                $qty = null;
                if (isset($post['qty'][$itemId])) {
                    $qty = $this->_processLocalizedQty($post['qty'][$itemId]);
                }
                if (is_null($qty)) {
                    $qty = $item->getQty();
                    if (!$qty) {
                        $qty = 1;
                    }
                } elseif (0 === $qty) {
                    try {
                        $item->delete();
                    } catch (Exception $e) {
                        Mage::logException($e);
                        Mage::getSingleton('customer/session')->addError(
                            $this->__('Can\'t delete item from partslist')
                        );
                    }
                } elseif ('' === $qty) {
                    $qty = null;
                }

                // Check that we need to save
                if ( $item->getQty() == $qty ) {
                    continue;
                }
                try {
                    $item->setQty($qty)->save();
                    $updatedItems++;
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError(
                        $this->__('Can\'t save description %s', Mage::helper('core')->escapeHtml($description))
                    );
                }
            }

            // save partslist model for setting date of last update
            if ($updatedItems) {
                try {
                    $partslist->save();
                    Mage::helper('schrackwishlist/partslist')->calculate();
                }
                catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError($this->__('Can\'t update partslist'));
                }
            }
        }
        return $this->_redirect('wishlist/endcustomerpartslist/showroom', array('idkey' => $this->_getSession()->getEndcustomerCustomerId()));
    }

    public function editCustomerAction() {
        $this->loadLayout();
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (!($customer && $customer->getId())) {
                throw new Exception('Must be logged in.');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            return $this->_redirect('customer/account');
        }
        $this->renderLayout();
    }

    public function editCustomerPostAction() {
        try {
            if ( !$this->getRequest()->isPost() ) {
                throw new Exception('Wrong method.');
            }

            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ( !($customer && $customer->getId()) ) {
                throw new Exception('Must be logged in.');
            }

            $ecplCustomer = Mage::getModel('schrackwishlist/endcustomerpartslist_customer');
            $ecplCustomer->load($customer->getId());
            if ( !$ecplCustomer ) {
                throw new Exception('Customer not found.');
            }

            $idKey = $this->getRequest()->getParam('idkey', null);
            if ( $idKey === null ) {
                $idKey = $ecplCustomer->createIdKey();
            }

            $ecplCustomer->setIdKey($idKey);
            $ecplCustomer->setCustomerId($customer->getId());
            $ecplCustomer->setCompanyName($this->getRequest()->getParam('companyname'));
            $ecplCustomer->setAddress1($this->getRequest()->getParam('address1'));
            $ecplCustomer->setAddress2($this->getRequest()->getParam('address2'));
            $ecplCustomer->setAddress3($this->getRequest()->getParam('address3'));
            $ecplCustomer->setPhone($this->getRequest()->getParam('phone'));
            $ecplCustomer->setFax($this->getRequest()->getParam('fax'));
            $ecplCustomer->setEmail($this->getRequest()->getParam('email'));
            $ecplCustomer->setHomepage($this->getRequest()->getParam('homepage'));
            $ecplCustomer->setBannerUrl($this->getRequest()->getParam('banner_url'));
            $ecplCustomer->setWelcomeUrl($this->getRequest()->getParam('welcome_url'));
            $ecplCustomer->save();

            Mage::getSingleton('core/session')->addSuccess($this->__('The data has been saved.'));

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
        }

        return $this->_redirect('wishlist/endcustomerpartslist/editCustomer');
    }

    public function sendemailAction() {
        if (!$this->getRequest()->isAjax()) {
            die('wrong method');
        }

        $ecplCustomer = $this->_ecplHelper->getEndcustomerCustomer();
        $partslist = $this->_getPartslist();
        $partslist->sendShareEmail($ecplCustomer, $this->getRequest()->getParams());
        $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $this->_json->encodeAndDie();
    }

    private function _getPartslist() {
        return $this->_ecplHelper->getPartslist();
    }

    /**
     * get the customer id from the request param email
     * @return null
     * @throws Exception
     */
    private function _getCustomerId() {
        return $this->_ecplHelper->getCustomerId();
    }

    private function _getSession() {
        return Mage::getSingleton('core/session');
    }
} 
<?php
require_once 'Mage/Catalog/controllers/CategoryController.php';

class Schracklive_SchrackCatalog_CategoryController extends Mage_Catalog_CategoryController {
    public function viewAction() {
        $fq = $this->getRequest()->getParam('fq');
        $list = $this->getRequest()->getParam('list');
        if ( $this->getRequest()->isAjax() ) {
            $this->_initCatagory();

            if ( isset($list) ) {
                $this->loadLayout();

                $block = $this->getLayout()->createBlock('catalog/product_list');
                $block->setTemplate('catalog/product/list/table.phtml');

                $toolbarBlock = $this->getLayout()->createBlock('catalog/product_list_toolbar', 'product_list_toolbar');
                $toolbarBlock->setTemplate('catalog/product/list/toolbar.phtml');

                $pagerBlock = $this->getLayout()->createBlock('page/html_pager', 'product_list_toolbar_pager');
                $pagerBlock->setTemplate('catalog/product/list/pager.phtml');

                $toolbarBlock->append($pagerBlock);
                $block->append($toolbarBlock);

                $block->setToolbarBlockName('product_list_toolbar');


                $html = $block->toHtml();

                $this->_json = Mage::getModel('schrackcore/jsonresponse');
                $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
                $this->_json->setHtml($html);
                $this->_json->encodeAndDie();

            } else {
                $this->loadLayout();

                $block = $this->getLayout()->createBlock('solrsearch/form_search');
                $block->setTemplate('solrsearch/search.phtml');

                $html = $block->toHtml();

                $this->_json = Mage::getModel('schrackcore/jsonresponse');
                $this->_json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
                $this->_json->setHtml($html);
                $this->_json->encodeAndDie();
            }
        } else {
            if ($category = $this->_initCatagory()) {
                // force login for promotions:
                if ( $category->isPromotionProductsCategory() && ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
                    Mage::app()->getFrontController()->getResponse()->setRedirect(
                        Mage::getUrl('customer/account/login', array('referer' => Mage::helper('core')->urlEncode(
                            Mage::getUrl('', array('_current' => true,'_use_rewrite' => true))
                        )))
                    );
                    return;
                }

                $design = Mage::getSingleton('catalog/design');
                $settings = $design->getDesignSettings($category);

                // apply custom design
                if ($settings->getCustomDesign()) {
                    $design->applyCustomDesign($settings->getCustomDesign());
                }

                Mage::getSingleton('catalog/session')->setLastViewedCategoryId($category->getId());

                $update = $this->getLayout()->getUpdate();
                if ( $category->isPromotionProductsCategory() ) {
                    $removeInstruction = "<remove name=\"catalog.vertnav\"/>";
                } else {
                    $removeInstruction = "<remove name=\"customer_account_menu\"/>";
                }
                $update->addUpdate($removeInstruction);

                $update->addHandle('default');

                if (!$category->hasChildren()) {
                    $update->addHandle('catalog_category_layered_nochildren');
                }

                $this->addActionLayoutHandles();
                $update->addHandle($category->getLayoutUpdateHandle());
                $update->addHandle('CATEGORY_' . $category->getId());
                $this->loadLayoutUpdates();

                // apply custom layout update once layout is loaded
                if ($layoutUpdates = $settings->getLayoutUpdates()) {
                    if (is_array($layoutUpdates)) {
                        foreach($layoutUpdates as $layoutUpdate) {
                            $update->addUpdate($layoutUpdate);
                        }
                    }
                }

                $this->generateLayoutXml()->generateLayoutBlocks();
                // apply custom layout (page) template once the blocks are generated
                if ($settings->getPageLayout()) {
                    $this->getLayout()->helper('page/layout')->applyTemplate($settings->getPageLayout());
                }

                if ($root = $this->getLayout()->getBlock('root')) {
                    $root->addBodyClass('categorypath-' . $category->getUrlPath())
                        ->addBodyClass('category-' . $category->getUrlKey());
                }

                $this->_initLayoutMessages('catalog/session');
                $this->_initLayoutMessages('checkout/session');
                $this->renderLayout();
            }
            elseif (!$this->getResponse()->isRedirect()) {
                $this->_forward('noRoute');
            }


        }
    }
}

?>

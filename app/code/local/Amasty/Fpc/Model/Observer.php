<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpc
 */
class Amasty_Fpc_Model_Observer
{
    protected $_showBlockNames = false;
    protected $_showBlockTemplates = false;
    protected $_blockHtmlCache = array();
    protected $_ajaxBlocks = null;

    public function __construct()
    {
        try {
            if (
                Mage::app()->useCache('amfpc')
                &&
                Mage::getStoreConfig('amfpc/debug/block_info')
                &&
                Mage::getSingleton('amfpc/fpc_front')->allowedDebugInfo()
                &&
                !Mage::app()->getStore()->isAdmin()
            ) {
                $this->_showBlockNames = true;
                Mage::app()->getCacheInstance()->banUse('block_html');
            }

            $this->_showBlockTemplates = Mage::getStoreConfig('amfpc/debug/block_templates');
        } catch (Mage_Core_Model_Store_Exception $e) // Stores aren't initialized
        {
            $this->_showBlockNames = $this->_showBlockTemplates = false;
        }
    }

    public function actionPredispatch($observer)
    {
        if (Mage::app()->getStore()->isAdmin())
            return;

        if ($page = Mage::registry('amfpc_page')) {
            $page = Mage::helper('amfpc')->replaceFormKey($page);

            /** @var Amasty_Fpc_Model_Fpc_Front $front */
            $front = Mage::getSingleton('amfpc/fpc_front');
            $front->addBlockInfo($page, 'Session Initialized');

            $response = Mage::app()->getResponse();
            $request = Mage::app()->getRequest();

            $this->setResponse(
                $response,
                $page,
                Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_HIT_SESSION
            );

            if (0 === strpos($request->getPathInfo(), 'catalog/product/view')) {
                Mage::getModel('reports/product_index_viewed')
                    ->setProductId($request->getParam('id'))
                    ->save()
                    ->calculate();
            }

            Mage::app()->dispatchEvent(
                'controller_action_postdispatch',
                array('controller_action' => $observer->getData('controller_action'))
            );

            $response->sendHeaders();
            $response->outputBody();

            Mage::helper('ambase/utils')->_exit();
        }

        $request = $observer->getData('controller_action')->getRequest();
        Mage::getSingleton('amfpc/fpc')->validateBlocks($request);
    }

    public function afterToHtml($observer)
    {
        if (!Mage::app()->useCache('amfpc'))
            return;

        if (Mage::app()->getRequest()->isAjax() && $this->_ajaxBlocks === null)
            return;

        $block = $observer->getBlock();

        $fpc = Mage::getSingleton('amfpc/fpc');

        $discardedBlocks = $fpc->getDiscardedBlocks();
        $discardedAgents = false;

        foreach ($discardedBlocks as $class => $info) {
            if ($block instanceof $class) {
                if ($info['matched']) {
                    $fpc->setReadonly(true);
                    return;
                }
                $discardedAgents = $info['agents'];
            }
        }

        if ($this->_showBlockNames == false) {
            if (Mage::getStoreConfig('amfpc/general/dynamic_blocks')) {
                /** @var Amasty_Fpc_Model_Config $config */
                $config = Mage::getSingleton('amfpc/config');

                if ($discardedAgents) {
                    $transport = $observer->getTransport();

                    $html = $transport->getHtml();
                    $html = "<!--AMFPC_DISCARD[$discardedAgents]-->$html<!--AMFPC_DISCARD-->";
                    $transport->setHtml($html);
                }

                if ($config->blockIsDynamic($block, $isAjax, $tags, $children)) {
                    $name = $block->getNameInLayout();

                    if (in_array($name, array('global_messages', 'messages')) && $block->getData('amfpc_wrapped'))
                        return;

                    if ($name == 'google_analytics' && $block->getOrderIds())
                        return;

                    $transport = $observer->getTransport();

                    $html = $transport->getHtml();

                    $fpc->saveBlockCache($name, $html, $tags);

                    if (($this->_ajaxBlocks !== null) && array_key_exists($name, $this->_ajaxBlocks)) {
                        $this->_ajaxBlocks[$name] = $html;
                    }

                    if (!$block->getData('amfpc_wrapped')) {
                        $tag = ($isAjax ? 'amfpc_ajax' : 'amfpc');

                        $html = "<$tag name=\"$name\">$html</$tag>";

                        $block->setData('amfpc_wrapped', true);
                    }

                    $transport->setHtml($html);
                } else if (!empty($children)) {
                    $transport = $observer->getTransport();

                    $html = $transport->getHtml();

                    foreach ($children as $childName => $tags) {
                        if (preg_match(
                            '#<amfpc\s*name="' . preg_quote($childName) . '"\s*>(.*?)</amfpc>#s',
                            $html,
                            $matches
                        )
                        ) {
                            $fpc->saveBlockCache($childName, $matches[1], $tags);
                        }
                    }
                }
            }
        } else {
            if ($block instanceof Mage_Core_Block_Template || $block instanceof Mage_Cms_Block_Block) {
                $transport = $observer->getTransport();

                $html = $transport->getHtml();

                if ($this->_showBlockTemplates) {
                    if ($block instanceof Mage_Core_Block_Template) {
                        $template = $block->getTemplateFile();
                    } else {
                        $template = get_class($block);
                    }
                    $templateHint
                        = "<div class=\"amfpc-template-info\">$template</div>";
                } else
                    $templateHint = '';

                $html = <<<HTML
<div class="amfpc-block-info">
    <div class="amfpc-block-handle"
        onmouseover="$(this).parentNode.addClassName('active')"
        onmouseout="$(this).parentNode.removeClassName('active')"
    >{$block->getNameInLayout()}</div>
    $templateHint
    $html
</div>
HTML;

                $transport->setHtml($html);
            }
        }

    }

    public function layoutRenderBefore()
    {
        $request = Mage::app()->getRequest();

        if ($dynamicBlocks = Mage::registry('amfpc_blocks')) {
            $layout = Mage::app()->getLayout();
            $page = $dynamicBlocks['page'];

            Mage::app()->setUseSessionVar(false);

            /** @var Amasty_Fpc_Model_Fpc_Front $front */
            $front = Mage::getSingleton('amfpc/fpc_front');

            foreach ($dynamicBlocks['blocks'] as $name) {
                $blockConfig = Mage::app()->getConfig()->getNode('global/amfpc/blocks/' . $name);
                $parent = (string)$blockConfig['parent'];

                $realName = $parent ? $parent : $name;

                if (!isset($this->_blockHtmlCache[$realName])) {
                    $block = $layout->getBlock($realName);
                    if ($block) {
                        $this->_blockHtmlCache[$realName] = $block->toHtml();
                    }
                }

                if ($parent && isset($this->_blockHtmlCache[$realName])) {
                    if (preg_match(
                        '#<amfpc\s*name="' . preg_quote($name) . '"\s*>(.*?)</amfpc>#s',
                        $this->_blockHtmlCache[$realName],
                        $matches
                    )
                    ) {
                        $this->_blockHtmlCache[$name] = $matches[1];
                    }
                }

                if (isset($this->_blockHtmlCache[$name])) {
                    $blockHtml = $this->_blockHtmlCache[$name];
                    $blockHtml = preg_replace('/<amfpc[^>]*>/', '', $blockHtml);
                    $blockHtml = str_replace('</amfpc>', '', $blockHtml);
                    $blockHtml = str_replace('</amfpc_ajax>', '', $blockHtml);

                    $front->addBlockInfo($blockHtml, $name . ($parent ? "[$parent]" : '') . ' (refresh)');

                    if (preg_match_all(
                        '#<amfpc(_ajax)? name="' . preg_quote($name) . '" />#',
                        $page,
                        $matches,
                        PREG_OFFSET_CAPTURE)
                    ) {
                        for ($i = sizeof($matches[0]) - 1; $i >= 0; $i--) {
                            $page = substr_replace($page, $blockHtml, $matches[0][$i][1], strlen($matches[0][$i][0]));
                        }
                    }
                }
            }

            $front->addBlockInfo($page, 'Late page load');

            if (Mage::registry('amfpc_new_session')) {
                $page = Mage::helper('amfpc')->replaceFormKey($page);
            }

            $response = Mage::app()->getResponse();

            $this->setResponse($response, $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_HIT_UPDATE);

            Mage::app()->dispatchEvent(
                'controller_action_postdispatch',
                array('controller_action' => Mage::app()->getFrontController()->getAction())
            );

            $response->sendHeaders();
            $response->outputBody();

            Mage::helper('ambase/utils')->_exit();
        } else if ($request->isAjax()) {
            $blocks = $request->getParam('amfpc_ajax_blocks');
            if ($blocks) {
                $blocks = explode(',', $blocks);

                $cmsAjaxBlocks = (bool)(string)Mage::app()->getConfig()->getNode('global/amfpc/cms_ajax_blocks');

                if ($cmsAjaxBlocks) {
                    $this->_ajaxBlocks = array_fill_keys($blocks, null);
                } else {

                    /** @var Amasty_Fpc_Model_Fpc_Front $front */
                    $front = Mage::getSingleton('amfpc/fpc_front');

                    Mage::app()->setUseSessionVar(false);

                    $result = array();
                    $layout = Mage::app()->getLayout();
                    foreach ($blocks as $name) {
                        $block = $layout->getBlock($name);
                        if ($block) {
                            $content = Mage::getSingleton('core/url')->sessionUrlVar($block->toHtml());

                            $front->addBlockInfo($content, $name . ' (ajax)');
                            $result[$name] = $content;
                        }
                    }

                    $blocksJson = Mage::helper('core')->jsonEncode($result);

                    Mage::app()->getResponse()->setBody($blocksJson)->sendResponse();
                    Mage::helper('ambase/utils')->_exit();
                }
            }
        }
    }

    protected function _canPreserve()
    {
        if (!Mage::registry('amfpc_preserve'))
            return false;

        if (Mage::app()->getResponse()->getHttpResponseCode() != 200)
            return false;

        foreach (Mage::app()->getResponse()->getHeaders() as $header) {
            if ($header['name'] == 'Status') {
                if (substr($header['value'], 0, 3) !== '200')
                    return false;
                else
                    break;
            }
        }

        if ($this->_showBlockNames)
            return false;

        if ($layout = Mage::app()->getLayout()) {
            if ($block = $layout->getBlock('messages'))
                if ($block->getMessageCollection()->count() > 0)
                    return false;
            if ($block = $layout->getBlock('global_messages'))
                if ($block->getMessageCollection()->count() > 0)
                    return false;
        } else
            return false;

        return true;
    }

    public function setResponse($response, $html, $status)
    {
        /** @var Amasty_Fpc_Model_Fpc_Front $front */
        $front = Mage::getSingleton('amfpc/fpc_front');

        $html = preg_replace(
            '#(<amfpc[^>]*?>|</amfpc(_ajax)?>)#s',
            '',
            $html
        );

        $front->addLoadTimeInfo($html, $status);

        $response->setBody($html);
    }

    public function onHttpResponseSendBefore($observer)
    {
        if (Mage::app()->getRequest()->getModuleName() == 'api')
            return;

        if (!Mage::app()->useCache('amfpc'))
            return;

        if (Mage::app()->getStore()->isAdmin())
            return;

        if (Mage::app()->getRequest()->isAjax()
            && $this->_ajaxBlocks === null
            && !Mage::getSingleton('amfpc/config')->canSaveAjax()
        )
            return;

        // No modifications in response till here

        Mage::getSingleton('core/session')->getFormKey(); // Init form key

        $page = $observer->getResponse()->getBody();

        if ($ignoreStatus = Mage::registry('amfpc_ignored')) {
            $this->setResponse($observer->getResponse(), $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_IGNORE_PARAM);

            return;
        }

        $tags = Mage::getSingleton('amfpc/config')->matchRoute(Mage::app()->getRequest());

        if (!$tags && !Mage::getStoreConfig('amfpc/pages/all')) {
            $this->setResponse($observer->getResponse(), $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_NEVER_CACHE);

            return;
        }

        if (Mage::helper('amfpc')->inIgnoreList() || Mage::registry('amfpc_ignorelist')) {
            $this->setResponse($observer->getResponse(), $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_IGNORE);
            return;
        }


        if ($this->_canPreserve()) {
            $fpc = Mage::getSingleton('amfpc/fpc');

            if ($fpc->matchRoute(Mage::app()->getRequest(), 'amshopby/index/index')) {
                $rootCategory = Mage::helper('amshopby')->getCurrentCategory();
                $tags [] = 'catalog_category_' . $rootCategory->getId();
            }

            $tags[] = Amasty_Fpc_Model_Fpc::CACHE_TAG;
            $tags[] = Mage_Core_Block_Abstract::CACHE_GROUP;
            $tags[] = Mage::getSingleton('amfpc/fpc_front')->getUrlTag();

            $lifetime = +Mage::getStoreConfig('amfpc/general/page_lifetime');
            $lifetime *= 3600;

            $tags = array_filter($tags);
            Mage::getSingleton('amfpc/fpc')->savePage($page, $tags, $lifetime);
            Mage::getSingleton('amfpc/fpc_front')->incrementHits();
        }

        if (Mage::registry('amfpc_cms_blocks')) {
            $this->setResponse($observer->getResponse(), $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_CMS_UPDATE);
            return;
        }

        if (is_array($this->_ajaxBlocks)) {
            $result = array();
            $front = Mage::getSingleton('amfpc/fpc_front');
            foreach ($this->_ajaxBlocks as $name => $content) {
                /** @var Amasty_Fpc_Model_Fpc_Front $front */
                $front->addBlockInfo($content, $name . ' (ajax)');
                $result[$name] = $content;
            }

            $blocksJson = Mage::helper('core')->jsonEncode($result);

            Mage::app()->getResponse()->setBody($blocksJson);
            return;
        }

        $this->setResponse($observer->getResponse(), $page, Amasty_Fpc_Model_Fpc_Front::PAGE_LOAD_MISS);
    }

    public function cleanCache(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::getSingleton('amfpc/fpc')->getFrontend()->clean(Zend_Cache::CLEANING_MODE_OLD);
    }

    public function flushOutOfStockCache($observer)
    {
        $item = $observer->getItem();

        if ($item->getStockStatusChangedAutomatically()) {
            $tags = array('catalog_product_' . $item->getProductId(), 'catalog_product');
            Mage::dispatchEvent('application_clean_cache', array('tags' => $tags));
        }

        return $item;
    }

    public function onQuoteSubmitSuccess($observer)
    {
        if (Mage::getStoreConfig('amfpc/product/flush_on_purchase')) {
            $this->_cleanItemsCache(
                $observer->getQuote()->getAllItems()
            );
        }
    }

    public function onOrderCancelAfter($observer)
    {
        if (Mage::getStoreConfig('amfpc/product/flush_on_purchase')) {
            $this->_cleanItemsCache(
                $observer->getOrder()->getAllItems()
            );
        }
    }

    protected function _cleanItemsCache($items)
    {
        $tags = array();

        foreach ($items as $item) {
            $tags [] = 'catalog_product_' . $item->getProductId();
            $children = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $tags [] = 'catalog_product_' . $childItem->getProductId();
                }
            }
        }

        if (!empty($tags))
            Mage::dispatchEvent('application_clean_cache', array('tags' => $tags));
    }

    public function onApplicationCleanCache($observer)
    {
        if (!Mage::helper('amfpc')->storeInitialized())
            return;

        $tags = $observer->getTags();

        /**
         * @var Amasty_Fpc_Model_Fpc $fpc
         */
        $fpc = Mage::getSingleton('amfpc/fpc');

        if (!empty($tags)) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }

            $productIds = array();
            foreach ($tags as $tag) {
                if (preg_match('/^catalog_product_(?P<id>\d+)$/i', $tag, $matches)) {
                    $productIds[] = +$matches['id'];
                }
            }

            $flushType = Mage::getStoreConfig('amfpc/product/flush_type');

            if ($flushType == Amasty_Fpc_Model_Config_Source_FlushType::FLUSH_ASSOCIATED) {
                $additionalTags = $fpc->getProductsAdditionalTags($productIds);

                if (!empty($additionalTags)) {
                    $tags = array_merge($tags, $additionalTags);
                }
            } else if ($flushType == Amasty_Fpc_Model_Config_Source_FlushType::FLUSH_PRODUCT_ONLY) {
                if (in_array(Mage_Catalog_Model_Product::CACHE_TAG, $tags)) {
                    // Keep category cache
                    $catTagPrefix = Mage_Catalog_Model_Category::CACHE_TAG . '_';
                    foreach ($tags as $tagKey => $tag) {
                        if (strpos($tag, $catTagPrefix) === 0) {
                            unset($tags[$tagKey]);
                        }
                    }
                }
            }
        }

        $fpc->clean($tags);
    }

    public function onModelSaveBefore($observer)
    {
        $object = $observer->getObject();

        if (class_exists('Mirasvit_AsyncCache_Model_Asynccache', false)
            && $object instanceof Mirasvit_AsyncCache_Model_Asynccache
        ) {
            if ($object->getData('status') == Mirasvit_AsyncCache_Model_Asynccache::STATUS_SUCCESS &&
                $object->getOrigData('status') != Mirasvit_AsyncCache_Model_Asynccache::STATUS_SUCCESS
            ) {
                Mage::getSingleton('amfpc/fpc')->getFrontend()->clean($object->getMode(), $object->getTagArray(), true);
            }
        }
    }

    public function onCustomerLogin($observer)
    {
        $customer = $observer->getCustomer();

        Mage::getSingleton('customer/session')
            ->setCustomerGroupId($customer->getGroupId());
    }

    public function onReviewSaveAfter($observer)
    {
        $review = $observer->getObject();

        $productEntityId = $review->getEntityIdByCode(Mage_Review_Model_Review::ENTITY_PRODUCT_CODE);

        if ($review->getEntityId() == $productEntityId) {
            if (Mage::app()->getStore()->isAdmin()
                || $review->getStatusId() == Mage_Review_Model_Review::STATUS_APPROVED
            ) {

                Mage::getSingleton('amfpc/fpc')->clean(
                    'catalog_product_' . $review->getEntityPkValue()
                );
            }
        }
    }

    public function onQuoteSaveAfter($observer)
    {
        Mage::helper('amfpc')->invalidateBlocksWithAttribute('cart');
    }

    public function onCustomerLoginLogout($observer)
    {
        Mage::helper('amfpc')->invalidateBlocksWithAttribute(array('customer', 'cart'));
    }

    public function onCategorySaveAfter($observer)
    {
        if (Mage::getStoreConfig('amfpc/category/flush_all'))
            Mage::getSingleton('amfpc/fpc')->flush();
    }

    public function onAdminhtmlInitSystemConfig($observer)
    {
        $backend = Mage::getSingleton('amfpc/fpc')->getBackendType();

        if (FALSE === stripos($backend, 'database')) {
            $observer->getConfig()->setNode(
                'sections/amfpc/groups/compression/fields/max_size', false, true
            );
        }
    }

    public function onCrawlerProcessLink($observer)
    {
        /**
         * @var Amasty_Fpc_Model_Fpc $fpc
         */
        $fpc = Mage::getSingleton('amfpc/fpc');
        $data = $observer->getData('data');
        $key = $fpc->getCacheKey($data);
        $meta = $fpc->getFrontend()->getMetadatas($key);

        if ($meta) {
            $timeRemains = $meta['expire'] - time();

            if ($timeRemains > 0) {
                $action = Mage::getStoreConfig('amfpc/regen/crawler_action');

                $data->setData('hasCache', true);

                if (Amasty_Fpc_Model_Config_Source_CrawlerAction::ACTION_REGENERATE == $action) {
                    $lifetime = Mage::getStoreConfig('amfpc/general/page_lifetime');
                    $lifetime *= 3600;

                    $fpc->getFrontend()->touch($key, $lifetime - $timeRemains);
                } else if (Amasty_Fpc_Model_Config_Source_CrawlerAction::ACTION_REFRESH == $action) {
                    $fpc->getFrontend()->remove($key);
                    $data->setData('hasCache', false);
                }
            }
        }
    }

    public function onRefreshType($observer)
    {
        if ($observer->getType() == 'amfpc') {
            Mage::getSingleton('amfpc/fpc')->flush();
        }
    }

    public function onMassRefreshAction($observer)
    {
        $types = Mage::app()->getRequest()->getParam('types');

        if (in_array('amfpc', $types)) {
            Mage::getSingleton('amfpc/fpc')->flush();
        }
    }

    public function onEndProcessCatalogProductSave($observer)
    {
        if ($product = Mage::registry('current_product')) {
            $product->cleanCache();
        }
    }

    public function updateAttributesOnMassAction($observer)
    {
        $productIds = $observer->getProductIds();
        Mage::helper('amfpc')->collectTags($productIds);
    }

    public function updateAttributesOnMassStockUpdate($observer)
    {
        $changedProductIds = $observer->getProducts();
        Mage::helper('amfpc')->collectTags($changedProductIds);

    }

}

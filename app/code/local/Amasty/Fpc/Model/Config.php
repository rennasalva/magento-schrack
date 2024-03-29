<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpc
 */

class Amasty_Fpc_Model_Config
{
    protected $_config = null;

    public function getConfig()
    {

        if (!$this->_config)
            $this->_config = Mage::app()->getConfig()?->getNode('global/amfpc')?->asArray();

        if (Mage::getSingleton('amfpc/fpc_front')->getDbConfig('web/cookie/cookie_restriction'))
            $this->_config['blocks'][self::getCookieNoticeBlockName()] = array();

        return $this->_config;
    }

    public function matchRoute(Mage_Core_Controller_Request_Http $request)
    {
        $fpc = Mage::getSingleton('amfpc/fpc');

        $config = $this->getConfig();
        $config = $config['routes'];

        foreach ($config as $route)
        {
            if ($fpc->matchRoute($request, $route['path']))
            {
                $tags = explode(',', $route['tags']);

                foreach ($tags as &$tag)
                {
                    if (preg_match('/\{(\w+)\}/', $tag, $matches))
                    {
                        $paramId = $matches[1];
                        if ($param = Mage::app()->getRequest()->getParam($paramId))
                        {
                            $tag = str_replace($matches[0], $param, $tag);
                        }
                    }
                }

                if ($request->getRouteName() === 'amlanding') {
                    $productsCacheTags = array();
                    $layer = Mage::registry('current_layer');
                    $products = $layer->getProductCollection();

                    foreach ($products as $product) {
                        $productsCacheTags = array_merge($productsCacheTags, $product->getCacheIdTags());
                    }
                    $tags = array_merge($tags, $productsCacheTags);
                }

                return $tags;
            }
        }
    }

    public function blockIsDynamic($block, &$isAjax, &$tags, &$children)
    {
        $children = array();

        $config = $this->getConfig();

        $name = $block->getNameInLayout();

        if (isset($config['ajax_blocks'][$name]))
        {
            $isAjax = true;
            return true;
        }

        if (isset($config['blocks'][$name]))
        {
            if (isset($config['blocks'][$name]['tags']))
                $tags = explode(',', $config['blocks'][$name]['tags']);

            return true;
        }

        foreach ($config['blocks'] as $id => $block)
        {
            if (isset($block['@']['parent']) && $block['@']['parent'] == $name)
            {
                $tags = isset($block['tags']) ? explode(',', $block['tags']) : array();

                $children[$id] = $tags;
            }
        }

        return false;
    }

    public static function getCookieNoticeBlockName()
    {
        $name = Mage::app()
            ->getConfig()
            ->getNode('global/amfpc/cookie_notice_block');

        return (string)$name;
    }

    public function canSaveAjax()
    {
        $pattern = (string)Mage::app()
            ->getConfig()
            ->getNode('global/amfpc/allowed_ajax_pattern');

        if (!$pattern)
            return false;

        $request = Mage::app()->getRequest();

        $internalUri = implode('/', array(
            $request->getModuleName(),
            $request->getControllerName(),
            $request->getActionName()
        ));

        $canSave = preg_match("#$pattern#", $internalUri);

        return $canSave;
    }
}
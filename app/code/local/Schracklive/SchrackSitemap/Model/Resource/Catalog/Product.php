<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sitemap
 * @copyright  Copyright (c) 2006 - 2018 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Sitemap resource product collection model
 *
 * @category    Mage
 * @package     Mage_Sitemap
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackSitemap_Model_Resource_Catalog_Product extends Mage_Sitemap_Model_Resource_Catalog_Product
{
    /**
     * Get product collection array
     *
     * @param int $storeId
     * @return array
     */
    public function getCollection($storeId)
    {
        /* @var $store Mage_Core_Model_Store */
        $store = Mage::app()->getStore($storeId);
        if (!$store) {
            return false;
        }

        $this->_select = $this->_getWriteAdapter()->select()
            ->from(array('main_table' => $this->getMainTable()), array($this->getIdFieldName()))
            ->join(
                array('w' => $this->getTable('catalog/product_website')),
                'main_table.entity_id = w.product_id',
                array()
            )
            ->where('w.website_id=?', $store->getWebsiteId());

        $storeId = (int)$store->getId();

        /** @var $urlRewrite Mage_Catalog_Helper_Product_Url_Rewrite_Interface */
        $urlRewrite = $this->_factory->getProductUrlRewriteHelper();
        $urlRewrite->joinTableToSelect($this->_select, $storeId);

        $this->_addFilter($storeId, 'visibility',
            Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds(), 'in'
        );
        $this->_addFilter($storeId, 'status',
            Mage::getSingleton('catalog/product_status')->getVisibleStatusIds(), 'in'
        );
        $this->_addFilter($storeId, 'schrack_sts_statuslocal', array('std','wirdausl','istausl','tot','gesperrt','unsaleable'), 'in');

        return $this->_loadEntities();
    }

}

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
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006 - 2018 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product url rewrite helper
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackSitemap_Helper_Catalog_Product_Url_Rewrite extends Mage_Catalog_Helper_Product_Url_Rewrite
{
    public function __construct(array $args = array())
    {
        parent::__construct($args);
    }

    /**
     * Prepare url rewrite left join statement for given select instance and store_id parameter.
     *
     * @param Varien_Db_Select $select
     * @param int $storeId
     * @return Mage_Catalog_Helper_Product_Url_Rewrite_Interface
     */
    public function joinTableToSelect(Varien_Db_Select $select, $storeId)
    {
        $select->joinLeft(
            array('url_rewrite' => $this->_resource->getTableName('core/url_rewrite')),
            'url_rewrite.product_id = main_table.entity_id AND url_rewrite.is_system = 1 AND ' .
                $this->_connection->quoteInto('url_rewrite.category_id = main_table.schrack_main_category_eid AND url_rewrite.store_id = ? AND ',
                    (int)$storeId) .
                $this->_connection->prepareSqlCondition('url_rewrite.id_path', array('like' => 'product/%')),
            array('request_path' => 'url_rewrite.request_path'));
        return $this;
    }
}

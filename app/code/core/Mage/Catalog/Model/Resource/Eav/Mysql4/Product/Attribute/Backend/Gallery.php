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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package    Mage_Catalog
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Category image attribute backend
 *
 * @category    Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Attribute_Backend_Gallery extends Mage_Eav_Model_Entity_Attribute_Backend_Gallery
{

    public function __construct()
    {
        parent::__construct();
        $resource = Mage::getSingleton('core/resource');
        $this->setConnection($resource->getConnection('catalog_read'), $resource->getConnection('catalog_write'));
        /**
         * 0 - big image
         * 1 - thumbnail
         */
        $this->_imageTypes = array(0, 1);
    }

}

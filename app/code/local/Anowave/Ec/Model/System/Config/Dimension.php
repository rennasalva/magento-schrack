<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2015 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
class Anowave_Ec_Model_System_Config_Dimension
{
	public function toOptionArray()
	{
		return array
		(
			array
			(
				'value' => 'void',
				'label' => Mage::helper('ec')->__('No value')
			),
			array
			(
				'value' => 'getCustomerId', 
				'label' => Mage::helper('ec')->__('Customer ID')
			),
			array
			(
				'value' => 'getWishlistCount',
				'label' => Mage::helper('ec')->__('Number of items in Wishlist')
			),
			array
			(
				'value' => 'getCustomerGender',
				'label' => Mage::helper('ec')->__('Customer Gender')
			),
			array
			(
				'value' => 'getCustomerGroup',
				'label' => Mage::helper('ec')->__('Customer Group')
			),
			array
			(
				'value' => 'getCustomerOrdersCount',
				'label' => Mage::helper('ec')->__('Customer number of orders prior to purchase')
			)
		);
	}
}
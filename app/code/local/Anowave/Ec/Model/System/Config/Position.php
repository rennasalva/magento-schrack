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
class Anowave_Ec_Model_System_Config_Position
{
	/**
	 * After body opening tag
	 * @var int
	 */
	const GTM_LOCATION_AFTER_BODY_OPENING_TAG 	= 1;
	
	/**
	 * Before body closing tag
	 * @var int
	 */
	const GTM_LOCATION_BEFORE_BODY_CLOSING_TAG 	= 2;
	
	/**
	 * Within head section 
	 * 
	 * @var int
	 */
	const GTM_LOCATION_HEAD = 3;
	
	public function toOptionArray()
	{
		return array
		(
			array
			(
				'value' => self::GTM_LOCATION_AFTER_BODY_OPENING_TAG, 
				'label' => Mage::helper('ec')->__('After <body> opening tag')
			
			),
			array
			(
				'value' => self::GTM_LOCATION_BEFORE_BODY_CLOSING_TAG, 
				'label' => Mage::helper('ec')->__('Before </body> closing tag')
			),
			array
			(
				'value' => self::GTM_LOCATION_HEAD,
				'label' => Mage::helper('ec')->__('Inside <head></head> tag (Experimental)')
			)
		);
	}
}
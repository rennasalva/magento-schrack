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
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
class Anowave_Ec_Model_System_Config_Behaviour
{
	/**
	 * Experiment with original content
	 * 
	 * @var string
	 */
	const CONTENT_REPLACE 	= 1;

	/**
	 * Experiment at browser level 
	 * 
	 * @var int
	 */
	const CONTENT_KEEP = 2;
	
	/**
	 * Get Options 
	 * 
	 * @return multitype:multitype:string
	 */
	public function toOptionArray()
	{
		return array
		(
			array
			(
				'value' => self::CONTENT_REPLACE, 
				'label' => Mage::helper('ec')->__('Replace original content')
			),
			array
			(
				'value' => self::CONTENT_KEEP, 
				'label' => Mage::helper('ec')->__('Keep original content')
			)
		);
	}
}
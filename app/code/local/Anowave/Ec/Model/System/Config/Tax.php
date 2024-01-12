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
class Anowave_Ec_Model_System_Config_Tax
{
	public function toOptionArray()
	{
		return array
		(
			array
			(
				'value' => 0, 
				'label' => Mage::helper('ec')->__('Exclude Tax amount')
			
			),
			array
			(
				'value' => 1, 
				'label' => Mage::helper('ec')->__('Include Tax amount')
			)
		);
	}
}
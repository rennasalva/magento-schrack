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
class Anowave_Ec_Model_System_Config_Eav
{
	/**
	 * Get Options 
	 * 
	 * @return multitype:multitype:string
	 */
	public function toOptionArray()
	{
		$attributes = array();
		
		$attributes[] = array
		(
			'value' => 'id',
			'label' => 'ID'
		);
		
		foreach(Mage::getResourceModel('catalog/product_attribute_collection')->getItems() as $attribute)
		{
			if ($attribute->getFrontendLabel())
			{
				$attributes[] = array
				(
					'value' => $attribute->getAttributeCode(),
					'label' => $attribute->getFrontendLabel()
				);
			}
		}
		
		return $attributes;
	}
}
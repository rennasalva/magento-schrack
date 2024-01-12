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
class Anowave_Ec_Block_Html extends Schracklive_SchrackPage_Block_Html
{
	public function getAbsoluteFooter()
	{
		$model = new Varien_Object(array
		(
			'footer' => parent::getAbsoluteFooter()
		));
		
		/**
		 * Notify listeners for absolute footer.
		 * This allows also other extensions to modify the absolute footer.
		 */
		Mage::dispatchEvent('absolute_footer',array
		(
			'model' => $model
		));
		
		return $model->getFooter();
	}
}
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
class Anowave_Ec_Model_System_Config_Comment_Position extends Mage_Core_Model_Config_Data
{
	public function getCommentText(Mage_Core_Model_Config_Element $element, $value)
    {
        return join('<br />', array
        (
        	htmlentities('If GTM install snippet is located after the <body>'),
        	htmlentities('opening tag, data such as product details, impressions and purchases will NOT be tracked with standard PAGEVIEW.'),
        	htmlentities('You need to define 3 (three) additional tags firing on the following events: detail, impression, purchase')
        ));
    }
}
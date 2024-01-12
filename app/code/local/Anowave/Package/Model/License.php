<?php
/**
 * Anowave Package
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
 * @package 	Anowave_Package
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
class Anowave_Package_Model_License extends Mage_Core_Model_Config_Data
{
	public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
	{
		return Mage::helper('core')->__
		(
			'Enter license key for domain: <strong>' . $_SERVER['HTTP_HOST'] . '</strong>. <br /><br />To obtain a license key for <strong>' . $_SERVER['HTTP_HOST'] . '</strong>: <br /><br /> 1. Login at <a href="http://www.anowave.com/marketplace/" target="_blank">Anowave</a> and go to "My profile"<br /> 2. Click "Change profile settings"<br />3. Add "<strong>' . $_SERVER['HTTP_HOST'] . '</strong>" as new domain<br />4. Click again "My profile"<br />5. Click "View order"<br />6. License key(s) are shown on screen<br /><br />Keep your license keys secret. For any questions related to configuration and/or activation please contact us.<br /><br />Watch <a href="https://www.youtube.com/watch?v=hrPvZOZYIK4" target="_blank">How to add license key</a> <sup>&copy;</sup> on YouTube.<br /><br />'
		);
	}
}
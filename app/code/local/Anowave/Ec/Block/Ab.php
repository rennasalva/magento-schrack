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
 
class Anowave_Ec_Block_Ab extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{	
		$this->_controller 		= 'ab';
		$this->_blockGroup 		= 'ec';
		
		$this->_headerText 		= Mage::helper('ec')->__('Manage A/B Tests');
		$this->_addButtonLabel 	= Mage::helper('ec')->__('Add New');
		
		parent::__construct();
	}
}
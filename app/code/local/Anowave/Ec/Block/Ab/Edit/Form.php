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
 
class Anowave_Ec_Block_Ab_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Preparing global layout
     *
     * You can redefine this method in child classes for changin layout
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) 
        {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        } 
    }
    
    protected function _prepareForm()
    {
    	$form = new Varien_Data_Form(array
    	(
    		'id' 		=> 'edit_form',
    		'action' 	=> $this->getUrl('*/*/save', array
    		(
    			'id' => $this->getRequest()->getParam('id'))
    		),
    		'method' 	=> 'post',
    		'enctype' 	=> 'multipart/form-data'
    	));
    
    	$form->setUseContainer(true);
    	$this->setForm($form);
    
    	if (Mage::registry('ab'))
    	{
    		$form->setValues(Mage::registry('ab')->getData());
    	}
    
    	return parent::_prepareForm();
    }
}

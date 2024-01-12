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
 
class Anowave_Ec_Block_Ab_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'ec';
        $this->_controller = 'ab';
        
        $this->_updateButton('save', 	'label', Mage::helper('ec')->__('Save'));
        $this->_updateButton('delete', 	'label', Mage::helper('ec')->__('Delete'));

        $this->_addButton('saveandcontinue', array
        (
		    'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
		    'onclick'   => "editForm.submit($('edit_form').action + 'back/edit/')",
		    'class'     => 'save',
		), 10);

        if($this->getRequest()->getParam('id')) 
        {
            $model = Mage::getModel('ec/ab')->load
            (
            	$this->getRequest()->getParam('id')
            );
            
            /**
             * Load model stores
             */
            $model->loadStores();

            Mage::register('ab', $model);
        }
    }

    /**
     * Get header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if(Mage::registry('ab') && Mage::registry('ab')->getId()) 
        {
            return Mage::helper('ec')->__('Edit A/B Test');
        } 
        else 
        {
            return Mage::helper('ec')->__('Add A/B Test');
        }
    }
}

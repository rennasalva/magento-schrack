<?php

class Schracklive_SchrackCatalogInventory_Block_Adminhtml_Stock_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'schrackcataloginventory';
        $this->_controller = 'adminhtml_stock';
        $this->_mode = 'edit';
        $this->_addButton('save_and_continue', array('label' => Mage::helper('adminhtml')->__('Save And Continue Edit'), 'onclick' => 'saveAndContinueEdit()', 'class' => 'save',), -100);
        $this->_updateButton('save', 'label', Mage::helper('schrackcataloginventory')->__('Save Stock'));
        $this->_formScripts[] = "             function toggleEditor() {                 if (tinyMCE.getInstanceById('form_content') == null) {                     tinyMCE.execCommand('mceAddControl', false, 'edit_form');                 } else {                     tinyMCE.execCommand('mceRemoveControl', false, 'edit_form');                 }             }               function saveAndContinueEdit(){                 editForm.submit($('edit_form').action+'back/edit/');             }         ";
    }

    public function getHeaderText() {
        if (Mage::registry('stock_data') && Mage::registry('stock_data')->getId()) {
            return Mage::helper('schrackcataloginventory')->__('Edit Stock "%s"', $this->escapeHtml(Mage::registry('stock_data')->getName()));
        } else {
            return Mage::helper('schrackcataloginventory')->__('New Stock');
        }
    }

    protected function _prepareLayout() {
        if ( $this->_blockGroup && $this->_controller && $this->_mode ) {
            $this->setChild('form', $this->getLayout()->createBlock($this->_blockGroup.'/'.$this->_controller.'_'.$this->_mode.'_form'));
        } 
        return parent::_prepareLayout();
    }

}


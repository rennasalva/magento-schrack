<?php

class Schracklive_Translation_Block_Adminhtml_Translation_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

	public function __construct() {
		parent::__construct();

		$this->_objectId = 'id';
		$this->_blockGroup = 'translation';
		$this->_controller = 'adminhtml_translation';

		//$this->_updateButton('save', 'label', Mage::helper('translation')->__('Save Item'));
		//$this->_updateButton('delete', 'label', Mage::helper('translation')->__('Delete Item'));

		/*$this->_addButton('saveandcontinue', array(
			'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
			'onclick' => 'saveAndContinueEdit()',
			'class' => 'save',
				), -100);*/

		$this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('translation_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'translation_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'translation_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
		$this->removeButton('delete');
	}

	public function getHeaderText() {
		if (Mage::registry('translation_data') && Mage::registry('translation_data')->getId()) {
			return Mage::helper('translation')->__("Edit Item '%s' (%s)", $this->escapeHtml(Mage::registry('translation_data')->getStringEn()), Mage::registry('translation_data')->getLocale());
		} else {
			return Mage::helper('translation')->__('Add Item');
		}
	}

}

?>
<?php

class Schracklive_Wws_Block_Adminhtml_Signal_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

	public function __construct() {
		parent::__construct();

		$this->_objectId = 'id';
		$this->_blockGroup = 'wws';
		$this->_controller = 'adminhtml_signal';

		$this->_addButton('saveandcontinue', array(
			'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
			'onclick' => 'saveAndContinueEdit()',
			'class' => 'save',
				), -100);

		$this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('signal_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'signal_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'signal_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
	}

	public function getHeaderText() {
		if (Mage::registry('signal_data') && Mage::registry('signal_data')->getId()) {
			return Mage::helper('wws')->__("Edit WWS Message '%s'", $this->escapeHtml(Mage::registry('signal_data')->getCode()));
		} else {
			return Mage::helper('wws')->__('Add WWS Message');
		}
	}

}

?>
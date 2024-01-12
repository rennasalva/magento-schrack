<?php

class Schracklive_Branch_Block_Adminhtml_Branch_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

	public function __construct() {
		parent::__construct();

		$this->_objectId = 'id';
		$this->_blockGroup = 'branch';
		$this->_controller = 'adminhtml_branch';

		$this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
	}

	public function getHeaderText() {
		if (Mage::registry('branch_data') && Mage::registry('branch_data')->getId()) {
			return Mage::helper('branch')->__("Edit Branch '%s'", $this->escapeHtml(Mage::registry('branch_data')->getBranchId()));
		} else {
			return Mage::helper('branch')->__('Add Branch');
		}
	}

}

?>
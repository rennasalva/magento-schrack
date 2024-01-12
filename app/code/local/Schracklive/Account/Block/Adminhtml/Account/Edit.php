<?php

class Schracklive_Account_Block_Adminhtml_Account_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

	public function __construct()
	{
		parent::__construct();

        $this->_controller = 'adminhtml_account';
        $this->_blockGroup = 'account';

		$this->_updateButton('save', 'label', Mage::helper('account')->__('Save Account'));
		// $this->_updateButton('delete', 'label', Mage::helper('account')->__('Delete Account'));
		$this->_removeButton('delete');

		$this->setId('accountEdit');

/*
		$this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
*/
	}

	public function getHeaderText()
	{
		if( Mage::registry('account_account_data') && Mage::registry('account_account_data')->getId() ) {
			return Mage::helper('account')->__('Edit Account');
		} else {
			return Mage::helper('account')->__('Add Account');
		}
	}

}

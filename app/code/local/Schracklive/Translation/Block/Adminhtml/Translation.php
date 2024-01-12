<?php

class Schracklive_Translation_Block_Adminhtml_Translation extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		$this->_controller = 'adminhtml_translation';
		$this->_blockGroup = 'translation';
		$this->_headerText = Mage::helper('translation')->__('Item Manager');
		//$this->_addButtonLabel = Mage::helper('translation')->__('Add Item');
		if (Mage::getStoreConfig('schrack/translation/commit') == "1") {
			$this->addButton('savefiles', array(
				'label'     => Mage::helper('translation')->__('Save and Commit'),
				'onclick'   => "setLocation('".$this->getUrl('*/*/saveFiles')."')",
				'class'     => 'save'));
		}
		$this->addButton('loadfiles', array(
            'label'     => Mage::helper('translation')->__('Load from files'),
            'onclick'   => "setLocation('".$this->getUrl('*/*/loadFiles')."')",
            'class'     => 'load'));
		parent::__construct();
		$this->_removeButton('add');
	}

}

?>
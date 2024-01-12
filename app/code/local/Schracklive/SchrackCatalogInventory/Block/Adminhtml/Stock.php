<?php

class Schracklive_SchrackCatalogInventory_Block_Adminhtml_Stock extends Mage_Adminhtml_Block_Widget_Grid_Container {

    protected $_addButtonLabel = 'Add New Stock';

    public function __construct() {
        parent::__construct();
        $this->_controller = 'adminhtml_stock';
        $this->_blockGroup = 'schrackcataloginventory';
        $this->_headerText = Mage::helper('schrackcataloginventory')->__('Stocks');
    }

    protected function _prepareLayout() {
        $this->setChild('grid', $this->getLayout()->createBlock($this->_blockGroup.'/'.$this->_controller.'_grid', $this->_controller.'.grid')->setSaveParametersInSession(true));
        return parent::_prepareLayout();
    }

}

?>

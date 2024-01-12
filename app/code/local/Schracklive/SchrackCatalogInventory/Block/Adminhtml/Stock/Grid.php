<?php

class Schracklive_SchrackCatalogInventory_Block_Adminhtml_Stock_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('stock_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('cataloginventory/stock')->getCollection()->addFieldToFilter('stock_number', array('gt' => '0'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        /*
        $this->addColumn('stock_id',       array('header' => Mage::helper('cataloginventory')->__('ID'), 
                                                 'align' => 'right', 
                                                 'width' => '50px', 
                                                 'index' => 'stock_id',));
         */
        $this->addColumn('stock_number',   array('header' => Mage::helper('cataloginventory')->__('Number'), 
                                                 'align' => 'right', 
                                                 'index' => 'stock_number',));
        $this->addColumn('stock_location', array('header' => Mage::helper('cataloginventory')->__('Vendor'),
                                                 'align' => 'left',
                                                 'index' => 'stock_location',));
        $this->addColumn('stock_name',     array('header' => Mage::helper('cataloginventory')->__('Stock Name'),
                                                 'align' => 'left', 
                                                 'index' => 'stock_name',));
        $this->addColumn('is_pickup',      array('header' => Mage::helper('cataloginventory')->__('Is Pickup'),
                                                 'align' => 'left', 
                                                 'index' => 'is_pickup',));
        $this->addColumn('is_delivery',    array('header' => Mage::helper('cataloginventory')->__('Is Delivery'), 
                                                 'align' => 'left', 
                                                 'index' => 'is_delivery',));
        $this->addColumn('delivery_hours', array('header' => Mage::helper('cataloginventory')->__('Delivery Hours'), 
                                                 'align' => 'right', 
                                                 'index' => 'delivery_hours',));
        $this->addColumn('locked_until',   array('header' => Mage::helper('cataloginventory')->__('Locked Until'),
                                                 'align' => 'left',
                                                 'index' => 'locked_until',));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));     
    }     
}

?>

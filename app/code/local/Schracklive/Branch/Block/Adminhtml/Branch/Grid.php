<?php

class Schracklive_Branch_Block_Adminhtml_Branch_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {
		parent::__construct();
		$this->setId('branchGrid');
		$this->setDefaultSort('branch_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection() {
		/** @var $collection Schracklive_Branch_Model_Mysql4_Branch_Collection */
		$collection = Mage::getModel('branch/branch')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {
		$this->addColumn('entity_id', array(
			'header' => Mage::helper('branch')->__('ID'),
			'align' => 'right',
			'width' => '50px',
			'index' => 'entity_id',
		));

		$this->addColumn('branch_id', array(
			'header' => Mage::helper('branch')->__('Branch ID'),
			'align' => 'left',
			'width' => '100px',
			'index' => 'branch_id',
		));

		$this->addColumn('warehouse_id', array(
			'header' => Mage::helper('branch')->__('Warehouse ID'),
			'align' => 'left',
			'index' => 'warehouse_id',
		));

		$this->addColumn('action',
				array(
					'header' => Mage::helper('branch')->__('Action'),
					'width' => '100',
					'type' => 'action',
					'getter' => 'getId',
					'actions' => array(
						array(
							'caption' => Mage::helper('branch')->__('Edit'),
							'url' => array('base' => '*/*/edit'),
							'field' => 'id'
						)
					),
					'filter' => false,
					'sortable' => false,
					'index' => 'stores',
					'is_system' => true,
		));
		return parent::_prepareColumns();
	}

	public function getRowUrl($row) {
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

}

?>
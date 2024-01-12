<?php

class Schracklive_Wws_Block_Adminhtml_Signal_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {
		parent::__construct();
		$this->setId('signalGrid');
		$this->setDefaultSort('signal_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection() {
		$collection = Mage::getModel('wws/signal')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {
		$this->addColumn('signal_id', array(
			'header' => Mage::helper('wws')->__('ID'),
			'align' => 'right',
			'width' => '50px',
			'index' => 'signal_id',
		));

		$this->addColumn('code', array(
			'header' => Mage::helper('wws')->__('Code'),
			'align' => 'right',
			'width' => '50px',
			'index' => 'code',
		));

		$this->addColumn('wws_message', array(
			'header' => Mage::helper('wws')->__('WWS Message'),
			'align' => 'left',
			'index' => 'wws_message',
		));

		$this->addColumn('message', array(
			'header' => Mage::helper('wws')->__('Message'),
			'align' => 'left',
			'index' => 'message',
		));

		$this->addColumn('action',
				array(
					'header' => Mage::helper('wws')->__('Action'),
					'width' => '100',
					'type' => 'action',
					'getter' => 'getId',
					'actions' => array(
						array(
							'caption' => Mage::helper('wws')->__('Edit'),
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

	protected function _prepareMassaction() {
		$this->setMassactionIdField('signal_id');
		$this->getMassactionBlock()->setFormFieldName('signal');

		$this->getMassactionBlock()->addItem('delete', array(
			'label' => Mage::helper('wws')->__('Delete'),
			'url' => $this->getUrl('*/*/massDelete'),
			'confirm' => Mage::helper('wws')->__('Are you sure?')
		));

		return $this;
	}

	public function getRowUrl($row) {
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

}

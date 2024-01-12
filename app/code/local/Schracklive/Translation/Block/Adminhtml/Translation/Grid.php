<?php

class Schracklive_Translation_Block_Adminhtml_Translation_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {
		parent::__construct();
		$this->setId('translationGrid');
		$this->setDefaultSort('translation_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection() {
		$collection = Mage::getModel('translation/translation')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {
		$this->addColumn('translation_id', array(
			'header' => Mage::helper('translation')->__('ID'),
			'align' => 'right',
			'width' => '50px',
			'index' => 'translation_id',
		));

		$this->addColumn('module_name', array(
			'header' => Mage::helper('translation')->__('Module Name'),
			'align' => 'left',
			'width' => '100px',
			'index' => 'module_name',
		));

		$this->addColumn('string_en', array(
			'header' => Mage::helper('translation')->__('Original'),
			'align' => 'left',
			'index' => 'string_en',
		));

		$this->addColumn('string_translated', array(
			'header' => Mage::helper('translation')->__('Translated'),
			'align' => 'left',
			'index' => 'string_translated',
		));

		$this->addColumn('locale', array(
			'header' => Mage::helper('translation')->__('Locale'),
			'align' => 'left',
			'width' => '80px',
			'index' => 'locale',
			'type' => 'options',
			'options' => $this->_getLocaleOptions(),
		));
		
		$this->addColumn('is_local', array(
			'header' => Mage::helper('translation')->__('Source'),
			'align' => 'left',
			'width' => '80px',
			'index' => 'is_local',
			'type' => 'options',
			'options' => array(
				0 => 'Magento',
				1 => 'P2N',
			),
		));

		$this->addColumn('is_orphaned', array(
			'header' => Mage::helper('translation')->__('Orphaned'),
			'align' => 'left',
			'index' => 'is_orphaned',
			'type' => 'options',
			'options' => array(
				0 => Mage::helper('translation')->__('No'),
				1 => Mage::helper('translation')->__('Yes'),
			),
		));
		
		$this->addColumn('created_time', array(
			'header' => Mage::helper('translation')->__('Date Added'),
			'align' => 'left',
			'index' => 'created_time',
		));

		$this->addColumn('action',
				array(
					'header' => Mage::helper('translation')->__('Action'),
					'width' => '100',
					'type' => 'action',
					'getter' => 'getId',
					'actions' => array(
						array(
							'caption' => Mage::helper('translation')->__('Edit'),
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

	protected function _getLocaleOptions() {
		$options = array();
		$locales = Mage::helper('translation')->getTranslatableLocales();
		foreach ($locales as $locale) {
			$options[$locale] = $locale;
		}
		return $options;
	}

	public function getRowUrl($row) {
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

}

?>
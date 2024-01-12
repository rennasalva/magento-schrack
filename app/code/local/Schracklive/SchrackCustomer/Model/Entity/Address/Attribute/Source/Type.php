<?php

class Schracklive_SchrackCustomer_Model_Entity_Address_Attribute_Source_Type extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

	protected $types = array(
		'1' => 'billing address',
		'2' => 'Company',
		'3' => 'apartment building',
		'4' => 'warehouse',
		'5' => 'construction site',
	);

	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions() {
		if (is_null($this->_options)) {
			foreach ($this->types as $value => $label) {
				$this->_options[] = array(
					'label' => Mage::helper('schrackcustomer')->__($label),
					'value' => $value
				);
			}
		}
		return $this->_options;
	}

	public function getStandardOptions() {
		$options = $this->getAllOptions();
		array_shift($options);

		return $options;
	}

}

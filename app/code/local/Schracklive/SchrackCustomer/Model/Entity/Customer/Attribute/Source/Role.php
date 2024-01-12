<?php

class Schracklive_SchrackCustomer_Model_Entity_Customer_Attribute_Source_Role extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

	protected $roles = array(
		'0' => '',
		'19' => 'owner',
		'1' => 'manager',
		'11' => 'executive manager',
		'20' => 'department manager',
		'2' => 'project manager',
		'3' => 'technician',
		'4' => 'purchasing agent',
		'5' => 'construction manager',
		'6' => 'fitter',
		'7' => 'office',
		'8' => 'other influencer',
		'9' => 'depot',
		'10' => 'accounting',
		'12' => 'designer',
		'13' => 'trainee',
		'14' => 'assistant',
		'15' => 'inspector',
		'16' => 'salesman',
		'17' => 'consultant',
		'18' => 'employee',
	);

	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions() {
		if (is_null($this->_options)) {
			foreach ($this->roles as $value => $label) {
				$this->_options[$value] = array(
					'label' => Mage::helper('schrackcustomer')->__($label),
					'value' => $value
				);
			}
		}
		return $this->_options;
	}

}

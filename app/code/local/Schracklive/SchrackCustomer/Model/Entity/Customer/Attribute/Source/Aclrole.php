<?php

class Schracklive_SchrackCustomer_Model_Entity_Customer_Attribute_Source_Aclrole extends Mage_Eav_Model_Entity_Attribute_Source_Table {

	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions() {
		if (is_null($this->_options)) {
			$options = Mage::getResourceModel('schrack/acl_role_collection')
				->load()
				->toOptionArray();
			array_unshift($options, array('value'=>'', 'label'=>'no role'));
			foreach ($options as $i => $option) {
				$this->_options[$i] = array(
					'label' => Mage::helper('schrackcustomer')->__($option['label']),
					'value' => $option['value'],
				);
			}
		}
		return $this->_options;
	}

}

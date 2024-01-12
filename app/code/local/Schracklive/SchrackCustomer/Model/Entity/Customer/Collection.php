<?php

class Schracklive_SchrackCustomer_Model_Entity_Customer_Collection extends Mage_Customer_Model_Entity_Customer_Collection {

	public function setAccountIdFilter($schrackAccountId) {
		$this->addFieldToFilter('schrack_account_id', $schrackAccountId);
		return $this;
	}

	public function setSystemContactFilter() {
		$this->addFieldToFilter('group_id', Mage::getStoreConfig('schrack/shop/system_group'));
		$this->addFieldToFilter('schrack_account_id', array('gt' => 0));
		$this->addFieldToFilter('schrack_wws_customer_id', array('neq' => ''));
		$this->addFieldToFilter('schrack_wws_contact_number', array('eq' => '-1'));
		return $this;
	}

	public function setContactFilter() {
		$this->addFieldToFilter('group_id', Mage::getStoreConfig('schrack/shop/contact_group'));
		$this->addFieldToFilter('schrack_account_id', array('gt' => 0));
		$this->addFieldToFilter('schrack_wws_customer_id', array('neq' => ''));
		$this->addFieldToFilter('schrack_wws_contact_number', array('gt' => 0));
		return $this;
	}

	public function setAnyContactFilter() {
		$this->addFieldToFilter(
				array(
					array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/system_group')),
					array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/contact_group')),
					array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/inactive_contact_group')),
					array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/deleted_contact_group'))
				)
		);
		$this->addFieldToFilter('schrack_account_id', array('gt' => 0));
		$this->addFieldToFilter('schrack_wws_customer_id', array('neq' => ''));
		return $this;
	}

	public function setRealContactAndProspectFilter($includeDeleted=true) {
		$groups = array(
			array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/contact_group')),
			array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/inactive_contact_group')),
			array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/prospect_group')),
		);
		if ($includeDeleted) {
			$groups[] = array('attribute' => 'group_id', 'eq' => Mage::getStoreConfig('schrack/shop/deleted_contact_group'));
		}
		$this->addFieldToFilter($groups);
		$this->addFieldToFilter('schrack_account_id', array('gt' => 0));
		return $this;
	}

	public function setProspectFilter() {
		$this->addFieldToFilter('group_id', Mage::getStoreConfig('schrack/shop/prospect_group'));
		$this->addFieldToFilter('schrack_account_id', array('gt' => 0));
		$this->addFieldToFilter('schrack_wws_contact_number', array('eq' => 0));
		return $this;
	}

	// sh@plan2.net
	// Adapted from addExpressionAttributeToSelect
	public function addExpressionAttributeToFilter($alias, $expression, $attribute, $value) {
		// validate alias
		if (isset($this->_joinFields[$alias])) {
			throw Mage::exception('Mage_Eav',
					Mage::helper('eav')->__('Joined field or attribute expression with this alias is already declared'));
		}
		if (!is_array($attribute)) {
			$attribute = array($attribute);
		}

		$fullExpression = $expression;
		// Replacing multiple attributes
		foreach ($attribute as $attributeItem) {
			if (isset($this->_staticFields[$attributeItem])) {
				$attrField = sprintf('e.%s', $attributeItem);
			} else {
				$attributeInstance = $this->getAttribute($attributeItem);

				if ($attributeInstance->getBackend()->isStatic()) {
					$attrField = 'e.'.$attributeItem;
				} else {
					$this->_addAttributeJoin($attributeItem, 'left');
					$attrField = $this->_getAttributeFieldName($attributeItem);
				}
			}

			$fullExpression = str_replace('{{attribute}}', $attrField, $fullExpression);
			$fullExpression = str_replace('{{'.$attributeItem.'}}', $attrField, $fullExpression);
		}

		if (!empty($fullExpression)) {
			$this->getSelect()->where($fullExpression." ".$value);
		} else {
			Mage::throwException('Invalid attribute identifier for filter ('.get_class($attribute).')');
		}
		return $this;
	}

}

?>

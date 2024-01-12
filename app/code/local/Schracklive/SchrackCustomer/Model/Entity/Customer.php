<?php

class Schracklive_SchrackCustomer_Model_Entity_Customer extends Mage_Customer_Model_Entity_Customer {

	protected function _beforeSave(Varien_Object $customer) {
        // Set id (entity_id) to object, if already existing, for inform parent class (UPDATE):
        if ($customer->getEmail() && !$customer->getId()) {
            $resource       = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');

            $query = "SELECT entity_id FROM customer_entity WHERE email LIKE '" . $customer->getEmail() . "'";

            $result = $readConnection->fetchAll($query);

            if (is_array($result) && !empty($result)) {
                foreach ($result as $index => $recordset) {
                    $entity_id = intval($recordset['entity_id']);
                }
                if ($entity_id) {
                    $customer->setData('id', $entity_id);
                    $customer->setData('entity_id', $entity_id);
                }
            }
        }

        parent::_beforeSave($customer);

		$customer->setUpdatedBy(Mage::helper('schrack/mysql4')->getChangeIdentifier());
		if (!$customer->getId()) {
			$customer->setCreatedBy($customer->getUpdatedBy());
		}

		return $this;
	}

	public function loadByWwsContactNumber(Schracklive_SchrackCustomer_Model_Customer $customer, $wwsCustomerId, $wwsContactNumber) {
		$this->_loadByContactNumber($customer, 'schrack_wws_customer_id', $wwsCustomerId, $wwsContactNumber);
		return $this;
	}
	
	public function loadByEmailAddress(Schracklive_SchrackCustomer_Model_Customer $customer, $wwsCustomerId, $emailAddress) {
		$this->_loadByEmailAddress($customer, 'schrack_wws_customer_id', $wwsCustomerId, $emailAddress);
		return $this;
	}

    /**
     * load only by email address, which we know to be unique, without having to set a wws_customer_id
     * @param Schracklive_SchrackCustomer_Model_Customer $customer
     * @param $wwsCustomerId
     * @param $emailAddress
     * @return $this
     */
    public function loadByEmailAddressOnly(Schracklive_SchrackCustomer_Model_Customer $customer, $emailAddress) {
        $this->_loadByEmailAddress($customer, null, null, $emailAddress);
        return $this;
    }

	public function loadByAccountContactNumber(Schracklive_SchrackCustomer_Model_Customer $customer, $accountId, $wwsContactNumber) {
		$this->_loadByContactNumber($customer, 'schrack_account_id', $accountId, $wwsContactNumber);
		return $this;
	}

	protected function _loadByContactNumber(Schracklive_SchrackCustomer_Model_Customer $customer, $customerIdField, $customerId, $wwsContactNumber) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable())
				->where($customerIdField.'=:customer_id')
				->where('schrack_wws_contact_number=:wws_contact_number');
		if ($customer->getSharingConfig()->isWebsiteScope()) {
			if (!$customer->hasData('website_id')) {
				Mage::throwException(Mage::helper('customer')->__('Customer website id must be specified, when using website scope.'));
			}
			$select->where('website_id=?', (int)$customer->getWebsiteId());
		}

		$id = $this->_getReadAdapter()->fetchOne($select, array(
			'customer_id' => $customerId,
			'wws_contact_number' => $wwsContactNumber,
				));
		if ($id) {
			$this->load($customer, $id);
		} else {
			$customer->setData(array());
		}
		return $this;
	}

	public function loadByS4sNickname ( Schracklive_SchrackCustomer_Model_Customer $customer, $s4sNickname ) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable())
				->where('schrack_s4s_nickname=:nickname');

		if ( $id = $this->_getReadAdapter()->fetchOne($select, array('nickname' => $s4sNickname)) ) {
			$this->load($customer, $id);
		} else {
			$customer->setData(array());
		}
		return $this;
	}
	
	public function loadByS4sId ( Schracklive_SchrackCustomer_Model_Customer $customer, $s4sId ) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable())
				->where('schrack_s4s_id=:id');

		if ( $id = $this->_getReadAdapter()->fetchOne($select, array('id' => $s4sId)) ) {
			$this->load($customer, $id);
		} else {
			$customer->setData(array());
		}
		return $this;
	}

	protected function _loadByEmailAddress(Schracklive_SchrackCustomer_Model_Customer $customer, $customerIdField, $customerId, $emailAddress) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable());
		if ( isset($customerIdField) ) {
			$select->where($customerIdField.'=:customer_id');
			$params = array('customer_id' => $customerId,'customer_email' => $emailAddress);
		} else {
			$params = array('customer_email' => $emailAddress);
		}
		$select->where('email=:customer_email');
		if ($customer->getSharingConfig()->isWebsiteScope()) {
			if (!$customer->hasData('website_id')) {
				Mage::throwException(Mage::helper('customer')->__('Customer website id must be specified, when using website scope.'));
			}
			$select->where('website_id=?', (int)$customer->getWebsiteId());
		}

		
		$id = $this->_getReadAdapter()->fetchOne($select, $params);
		if ($id) {
			$this->load($customer, $id);
		} else {
			$customer->setData(array());
		}
		return $this;
	}

	public function loadByUserPrincipalName(Schracklive_SchrackCustomer_Model_Customer $customer, $userPrincipalName) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable())
				->where('schrack_user_principal_name=:user_principal_name');
		if ($customer->getSharingConfig()->isWebsiteScope()) {
			if (!$customer->hasData('website_id')) {
				Mage::throwException(Mage::helper('customer')->__('Customer website id must be specified, when using website scope.'));
			}
			$select->where('website_id=?', (int)$customer->getWebsiteId());
		}

		if ($id = $this->_getReadAdapter()->fetchOne($select, array('user_principal_name' => $userPrincipalName))) {
			$this->load($customer, $id);
		} else {
			$customer->setData(array());
		}
		return $this;
	}

	public function loadTypeFields(Schracklive_SchrackCustomer_Model_Customer $customer) {
		if (!$customer->getId()) {
			return;
		}
		$fields = array('group_id', 'schrack_account_id', 'schrack_wws_customer_id', 'schrack_wws_contact_number', 'schrack_user_principal_name');
		foreach ($fields as $field) {
			if (!$customer->hasData($field)) {
				$this->_fetchAndSetStaticFields($customer, $fields);
				break;
			}
		}
	}

	protected function _fetchAndSetStaticFields(Schracklive_SchrackCustomer_Model_Customer $customer, $fields) {
		$select = $this->_getReadAdapter()->select()
				->from($this->getEntityTable(), $fields)
				->where($this->getEntityIdField().'='.$customer->getId());
		$row = $this->_getReadAdapter()->fetchRow($select, array(), Zend_Db::FETCH_ASSOC);

		foreach ($fields as $field) {
			if (!$customer->hasData($field)) {
				$customer->setData($field, $row[$field]);
			}
		}
	}

	/**
	 * Save/delete customer address
	 *
	 * @param Mage_Customer_Model_Customer $customer
	 * @return Mage_Customer_Model_Entity_Customer
	 */
	protected function _saveAddresses(Mage_Customer_Model_Customer $customer) {
		$defaultBillingId = $customer->getData('default_billing');
		$defaultShippingId = $customer->getData('default_shipping');
		foreach ($customer->getAddresses() as $address) {
			if ($address->getData('_deleted')) {
				if ($address->getId() == $defaultBillingId) {
					$customer->setData('default_billing', null);
				}
				if ($address->getId() == $defaultShippingId) {
					$customer->setData('default_shipping', null);
				}
				$address->delete();
			} else {
				// Schrack Live: use system contact
				$address->setParentId($customer->getSystemContact()->getId())
						->setStoreId($customer->getStoreId())
						->setIsCustomerSaveTransaction(true)
						->save();
				// Schrack Live: no default addresses for contacts
				if ($customer->isContact()) {
					$customer->setData('default_billing', null);
					$customer->setData('default_shipping', null);
				} else {
					if (($address->getIsPrimaryBilling() || $address->getIsDefaultBilling())
							&& $address->getId() != $defaultBillingId) {
						$customer->setData('default_billing', $address->getId());
					}
					if (($address->getIsPrimaryShipping() || $address->getIsDefaultShipping())
							&& $address->getId() != $defaultShippingId) {
						$customer->setData('default_shipping', $address->getId());
					}
				}
			}
		}
		if ($customer->dataHasChangedFor('default_billing')) {
			$this->saveAttribute($customer, 'default_billing');
		}
		if ($customer->dataHasChangedFor('default_shipping')) {
			$this->saveAttribute($customer, 'default_shipping');
		}
		return $this;
	}

	public function updateWwsCustomerId(Schracklive_SchrackCustomer_Model_Customer $customer) {
		$account = $customer->getAccount();
		$this->_getReadAdapter()->update($this->getEntityTable(), array('schrack_wws_customer_id' => $account->getWwsCustomerId()), 'schrack_account_id='.$account->getId());
	}

}

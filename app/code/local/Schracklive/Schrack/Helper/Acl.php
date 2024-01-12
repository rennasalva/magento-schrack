<?php

class Schracklive_Schrack_Helper_Acl extends Mage_Core_Helper_Abstract {

	// Hard-coded id's from database for ACL Roles:
	const ADMIN_ROLE_ID = 3; // @todo make configurable
	const ESTIMATOR_ROLE_ID = 4;
	const SYSTEM_CONTACT_ROLE_ID = 8; // @todo attach to group
	const ANONYMOUS_ROLE_ID = 9; // @todo make configurable
	const EMPLOYEE_ROLE_ID = 10; // @todo attach to group
	const DEFAULT_ROLE_ID = 5; // @todo make configurable
	const PROJECTANT_ROLE_ID = 11; // @todo make configurable
	const DEFAULT_NO_PRICES_ROLE_ID = 12; // @todo make configurable

	public function getAdminRoleId() {
		return self::ADMIN_ROLE_ID;
	}

	public function getSystemContactRoleId() {
		return self::SYSTEM_CONTACT_ROLE_ID;
	}

	public function getAnonymousRoleId() {
		return self::ANONYMOUS_ROLE_ID;
	}

	public function getEmployeeRoleId() {
		return self::EMPLOYEE_ROLE_ID;
	}

	public function getProjectantRoleId() {
		return self::PROJECTANT_ROLE_ID;
	}

	public function getDefaultRoleId() {
		return self::DEFAULT_ROLE_ID;
	}

	public function isAdminRoleId($id) {
		return self::ADMIN_ROLE_ID == $id;
	}

    public function getDefaultNoPricesRoleId() {
        return self::DEFAULT_NO_PRICES_ROLE_ID;
    }

	public function mayCheckout ( $customer = null ) {
        if ( ! $customer ) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        $aclRoleId = $customer->getSchrackAclRoleId();
		return $aclRoleId != self::ESTIMATOR_ROLE_ID && ! $this->isProjectantRoleIdWithCustomer($customer,$aclRoleId);
	}

	public function isProjectantRoleId($roleid) {
		$sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		$customer = Mage::getModel('customer/customer')->load($sessionCustomerId);
		return $this->isProjectantRoleIdWithCustomer($customer,$roleid);
	}

	public function isProjectantRoleIdWithCustomer($customer,$roleid) {
		// Special case: light
		$groupId = $customer->getGroupId();
		$prospectType = $customer->getSchrackCustomerType();
		$standardProspectGroup = Mage::getStoreConfig('schrack/shop/prospect_group');

		// If we configured that light prospect should have projectant group:
		if ($groupId == $standardProspectGroup
				&& ($prospectType == 'light-prospect')
				&& Mage::getStoreConfig('schrack/new_self_registration/groupAssignProjectantRoleToLightProspect') === '1') {
            $roleid = self::PROJECTANT_ROLE_ID;
		}

		// If we configured that full prospect should have projectant group:
		if ($groupId == $standardProspectGroup
				&& ($prospectType == 'full-prospect')
				&& Mage::getStoreConfig('schrack/new_self_registration/groupAssignProjectantRoleToFullProspect') === '1') {
            $roleid = self::PROJECTANT_ROLE_ID;
		}

        // This is a additional special case for 'list_price_customer' - Role:
		if ($roleid == 12) {
            $roleid = self::PROJECTANT_ROLE_ID;
		}

		return self::PROJECTANT_ROLE_ID == $roleid;
	}

	public function getAclRoleSelectorBoxForCustomerId($customerId=null) {
		if (!is_null($customerId)) {
			$aclRoleId = Mage::getModel('customer/customer')->load($customerId)->getSchrackAclRoleId();
		} else {
			$aclRoleId = $this->getDefaultRoleId();
		}
		$options = Mage::getResourceModel('schrack/acl_role_collection')
				->setVisibleOnWebsiteFilter()
				->setOrder('position', 'ASC')
				->load()
				->toOptionArray();

        $disableSelection = '';
        $readOnlyMode = false;
        if ($aclRoleId == 12) {
            $readOnlyMode = true;
            $disableSelection = ' disabled="disabled"';
        }

		$select = '<select name="role" size="1" class="form-control select_role_by_user"' . $disableSelection . '>';

		foreach ($options as $option) {
		    if ($readOnlyMode == false) {
		        // Extract select list entry in caase of normal user (should not be available):
                if ($option['value'] == 12) continue;
		    }
			$selected = ($aclRoleId == $option['value']) ? 'selected="selected"' : '';
			if ($option['label'] == 'list_price_customer') $option['label'] = 'List Price Customer';
			$text = $this->__($option['label']);
			$value = $option['value'];
			$select .= '<option value="' . $value . '" ' . $selected . '>' . $text . '</option>';
		}
		$select .= '</select>';

		return $select;
	}

}

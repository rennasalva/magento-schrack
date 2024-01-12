<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MageDeveloper_TYPO3connect_Model_Customer_Api_V2 extends Mage_Customer_Model_Customer_Api_V2
{

	/**
	 * @return string
	 */
	public function fetchalladvisors() {
		$result = array();
		$advisors = Mage::getModel('customer/customer')
		                ->getCollection()
		                ->addAttributeToSelect('email')
						->addFieldToFilter('group_id', Mage::getStoreConfig('schrack/shop/employee_group'))
						->addFieldToFilter('schrack_acl_role_id', Mage::helper('schrack/acl')->getEmployeeRoleId())
						->addFieldToFilter('schrack_user_principal_name', array('neq' => 'NULL'));
		foreach ($advisors as $advisor) {
			$advisorData = json_decode($this->fetch($advisor->getEmail(), null, true), true);
			$advisorDataHack = Mage::getModel('customer/customer')->loadByUserPrincipalName($advisorData['attributes']['schrack_user_principal_name']);
			$advisorData['attributes']['schrack_title'] = $advisorDataHack->getSchrackTitle();
			$result[] = $advisorData;
		}
		return json_encode($result);
	}

	/**
	 * Retrieve full customer data
	 * 
	 * @param string $email
	 * @param array $attributes
	 * @return array
	 */
	public function fetch($email, $attributes = null, $skipAddress = false) {
		$customer = Mage::getModel('customer/customer')
							->getCollection()
							->addAttributeToSelect('*')
							->addFieldToFilter('email', $email)
							->getFirstItem();

        if (!$customer->getId()) {
	        die();
            //$this->_fault('not_exists');
        }

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

		// ATTRIBUTES AND GENERAL INFORMATION
        $result = array();
        foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            $result['attributes'][$attributeAlias] = $customer->getData($attributeCode);
        }
        foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
            $result['attributes'][$attributeCode] = $customer->getData($attributeCode);
        }

		// CUSTOMER GROUP
		$group = Mage::getModel('customer/group')->load($customer->getGroupId());
		$result['attributes']['group'] = $group->getCode();

		// ADDRESSES
		if (!$skipAddress && !$result['attributes']['schrack_user_principal_name']) {
			$billingId = $customer->getDefaultBilling();
			$shippingId = $customer->getDefaultShipping();

			// Use Address Api
			$addrApi = Mage::getModel('customer/address_api');

			if ($billingId) {
				$result['billing'] = $addrApi->info($billingId);
			}

			if ($shippingId) {
				$result['shipping'] = $addrApi->info($shippingId);
			}
		}

        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT count(*) FROM schrack_terms_of_use";
        $termsOfUseCount = (int) $readConnection->fetchOne($sql);
		if ( $termsOfUseCount > 0 && ! $customer->getSchrackLastTermsConfirmed() ) {
            $sql = "SELECT entity_id, content FROM schrack_terms_of_use ORDER BY entity_id DESC LIMIT 1";
            $dbRes = $readConnection->fetchAll($sql);
            $row = reset($dbRes);
            $result['attributes']['new_terms_of_use_version'] = $row['entity_id'];
            $result['attributes']['new_terms_of_use_content_base64'] = $row['content'];
        }
			
		$ser = json_encode($result);
		return $ser;
	}
	



}
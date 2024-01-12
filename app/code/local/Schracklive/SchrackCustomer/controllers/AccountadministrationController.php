<?php

class Schracklive_SchrackCustomer_AccountadministrationController extends Mage_Core_Controller_Front_Action {

	/**
	 * Retrieve customer session model object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession() {
		return Mage::getSingleton('customer/session');
	}

	public function indexAction() {
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

        $schrackWwsContactNumber = $this->_getSession()->getCustomer()->getSchrackWwsContactNumber();
        if ($schrackWwsContactNumber == '-1') {
            $actAsCustomerActivated = true;
        } else {
            $actAsCustomerActivated = false;
        }

		if (!$this->_getSession()->getCustomer()->isAllowed('accessRight', 'edit') && $actAsCustomerActivated == false) {
			$this->_getSession()->addError($this->__('You have no right to edit accounts!'));
			$this->_redirect('*/account');
			return;
		}

		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
		$navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
		if ($navigationBlock) {
			$navigationBlock->setActive('customer/*/');
		}
		$this->renderLayout();
	}

	public function editAction() {
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$editCustomerID = Mage::app()->getRequest()->getParam('id');

		if (!$this->hasRightToEdit($editCustomerID) && !is_null($editCustomerID)) {
			$this->_getSession()->addError($this->__('You have no right to edit this account!'));
			$this->_redirect('*/*/');
			return;
		}

		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');

		$this->renderLayout();
	}

	public function newAction() {
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$customerModel = $this->_getSession()->getCustomer();

		if (!$customerModel->isAllowed('accessRight', 'edit')) {
			$this->_getSession()->addError($this->__('You have no right to create accounts!'));
			$this->_redirect('*/account');
			return;
		}

		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');

		$this->renderLayout();
	}

	public function savenewAction() {
		$session = $this->_getSession();
		if (!$session->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$errors = array();

		$data = $this->getRequest()->getPost();

        $dataTemp = array();
        foreach($data as $key => $value) {
            $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
            $dataTemp[$key] = $value;
        }
        $data = array();
        $data = $dataTemp;

        if (isset($data['schrack_salutatory'])) {
            $data['schrack_salutatory'] = str_replace(' undefined', '', $data['schrack_salutatory']);
        }

		if ($this->_getSession()->getCustomer()->getSchrackWwsCustomerId()) {
			$groupId = Mage::getStoreConfig('schrack/shop/contact_group');
		} else {
			$groupId = Mage::getStoreConfig('schrack/shop/prospect_group');
		}

		$customer = Mage::getModel('customer/customer');

        $emailFromNewContact = $data['email'];
        $query = "SELECT * FROM magento_common.login_token WHERE email LIKE '" . $emailFromNewContact . "'";
        $resource        = Mage::getSingleton('core/resource');
        $readConnection  = $resource->getConnection('core_read');
        $queryResult = $readConnection->query($query);
        if ($queryResult->rowCount() > 0) {
            $errors[0] = $this->__('This customer email already exists.') . ' -> ' . $emailFromNewContact;
        } else {
            $customer->setGroupId($groupId);
            $customer->setSchrackAccountId($this->_getSession()->getCustomer()->getSchrackAccountId());
            $customer->setSchrackWwsCustomerId($this->_getSession()->getCustomer()->getSchrackWwsCustomerId());
            $customer->setSchrackAclRoleId($data['role']);
            $customer->setEmail($data['email']);
            $customer->setPassword($customer->generatePassword());
            $customer->setPasswordConfirmation($customer->getPassword());
            $customer->setFirstname($data['firstname']);
            $customer->setLastname($data['lastname']);
            $customer->setGender($data['gender']);
            $customer->setPrefix(isset($data['prefix']) ? $data['prefix'] : '');
            $customer->setSchrackSalutatory($data['schrack_salutatory']);
            $customer->setSchrackAdvisorPrincipalName($this->_getSession()->getCustomer()->getAccount()->getAdvisorPrincipalName());

            Mage::helper('schrackcustomer/phone')->setPhoneNumbers($data, $customer);

            $errors = $customer->validate();
        }

		if (!is_array($errors)) {
			$errors = array();
		}
		$errors = array_merge($errors, Mage::helper('schrackcustomer/phone')->validatePhonenumbers($data));

		if (empty($errors)) {
			try {
                $customer->setConfirmation($customer->getRandomConfirmationKey());
                Mage::getSingleton('core/session')->setUserModificationAction('contact created');
				$customer->save();
				if ($customer->isConfirmationRequired()) {
					$customer->sendNewAccountEmail('confirmation');
				}
				$this->_getSession()->addSuccess($this->__('Account saved!'));
				$this->_getSession()->setCustomerFormData();
				$this->_redirect('*/*/');
				return;
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Can\'t save account'));
				$this->_redirect('*/*/new/');
				return;
			}
		} else {
			$this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
			foreach ($errors as $errorMsg) {
				$this->_getSession()->addError($errorMsg);
			}
		}

		$this->_redirect('*/*/new/');
	}

	public function saveAction() {
		$session = $this->_getSession();
		if (!$session->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$editCustomerID = $this->getRequest()->getPost('customer_id');

		if (!$this->hasRightToEdit($editCustomerID)) {
			$this->_getSession()->addError($this->__('You have no right to edit this account!'));
			$this->_redirect('*/*/');
			return;
		}

		$session->setEscapeMessages(true);

		if ($this->getRequest()->isPost()) {
			$errors = array();

			$customer = Mage::getModel('customer/customer')->load($editCustomerID);

			if (is_null($this->getRequest()->getPost('group_id'))) {
				$groupId = Mage::getStoreConfig('schrack/shop/inactive_contact_group');
			} else {
				$groupId = $this->getRequest()->getPost('group_id');
			}
			$customer->setGroupId($groupId);

			// check if still one admin user exists after update
			$adminRoleId = Mage::helper('schrack/acl')->getAdminRoleId();
			if ($this->getRequest()->getPost('role') != $adminRoleId && $customer->getSchrackAclRoleId() == $adminRoleId) {
				$contactCollection = $customer->getAccount()->getContacts()->addFieldToFilter('schrack_acl_role_id', array('eq' => (string)$adminRoleId));
				if ($contactCollection->getSize() - 1 <= 0) {
					$this->_getSession()->addError($this->__('You cannot delete the last administrator for this company!'));
					$this->_redirect('*/*/edit/id/'.$customer->getId());
					return;
				}
			}

			$customer->setSchrackAclRoleId($this->getRequest()->getPost('role'));

			// where to save the email?
			if ($this->getRequest()->getPost('email')) {
				$customer->setEmail($this->getRequest()->getPost('email'));
			}

			if ($this->getRequest()->getPost('password')) {
				$customer->setPassword($this->getRequest()->getPost('password'));
			}

			$customer->save();

			$this->_getSession()->addSuccess($this->__('Account saved!'));
			$this->_redirect('*/*/');
			return;
		}

		$this->_redirect('*/*/');
	}

	public function editPostAction() {
		$editCustomerID = $this->getRequest()->getPost('customer_id');
		if (!$this->_validateFormKey()) {
		    Mage::log('Wrong Form Key / editCustomerID = ' . $editCustomerID, null, 'editPostActionErrors.log');
			$this->_redirect('*/*/edit/id/'.$editCustomerID);
			return;
		}

		if ($this->getRequest()->isPost()) {
			$customer = Mage::getModel('customer/customer')->load($editCustomerID);

			$data = $this->getRequest()->getPost();

			// Only allow role-id = 12, in case of being special projectant:
            if($customer->getSchrackAclRole() == 'list_price_customer') {
                $data['role'] = 12;
            }

            $dataTemp = array();
            foreach($data as $key => $value) {
                $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                $dataTemp[$key] = $value;
            }
            $data = array();
            $data = $dataTemp;

            if (isset($data['schrack_salutatory'])) {
                $data['schrack_salutatory'] = str_replace(' undefined', '', $data['schrack_salutatory']);
            }
            
			$errors = array();
			$customerHelper = Mage::helper('customer');

			// validation start
			if (!Zend_Validate::is(trim($data['lastname']), 'NotEmpty')) {
				$errors[] = $customerHelper->__('The last name cannot be empty.');
			}

			$entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
			$attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'gender');
			if ($attribute->getIsRequired() && '' == trim($data['gender'])) {
				$errors[] = $customerHelper->__('Gender is required.');
			}
			$errors = array_merge($errors, Mage::helper('schrackcustomer/phone')->validatePhonenumbers($data));
			// validation end

			if (!empty($errors)) {
                Mage::log($errors, null, 'editPostActionErrors.log');
				$this->_getSession()->setCustomerFormData($data);
				foreach ($errors as $message) {
					$this->_getSession()->addError($message);
				}
				$this->_redirect('*/*/edit/id/'.$editCustomerID);
				return;
			}

			// check if still one admin user exists after update
			if ($this->_isLastAdmin($editCustomerID)) {
				$this->_getSession()->addError($this->__('You cannot delete the last administrator for this company!'));
				$this->_redirect('*/*/edit/id/'.$editCustomerID);
				return;
			}

			// may only happen for inactive customers without an email address in the CRM
			if (isset($data['schrack_new_email']) && $data['schrack_new_email'] && !$customer->getEmailAddress()) {
				$customer->setSchrackEmails($data['schrack_new_email']);  // real shop email address is still inactive+custno+cno@live.schrack.com
			}

            if (isset($data['prefix']))	            $customer->setPrefix($data['prefix']);
            if (isset($data['firstname']))	        $customer->setFirstname($data['firstname']);
            if (isset($data['schrack_salutatory']))	$customer->setSchrackSalutatory($data['schrack_salutatory']);
            if (isset($data['lastname']))	        $customer->setLastname($data['lastname']);
            if (isset($data['gender']))	            $customer->setGender($data['gender']);
            if (isset($data['role']))	            $customer->setSchrackAclRoleId($data['role']);

			Mage::helper('schrackcustomer/phone')->setPhoneNumbers($data, $customer);

			try {
                Mage::getSingleton('core/session')->setUserModificationAction('contact changed');
                Mage::log('No Errors Detected on editCustomerID = ' . $editCustomerID, null, 'editPostActionErrors.log');
				$customer->save();
				$this->_getSession()->addSuccess($this->__('Account information has been saved successfully'));

				$this->_redirect('*/*/');
				return;
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Can\'t save account'));
				$this->_redirect('*/*/new/');
				return;
			}
		}

		$this->_redirect('*/*/edit/id/'.$editCustomerID);
	}

	public function deleteAction() {
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$deleteCustomerID = Mage::app()->getRequest()->getParam('id');

		if (!$this->hasRightToEdit($deleteCustomerID)) {
			$this->_getSession()->addError($this->__('You have no right to edit this account!'));
			$this->_redirect('*/*/');
			return;
		}

		if ($this->_isLastAdmin($deleteCustomerID)) {
			$this->_getSession()->addError($this->__('You cannot delete the last administrator for this company!'));
			$this->_redirect('*/*/');
			return;
		}

		$customer = Mage::getModel('customer/customer')->load($deleteCustomerID);
		if (!$customer->isContact() && !$customer->isInactiveContact() && !$customer->isProspect()) {
			Mage::exception('Schracklive_SchrackCustomer', 'Customer is not a contact/prospect.');
		}
		try {
			if ($customer->isProspect()) {
				$customer->delete();
			} else {
				Mage::helper('schrackcustomer')->deleteContact($customer);
				$customer->save();
			}
			$this->_getSession()->addSuccess($this->__('Account has been deleted successfully'));
			$this->_redirect('*/*/');
			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addException($e, $this->__('Can\'t delete account'));
		}

		$this->_redirect('*/*/');
	}

	protected function _isLastAdmin($deleteCustomerID) {
		$customer = Mage::getModel('customer/customer')->load($deleteCustomerID);
		$adminRoleId = Mage::helper('schrack/acl')->getAdminRoleId();
		if ($this->getRequest()->getPost('role') != $adminRoleId && $customer->getSchrackAclRoleId() == $adminRoleId) {
			$contactCollection = $customer->getAccount()
					->getContacts()
					->addFieldToFilter('schrack_acl_role_id', array('eq' => (string)$adminRoleId));
			if ($contactCollection->getSize() - 1 <= 0) {
				return true;
			}
		}
	}

	public function deactivateAction() {
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$customerId = Mage::app()->getRequest()->getParam('id');

		if (!$this->hasRightToEdit($customerId)) {
			$this->_getSession()->addError($this->__('You have no right to edit this account!'));
			$this->_redirect('*/*/');
			return;
		}

		if ($this->_isLastAdmin($customerId)) {
			$this->_getSession()->addError($this->__('You cannot deactivate the last administrator for this company!'));
			$this->_redirect('*/*/');
			return;
		}

		$customer = Mage::getModel('customer/customer')->load($customerId);
		/* @var $customer Schracklive_SchrackCustomer_Model_Customer */
		$customer = Mage::getModel('customer/customer')->load($customerId);
		if (!$customer->isContact()) {
			Mage::exception('Schracklive_SchrackCustomer', 'Customer is not an active contact.');
		}

		Mage::helper('schrackcustomer')->deactivateContact($customer);
		try {
            Mage::getSingleton('core/session')->setUserModificationAction('contact deactivated');
			$customer->save();
			$this->_getSession()->addSuccess($this->__('Account has been deactivated successfully'));
			$this->_redirect('*/*/');
			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addException($e, $this->__('Can\'t deactivate account'));
		}

		$this->_redirect('*/*/');
	}

	public function activateAction() {
		// Check login of user (security):
		if (!$this->_getSession()->isLoggedIn()) {
			$this->_redirect('*/account/login');
			return;
		}

		$customerId = Mage::app()->getRequest()->getParam('id');

		if (!$this->hasRightToEdit($customerId)) {
			$this->_getSession()->addError($this->__('You have no right to edit this account!'));
			$this->_redirect('*/*/');
			return;
		}

		// @var $customer Schracklive_SchrackCustomer_Model_Customer:
		$customer = Mage::getModel('customer/customer')->load($customerId);
		if (!$customer->isInactiveContact()) {
			Mage::exception('Schracklive_SchrackCustomer', 'Customer is not an inactive contact.');
		}

		// Check, if custoer email already exists as prospect:
        $emails = explode(',', $customer->getSchrackEmails());
        $deactivatedContactEmail = array_shift($emails);

        // Init customer object:
        $possibleProspect = Mage::getModel('customer/customer');
        $possibleProspect->loadByEmail($deactivatedContactEmail);

        // Check, if already existing:
        if ($possibleProspect && $possibleProspect->getId()) {
            // There is email already existing (this is occupied from prospect with 99% probability):
            $this->_getSession()->addError($this->__('Contact Already Exists As Prospect'));
        } else {
            Mage::helper('schrackcustomer')->activateContact($customer);
            try {
                $newPass = $customer->generatePassword();
                $customer->setPassword($newPass);
                $customer->setConfirmation($newPass);
                Mage::getSingleton('core/session')->setUserModificationAction('contact activated');
                $customer->save();
                if ($customer->isConfirmationRequired()) {
                    $customer->sendNewAccountEmail('confirmation');
                }
                $this->_getSession()->addSuccess($this->__('Account has been activated successfully'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Can\'t activate account'));
            }
        }

		$this->_redirect('*/*/');
	}

	protected function hasRightToEdit($editCustomerID) {
		$customerModel = $this->_getSession()->getCustomer();
		$customerToEdit = Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('entity_id', $editCustomerID)->setAccountIdFilter($customerModel->getSchrackAccountId());

		if ($customerToEdit->count() && $customerModel->isAllowed('accessRight', 'edit')) {
			return true;
		} else {
			return false;
		}
	}

}

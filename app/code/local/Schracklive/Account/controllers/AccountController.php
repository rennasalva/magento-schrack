<?php

class Schracklive_Account_AccountController extends Mage_Core_Controller_Front_Action {

	/**
	 * Retrieve customer session model object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getSession() {
		return Mage::getSingleton('customer/session');
	}

	protected function _getAccount() {
		return $this->_getSession()->getCustomer()->getAccount();
	}

	/**
	 * Action predispatch
	 *
	 * Check customer authentication for all actions
	 */
	public function preDispatch() {
		// a brute-force protection here would be nice
		parent::preDispatch();

		if (!$this->getRequest()->isDispatched()) {
			return;
		}

		if (!$this->_getSession()->authenticate($this)) {
			$this->setFlag('', 'no-dispatch', true);
		}
	}

	public function indexAction() {
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('catalog/session');

		$this->getLayout()->getBlock('head')->setTitle($this->__('My Company'));

		$this->renderLayout();
	}

	public function editAction() {
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
		$this->_initLayoutMessages('catalog/session');

		if ($block = $this->getLayout()->getBlock('account_account_edit')) {
			$block->setRefererUrl($this->_getRefererUrl());
		}

		$data = $this->_getSession()->getAccountFormData(true);
		$account = $this->_getAccount();
		if (!empty($data)) {
			$account->addData($data);
		}

		$this->getLayout()->getBlock('head')->setTitle($this->__('Company Information'));

		$this->renderLayout();
	}

	public function editPostAction() {
		if (!$this->_validateFormKey()) {
			return $this->_redirect('*/*/edit');
		}

		if ($this->getRequest()->isPost()) {
			$templateId = Mage::getStoreConfig('schrack/customer/changeCompanyInfo');
			$customer = $this->_getSession()->getCustomer();
			$receiverEmail = Mage::getStoreConfig('trans_email/ident_custom2/email');
			$receiverName = Mage::getStoreConfig('trans_email/ident_custom2/name');

			try {
				if (is_null($templateId) || trim($receiverEmail) == '') {
					Mage::exception('Schracklive_Account', "Template-Id #{$templateId} or receiver email <{$receiverEmail}> missing.");
				}
				$emailTemplateVariables = $this->getRequest()->getPost();
				$emailTemplateVariables['username'] = $customer->getFirstname().' '.$customer->getLastname();
				$emailTemplateVariables['useremail'] = $customer->getEmail();
				$emailTemplateVariables['wwscustomerid'] = $customer->getSchrackWwsCustomerId();

				$senderName = Mage::getStoreConfig('general/store_information/name');
				$senderMail = 'noreply@schrack.com';
				$sender = array('name' => $senderName, 'email' => $senderMail);
				$subject = $this->__('Company data change requested');

				if (!Mage::getModel('core/email_template')
						->setTemplateSubject($subject)
						->sendTransactional($templateId, $sender, $receiverEmail, $receiverName, $emailTemplateVariables)) {
					Mage::exception('Schracklive_Account', $this->__('Email could not be sent.'));
				}

				$this->_getSession()->addSuccess($this->__('Your request for data change will be processed soon.'));
				$this->_redirect('customer/account');
				return;
			} catch (Exception $e) {
				Mage::logException($e);
				$this->_getSession()->addError($this->__('An error occured! Please contact your sales representative.'));
				$this->_redirect('customer/account');
				return;
			}

			$this->_redirect('*/*/edit');
		}
	}

	public function _OLD_editPostAction() {
		if (!$this->_validateFormKey()) {
			return $this->_redirect('*/*/edit');
		}

		if ($this->getRequest()->isPost()) {
			$account = Mage::getModel('account/account')
					->setId($this->_getAccount()->getId());

			$fields = Mage::getConfig()->getFieldset('account_account');
			$data = $this->getRequest()->getPost();

			foreach ($fields as $code => $node) {
				if ($node->is('update') && isset($data[$code])) {
					$account->setData($code, $data[$code]);
				}
			}

			$errors = $account->validate();
			if (!is_array($errors)) {
				$errors = array();
			}

			if (!empty($errors)) {
				$this->_getSession()->setAccountFormData($this->getRequest()->getPost());
				foreach ($errors as $message) {
					$this->_getSession()->addError($message);
				}
				$this->_redirect('*/*/edit');
				return $this;
			}

			try {
				$account->save();
				$this->_getSession()->getCustomer()->setAccount($account);
				$this->_getSession()->addSuccess($this->__('Company information was successfully saved'));

				$this->_redirect('customer/account');
				return;
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->setAccountFormData($this->getRequest()->getPost())
						->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->setAccountFormData($this->getRequest()->getPost())
						->addException($e, $this->__('Can\'t save company').'<br/><i>'.$e->getMessage().'</i>');
			}
		}

		$this->_redirect('*/*/edit');
	}

}

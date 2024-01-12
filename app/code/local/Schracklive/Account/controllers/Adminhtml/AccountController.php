<?php

class Schracklive_Account_Adminhtml_AccountController extends Mage_Adminhtml_Controller_Action {

	protected function _initAction() {
		$this->loadLayout()
				->_setActiveMenu('customer/account')
				->_addBreadcrumb(Mage::helper('adminhtml')->__('Account Manager'), Mage::helper('adminhtml')->__('Account Manager'));
		return $this;
	}

	public function indexAction() {
		$this->_initAction();
		$this->_addContent($this->getLayout()->createBlock('account/adminhtml_account'));
		$this->renderLayout();
	}

    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('account/adminhtml_account_grid')->toHtml()
        );
    }

    public function editAction() {
		$id = $this->getRequest()->getParam('id');
		$account = Mage::getModel('account/account')->load($id);

		if ($account->getId() || $id == 0) {
			$this->_initAction();

			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$account->setData($data);
			}
			Mage::register('account_account_data', $account);

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('account/adminhtml_account_edit'))
					->_addLeft($this->getLayout()->createBlock('account/adminhtml_account_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('account')->__('Account does not exist'));
			$this->_redirect('*/*/');
		}
	}

	public function newAction() {
		$this->_forward('edit');
	}

	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			$account = Mage::getModel('account/account');
			$account->setData($data)
					->setId($this->getRequest()->getParam('id'));

			$helper = Mage::helper('account/protobuf');

			try {
				$isNewAccount = false;
				if (!$account->getId()) {
					$isNewAccount = true;
					$systemContact = Mage::getModel('customer/customer');
					Mage::helper('schrackcustomer')->setupSystemContact($systemContact, $account);
				}
				$account->save();
				if ($isNewAccount) {
					$systemContact->save();

					$address = Mage::getModel('customer/address');
					Mage::helper('schrackcustomer')->setupBillingAddress($address, $systemContact->getId());
					$address->save();

					$systemContact->setData('default_billing', $address->getId());
					$systemContact->setData('default_shipping', $address->getId());
					$systemContact->save();
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('account')->__('Account was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $account->getId()));
					return;
				}
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('account')->__('Unable to find account to save'));
			$this->_redirect('*/*/');
		}
	}

	public function deleteAction() {
        throw new Exception('Delete of accounts is not supportet!');
	}

	// This is a necessary ACL-adaption for the security update SUPEE-6285:
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('customer/account');
	}
}

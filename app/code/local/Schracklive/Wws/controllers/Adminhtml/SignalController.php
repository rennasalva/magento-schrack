<?php

class Schracklive_Wws_Adminhtml_SignalController extends Mage_Adminhtml_Controller_action {

	protected function _initAction() {
		$this->loadLayout()
				->_setActiveMenu('wws/signal')
				->_addBreadcrumb(Mage::helper('adminhtml')->__('WWS Message(s) Manager'), Mage::helper('adminhtml')->__('WWS Message Manager'));

		return $this;
	}

	public function indexAction() {
		$this->_initAction();
		$this->_addContent($this->getLayout()->createBlock('wws/adminhtml_signal'));
		$this->renderLayout();
	}

	public function editAction() {
		$id = $this->getRequest()->getParam('id');
		$model = Mage::getModel('wws/signal')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('signal_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('wws/signal');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('WWS Message Manager'), Mage::helper('adminhtml')->__('WWS Message Manager'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('wws/adminhtml_signal_edit'))
					->_addLeft($this->getLayout()->createBlock('wws/adminhtml_signal_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wws')->__('WWS Message does not exist'));
			$this->_redirect('*/*/');
		}
	}

	public function newAction() {
		$this->_forward('edit');
	}

	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {

			$model = Mage::getModel('wws/signal');
			$model->setData($data)
					->setId($this->getRequest()->getParam('id'));

			try {
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('wws')->__('WWS Message was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		}
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('wws')->__('Unable to find WWS Message to save'));
		$this->_redirect('*/*/');
	}

	public function deleteAction() {
		if ($this->getRequest()->getParam('id') > 0) {
			try {
				$model = Mage::getModel('wws/signal');

				$model->setId($this->getRequest()->getParam('id'))
						->delete();

				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('WWS Message was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

	public function massDeleteAction() {
		$signalIds = $this->getRequest()->getParam('signal');
		if (!is_array($signalIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select WWS Message(s)'));
		} else {
			try {
				foreach ($signalIds as $signalId) {
					$signal = Mage::getModel('wws/signal')->load($signalId);
					$signal->delete();
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(
						Mage::helper('adminhtml')->__(
								'Total of %d Message(s) were successfully deleted', count($signalIds)
						)
				);
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	public function massStatusAction() {
		$signalIds = $this->getRequest()->getParam('signal');
		if (!is_array($signalIds)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Please select WWS Message(s)'));
		} else {
			try {
				foreach ($signalIds as $signalId) {
					$signal = Mage::getSingleton('wws/signal')
									->load($signalId)
									->setStatus($this->getRequest()->getParam('status'))
									->setIsMassupdate(true)
									->save();
				}
				$this->_getSession()->addSuccess(
						$this->__('Total of %d WWS Message(s) were successfully updated', count($signalIds))
				);
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}

	// This is a necessary ACL-adaption for the security update SUPEE-6285:
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('system/wws_signal');
	}
}

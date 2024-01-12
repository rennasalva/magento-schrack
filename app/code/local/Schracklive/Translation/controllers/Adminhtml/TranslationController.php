<?php

class Schracklive_Translation_Adminhtml_TranslationController extends Mage_Adminhtml_Controller_action {

	protected function _initAction() {
		$this->loadLayout()
				->_setActiveMenu('translation/items')
				->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}

	public function indexAction() {
		$this->_initAction();
		// $this->_addContent($this->getLayout()->createBlock('translation/adminhtml_translation'));
		Mage::getSingleton('adminhtml/session')->addError("Translations are not longer edited here. Please use STS translation functionallity!");
		$this->renderLayout();
		$this->_redirect('*/*/dummy');
	}

	public function dummyAction() {
		$this->_initAction();
		// Mage::getSingleton('adminhtml/session')->addError("Translations are not longer edited here. Please use STS translation functionallity!");
		$this->renderLayout();
	}

	public function editAction() {
		$id = $this->getRequest()->getParam('id');
		$model = Mage::getModel('translation/translation')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('translation_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('translation/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('translation/adminhtml_translation_edit'))
					->_addLeft($this->getLayout()->createBlock('translation/adminhtml_translation_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('translation')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}

	public function newAction() {
		$this->_forward('edit');
	}

	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {

			$model = Mage::getModel('translation/translation')->load($this->getRequest()->getParam('id'));
			$translationOld = $model->getStringTranslated();
			$model->setData($data)
					->setId($this->getRequest()->getParam('id'));
			$translationNew = $model->getStringTranslated();
			if ($translationOld == $translationNew) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('translation')->__('Nothing was changed'));
				$this->_redirect('*/*/');
				return;
			}

			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
							->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}
				$model->setIsLocal(1);
				$model->setIsChanged(1);
				$model->setIsTranslated(Schracklive_Translation_Model_Status::STATUS_TRANSLATED);

				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('translation')->__('Item was successfully saved'));
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
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper('translation')->__('Unable to find item to save'));
		$this->_redirect('*/*/');
	}

	public function deleteAction() {
		if ($this->getRequest()->getParam('id') > 0) {
			try {
				$model = Mage::getModel('translation/translation');

				$model->setId($this->getRequest()->getParam('id'))
						->delete();

				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

	/**
	 * Export translation grid to CSV format
	 */
	public function exportCsvAction() {
		$fileName = 'translation.csv';
		$content = $this->getLayout()->createBlock('translation/adminhtml_translation_grid')->getCsvFile();

		$this->_prepareDownloadResponse($fileName, $content);
	}

	public function loadFilesAction() {
		$userId = Mage::getSingleton('admin/session')->getUser()->getId();

		Mage::helper('translation')->updateFromSvn();
		Mage::helper('translation')->loadAllFiles();

		$this->_getSession()->addSuccess($this->__('Files loaded'));
		$this->_redirect('*/*/index');
	}

	public function saveFilesAction() {
		// First check SVN status
		if (Mage::helper('translation')->updateFromSvn()) {
			Mage::helper('translation')->loadAllFiles();
		}
		// Find changed strings and modules
		$translationCollection = Mage::getModel('translation/translation')
						->getCollection()
						->addFieldToFilter('is_changed', array('eq' => '1'))
						->load();
		$changedFiles = array();
		foreach ($translationCollection as $translation) {
			if (!isset($changedFiles[$translation->getLocale()])) {
				$changedFiles[$translation->getLocale()] = array();
			}
			$changedFiles[$translation->getLocale()][$translation->getFile()] = $translation->getFile();
		}

		foreach ($changedFiles as $locale => $files) {
			foreach ($files as $file) {
				$translationCollection = Mage::getModel('translation/translation')
								->getCollection()
								->addFieldToFilter('locale', array('eq' => $locale))
								->addFieldToFilter('file', array('eq' => $file))
								->addFieldToFilter('is_local', array('eq' => '1'))
								->setOrder('string_en', Varien_Data_Collection::SORT_ORDER_ASC)
								->load();
				$fp = fopen($this->_getTranslationFilePath('local'.DS.$file, $locale), 'w');
				foreach ($translationCollection as $translation) {
					$stringEn = '"'.str_replace('"', '""', $translation->getStringEn()).'"';
					$stringTranslated = '"'.str_replace('"', '""', $translation->getStringTranslated()).'"';
					fwrite($fp, $stringEn.','.$stringTranslated."\n");
					if ($translation->getIsChanged() === "1") {
						$translation->setIsChanged(0)->save();
					}
				}
				fclose($fp);
				// Always add changed files, could be a new one
				svn_add($this->_getTranslationFilePath('local'.DS.$file, $locale));
			}
			$this->_getSession()->addSuccess($this->__('Files saved for locale %s', $locale));
			try {
				svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_USERNAME, Mage::getStoreConfig('schrack/translation/username'));
				svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_PASSWORD, Mage::getStoreConfig('schrack/translation/password'));
				$svnResponse = svn_commit('Magento Backend Translation by '.Mage::getSingleton('admin/session')->getUser()->getUsername(), array(realpath($this->_getTranslationFilePath('local', $locale))));
				if ($svnResponse[0] != -1) {
					$this->_getSession()->addSuccess($this->__('Files committed for locale %s', $locale));
				} else {
					$this->_getSession()->addNotice($this->__('No files changed for locale %s', $locale));
				}
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			}
		}
		if (count($changedFiles) > 0) {
			Mage::app()->cleanCache(Mage_Core_Model_Translate::CACHE_TAG);
			$this->_getSession()->addSuccess($this->__('Cache cleared'));
		} else {
			$this->_getSession()->addNotice($this->__('No strings changed'));
		}
		$this->_redirect('*/*/index');
	}

	protected function _getTranslationFilePath($fileName, $locale) {
		$file = Mage::getBaseDir('locale');
		$file.= DS.$locale.DS.$fileName;
		return $file;
	}

	// This is a necessary ACL-adaption for the security update SUPEE-6285:
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('system/translation');
	}
}

?>
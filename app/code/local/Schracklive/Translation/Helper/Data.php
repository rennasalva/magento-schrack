<?php

class Schracklive_Translation_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getTranslatableLocales() {
		return explode(',', Mage::getStoreConfig('schrack/translation/codes', Mage::getStoreConfig('schrack/shop/store')));
	}
	
	public function updateFromSvn() {
		$locales = $this->getTranslatableLocales();
		$clearCache = false;
		$reload = false;
		// First check SVN status
		svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_USERNAME, Mage::getStoreConfig('schrack/translation/username'));
		svn_auth_set_parameter(SVN_AUTH_PARAM_DEFAULT_PASSWORD, Mage::getStoreConfig('schrack/translation/password'));
		try {
			$changedFilesEn = svn_status(realpath($this->_getTranslationFilePath('local', Mage_Core_Model_Locale::DEFAULT_LOCALE)), SVN_SHOW_UPDATES);
		} catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			$changedFilesEn = array();
		}
		if ($changedFilesEn === false || !is_array($changedFilesEn)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('translation')->__('Could not contact versioning server for locale %s', Mage_Core_Model_Locale::DEFAULT_LOCALE));
			return false;
		} elseif (count($changedFilesEn) > 0) {
			svn_update(realpath($this->_getTranslationFilePath('local', Mage_Core_Model_Locale::DEFAULT_LOCALE)));
			$clearCache = true;
		}
		foreach ($locales as $locale) {
			$updateSvn = false;
			$changedFilesTranslation = svn_status(realpath($this->_getTranslationFilePath('local', $locale)), SVN_SHOW_UPDATES);
			// Update from SVN und reload DB info if changes found
			if (count($changedFilesTranslation) > 0) {
				foreach ($changedFilesTranslation as $translationFile) {
					if ($translationFile['text_status'] == SVN_WC_STATUS_UNVERSIONED) {
						svn_add($translationFile['path']);
					} elseif ($translationFile['repos_text_status'] == SVN_WC_STATUS_MODIFIED) {
						$updateSvn = true;
					}
				}
				if ($updateSvn) {
					svn_update(realpath($this->_getTranslationFilePath('local', $locale)));
					$clearCache = true;
				}
			}
		}
		if ($clearCache) {
			Mage::app()->cleanCache(Mage_Core_Model_Translate::CACHE_TAG);
			return true;
		}
		return false;
	}

	public function loadAllFiles($userId=null) {
		$translateEn = Mage::getModel('core/translate')->setLocale(Mage_Core_Model_Locale::DEFAULT_LOCALE)->init('frontend', true);
		$time = now();
		$dbTranslations = array();
		$fileTranslations = array();
		// Load and map current translation DB
		$translationCollection = Mage::getModel('translation/translation')
						->getCollection()
						->load();
		foreach ($translationCollection as $translation) {
			$dbTranslations[$translation->getModuleName()][$translation->getStringEn()][$translation->getLocale()] = $this->_createdDbTranslationNode($translation);
			unset($translation);
		}
		unset($translationCollection);

		// Iterate over all modules
		$fileTranslations = $this->_loadFileTranslations($translateEn, $fileTranslations);
		$locales = $this->getTranslatableLocales();
		foreach ($locales as $locale) {
			$translate = Mage::getModel('core/translate')->setLocale($locale)->init('frontend', true);
			$fileTranslations = $this->_loadFileTranslations($translate, $fileTranslations, Schracklive_Translation_Model_Status::STATUS_TRANSLATED);
		}

		foreach ($fileTranslations as $moduleName => $fileTranslation) {
			foreach ($fileTranslation as $originalString => $translations) {
				foreach ($locales as $locale) {
					// Check if it is in the DB and changed
					$task = 'add';
					// Copy en_US default string if no local variant found (but only if it exists in default locale)
					if (!isset($translations[$locale])) {
						if (isset($translations[Mage_Core_Model_Locale::DEFAULT_LOCALE])) {
							$translations[$locale] = $translations[Mage_Core_Model_Locale::DEFAULT_LOCALE];
						} else {
							// We got an rogue string not matching the default locale (en_US) file from another locale, ignore it
							$task = 'skip';
						}
					}
					if (isset($dbTranslations[$moduleName][$originalString][$locale])) {
						if (isset($translations[$locale])
							&& ($dbTranslations[$moduleName][$originalString][$locale]['string_translated'] != $translations[$locale]['string_translated']
								|| ($dbTranslations[$moduleName][$originalString][$locale]['is_local'] != $translations[$locale]['is_local']))
								&& $dbTranslations[$moduleName][$originalString][$locale]['is_changed'] != '1') {
							$task = 'update';
						} else {
							$task = 'skip';
						}
					}
					if (!isset($translations[Mage_Core_Model_Locale::DEFAULT_LOCALE])) {

					}
					if ($task == 'add') {
						$newRow = array(
							'user_id' => $userId,
							'module_name' => $moduleName,
							'file' => $translations[$locale]['file'],
							'string_en' => $originalString,
							'string_translated' => $translations[$locale]['string_translated'],
							'locale' => $locale,
							'is_local' => $translations[$locale]['is_local'],
							'is_changed' => $translations[$locale]['is_changed'],
							'is_translated' => $translations[$locale]['is_translated'],
							'created_time' => $time,
							'update_time' => $time,
							'is_orphaned' => (isset($translations[Mage_Core_Model_Locale::DEFAULT_LOCALE]) ? 0 : 1),
						);
						$dbTranslation = Mage::getModel('translation/translation')->setData($newRow)->save();
						$dbTranslations[$moduleName][$originalString] = $this->_createdDbTranslationNode($dbTranslation);
					} elseif ($task == 'update') {
						if (isset($dbTranslations[$moduleName][$originalString]['translation_id'])) {
							$dbTranslation = Mage::getModel('translation/translation')->load($dbTranslations[$moduleName][$originalString]['translation_id']);
							$dbTranslation->setFile($translations[$locale]['file']);
							$dbTranslation->setUserId($userId);
							$dbTranslation->setIsLocal($translations[$locale]['is_local']);
							$dbTranslation->setStringTranslated($translations[$locale]['string_translated']);
							$dbTranslation->setUpdateTime($time);
							$dbTranslation->save();
							$dbTranslations[$moduleName][$originalString] = $this->_createdDbTranslationNode($dbTranslation);
						}
					}
				}
				if (isset($dbTranslations[$moduleName]) && isset($dbTranslations[$moduleName][$originalString])) {
					unset($dbTranslations[$moduleName][$originalString]);
				}
			}
			foreach ($dbTranslations[$moduleName] as $deleteTranslation) {
				if (isset($deleteTranslation['is_changed']) && $deleteTranslation['is_changed'] !== '1') {
					Mage::getModel('translation/translation')->load($deleteTranslation['translation_id'])->delete();
				}
			}
		}
	}

	protected function _createdDbTranslationNode($translation) {
		return array(
			'translation_id' => $translation->getTranslationId(),
			'string_translated' => $translation->getStringTranslated(),
			'is_local' => $translation->getIsLocal(),
			'is_changed' => $translation->getIsChanged(),
			'is_translated' => $translation->getIsTranslated(),
		);
	}

	protected function _loadFileTranslations($translate, $fileTranslations, $is_translated = Schracklive_Translation_Model_Status::STATUS_ORIGINAL) {
		foreach ($translate->getModulesConfig() as $moduleName => $info) {
			if (!Mage::getConfig()->getModuleConfig($moduleName)->is('active', 'true')) {
				continue;
			}
			$info = $info->asArray();
			// Load and map current translation files
			foreach ($info['files'] as $file) {
				$file = $translate->getModuleFilePath($moduleName, $file);
				$strings = $translate->getFileData($file);
				$path = explode(DS, $file);
				// Windows system or DS set wrong, handle
				if (strpos($path[count($path) - 1], 'local') === 0) {
					$tmpPath = explode('/', $path[count($path) - 1]);
					$path[count($path) - 1] = $tmpPath[0];
					$path[] = $tmpPath[1];
				}
				if (strpos($path[count($path) - 2], 'local') === 0) {
					$is_local = 1;
				} else {
					$is_local = 0;
				}
				$file = $path[count($path) - 1];
				foreach ($strings as $original => $string_translated) {
					if (!isset($fileTranslations[$moduleName][$original][$translate->getLocale()])
						|| $is_local === 1
						|| $fileTranslations[$moduleName][$original][$translate->getLocale()]['is_translated'] == Schracklive_Translation_Model_Status::STATUS_ORIGINAL) {
						$fileTranslations[$moduleName][$original][$translate->getLocale()] = array(
							'file' => $file,
							'is_local' => $is_local,
							'is_changed' => 0,
							'is_translated' => $is_translated,
							'string_translated' => $string_translated,
						);
					}
				}
			}
		}
		return $fileTranslations;
	}
	
	protected function _getTranslationFilePath($fileName, $locale) {
		$file = Mage::getBaseDir('locale');
		$file.= DS.$locale.DS.$fileName;
		return $file;
	}

}

?>
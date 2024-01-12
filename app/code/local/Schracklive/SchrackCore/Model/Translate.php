<?php

class Schracklive_SchrackCore_Model_Translate extends Mage_Core_Model_Translate {

	/**
	 * Loading data from module translation files
	 *
	 * @param   string $moduleName
	 * @param   array $files
	 * @param bool     $forceReload
	 * @return  Mage_Core_Model_Translate
	 */
	protected function _loadModuleTranslation($moduleName, $files, $forceReload=false) {
		foreach ($files as $file) {
			$file = $this->_getModuleFilePath($moduleName, $file);
			$path = explode(DS, $file);
			if ($path[count($path)-2] == 'local') {
				$this->_addData($this->_getFileData($file), $moduleName, true);
			} else {
				$this->_addData($this->_getFileData($file), $moduleName, $forceReload);
			}
		}
		return $this;
	}

	/**
	 * Adding translation data
	 *
	 * @param array  $data
	 * @param string $scope
	 * @param bool   $forceReload
	 * @return Schracklive_SchrackCore_Model_Translate
	 */
	protected function _addData($data, $scope, $forceReload=false) {
		foreach ($data as $key => $value) {
			if ($key === $value) {
				continue;
			}
			$key = $this->_prepareDataString($key);
			$value = $this->_prepareDataString($value);
			if ($scope && isset($this->_dataScope[$key]) && !$forceReload) {
				/**
				 * Checking previos value
				 */
				$scopeKey = $this->_dataScope[$key] . self::SCOPE_SEPARATOR . $key;
				if (!isset($this->_data[$scopeKey])) {
					if (isset($this->_data[$key])) {
						$this->_data[$scopeKey] = $this->_data[$key];
					}
				}
				$scopeKey = $scope . self::SCOPE_SEPARATOR . $key;
				$this->_data[$scopeKey] = $value;
			} else {
				$scopeKey = $scope . self::SCOPE_SEPARATOR . $key;
				$this->_data[$scopeKey] = $value;
				$this->_data[$key] = $value;
				$this->_dataScope[$key] = $scope;
			}
		}
		return $this;
	}

	public function getModuleFilePath($module, $fileName) {
		return parent::_getModuleFilePath($module, $fileName);
	}

	public function getFileData($file) {
		return parent::_getFileData($file);
	}

}

?>

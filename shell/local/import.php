<?php

require_once 'shell.php';

class Schracklive_Shell_Import extends Schracklive_Shell {

	public function run() {
		if ($this->_getParametrizedArg('profile-id')) {
			try {
				$profile = $this->_getById($this->getArg('profile-id'));
				$this->_applyArguments($profile);
				$this->_verify($profile);
				$this->_parse($profile);
				$this->_import($profile);
			} catch (Exception $e) {
				echo $e->getMessage(), "\n";
			}
		} else {
			echo $this->usageHelp();
		}
	}

	protected function _getParametrizedArg($name) {
		$argument = $this->getArg($name);
		if ($argument && gettype($argument == 'string')) {
			return $argument;
		}
		return false;
	}

	protected function _getById($profileId) {
		$profile = Mage::getModel('dataflow/profile');
		$profile->load($profileId);
		if (!$profile->getId()) {
			throw new Exception('Profile not found.');
		}
		return $profile;
	}

	protected function _applyArguments(Mage_Dataflow_Model_Profile $profile) {
		if ($this->getArg('data-transfer-server')) {
			// this MUST be set prior to calling _parseGuiData()
			$profile->setDataTransfer('file');
		}

		$guiData = $profile->getGuiData();
		if ($this->_getParametrizedArg('path')) {
			$guiData['file']['path'] = $this->getArg('path');
		}
		if ($this->_getParametrizedArg('filename')) {
			$guiData['file']['filename'] = $this->getArg('filename');
		}
		$profile->setGuiData($guiData);
		$profile->_parseGuiData();
	}

	protected function _verify(Mage_Dataflow_Model_Profile $profile) {
		if ($profile->getDirection() != 'import') {
			throw new Exception('Not an "import" profile.');
		}
		if ($profile->getDataTransfer() != 'file') {
			throw new Exception('Data transfer is not "local/remote server".');
		}
	}

	protected function _parse(Mage_Dataflow_Model_Profile $profile) {
		$map = array(
			Varien_Convert_Exception::FATAL => 'FATAL',
			Varien_Convert_Exception::ERROR => 'ERROR',
			Varien_Convert_Exception::WARNING => 'WARNING',
			Varien_Convert_Exception::NOTICE => 'NOTICE',
		);

		$xml = '<convert version="1.0"><profile name="default">'.$profile->getActionsXml().'</profile></convert>';
		$profileRunner = Mage::getModel('core/convert')
						->importXml($xml)
						->getProfile('default');
		try {
			$batch = Mage::getSingleton('dataflow/batch')
							->setProfileId($profile->getId())
							->setStoreId($profile->getStoreId())
							->save();
			$profile->setBatchId($batch->getId());

			$profileRunner->setDataflowProfile($profile);
			$profileRunner->run();
		} catch (Exception $e) {
			echo $e;
		}

		foreach ($profileRunner->getExceptions() as $e) {
			echo $map[$e->getLevel()].': '.$e->getMessage()."\n";
		}
	}

	protected function _import(Mage_Dataflow_Model_Profile $profile) {
		$batchModel = Mage::getSingleton('dataflow/batch');
		$batchImportModel = $batchModel->getBatchImportModel();
		$importIds = $batchImportModel->getIdCollection();
		$adapter = Mage::getModel($batchModel->getAdapter());
		$adapter->setBatchParams($batchModel->getParams());

		$errors = array();
		$saved = 0;
		foreach ($importIds as $importId) {
			$batchImportModel->load($importId);
			if (!$batchImportModel->getId()) {
				$errors[] = 'Skip undefined row';
				continue;
			}

			try {
				$importData = $batchImportModel->getBatchData();
				$adapter->saveRow($importData);
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
				continue;
			}
			$saved++;
		}
		try {
			$batchModel->beforeFinish();
		} catch (Mage_Core_Exception $e) {
			$errors[] = $e->getMessage();
		}
		$batchModel->delete();

		foreach ($errors as $error) {
			echo 'ERROR: '.$error."\n";
		}
		echo "NOTICE: Saved ${saved} rows\n";
	}

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f import.php -- [options]

  --profile-id <id>               Show cache info
  --help                          This help

USAGE;
	}

}

$shell = new Schracklive_Shell_Import();
$shell->run();


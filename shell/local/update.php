<?php

require_once('shell.php');

class Schracklive_Shell_Update extends Schracklive_Shell {

	protected function _updateAll() {
		$this->_updateResources();
		$this->_loadTranslations();
	}

	protected function _updateResources() {
		Mage_Core_Model_Resource_Setup::applyAllUpdates();
		Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
	}

	protected function _loadTranslations() {
		// nothing to do any longer, translations will be distributed from sts
	}

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f update.php -- [options]

  all                      Make all updates
  resources                Update resources and data
  translations             Update translations from SVN and import the CSV files
  help                     This help

USAGE;
	}

	public function run() {
		if ($this->getArg('all')) {
			$this->_updateAll();
		} elseif ($this->getArg('resources')) {
			$this->_updateResources();
		} elseif ($this->getArg('translations')) {
			$this->_loadTranslations();
		} else {
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklive_Shell_Update();
$shell->run();

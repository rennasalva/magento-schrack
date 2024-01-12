<?php

/*
  DELETE FROM catalog_product_entity;
  DELETE FROM catalog_category_entity WHERE entity_id > 2;
  DELETE FROM eav_attribute WHERE entity_type_id=4 AND attribute_code LIKE 'schrack\_%';
  DELETE FROM eav_attribute_set WHERE entity_type_id=4 AND attribute_set_name='Schrack';
  DELETE FROM eav_attribute_set WHERE entity_type_id=4 AND attribute_set_name LIKE 'schrack\_%';
 */

require_once 'shell.php';

class Schracklive_Shell_ProductImport extends Schracklive_Shell {

	const TASK_START = 'start';
	const TASK_IMPORT = 'import';
	const TASK_IMPORT_PRODUCTS = 'import_products';
	const TASK_CLEAN = 'clean';
	const PRODUCT_ACTIVE = 'active';
	const PRODUCT_INACTIVE = 'inactive';

	var $_baseCmd;
	var $_statusFile;
	var $_limit = 50000;
	var $_task = self::TASK_START;
	var $_passThroughArgs = array('file', 'idfile', 'status');
	var $_importer;
	var $_importFile;
	var $_idFile;
	var $_productStatus = self::PRODUCT_ACTIVE;

	public function __construct($tempDir = null) {
		parent::__construct();
        
        $modCfg = Mage::getConfig()->getModuleConfig('Schracklive_SchrackCatalog');
        $schrackCatalogVersion = (string) $modCfg->version;
        $schrackCatalogVersionArray = explode('.',$schrackCatalogVersion);
        if ( (int) $schrackCatalogVersionArray[0]  > 1 ) {
            throw new Exception('Version ' . $schrackCatalogVersion . ' of module "Schracklive_SchrackCatalog" is not compatible wit this (older) XML importer! "Schracklive_SchrackCatalog" version must be < 2.0.0 .');
        }
        
        if ( isset($tempDir) ) {
            Mage::getConfig()->getOptions()->setData('tmp_dir',$tempDir);
        }
		if ($this->getArg('file')) {
			$this->_importFile = $this->getArg('file');
		} elseif (!$this->getArg('task') || $this->getArg('task') != self::TASK_CLEAN) {
			echo $this->usageHelp();
			return;
		}
		if ($this->getArg('idfile')) {
			$this->_idFile = $this->getArg('idfile');
		}
		if ($this->getArg('status') && $this->getArg('status') != self::PRODUCT_ACTIVE) {
			$this->_productStatus = self::PRODUCT_INACTIVE;
		}
		$this->_importer = Mage::getModel('schrackcatalog/import');
        if ( ! isset($this->_baseCmd) ) {
            $this->_baseCmd = 'php '.__FILE__;
        }
		foreach ($this->_args as $arg => $val) {
			if (in_array($arg, $this->_passThroughArgs)) {
				$this->_baseCmd .= ' --'.$arg.' '.$val;
			}
		}
		$this->_statusFile = Mage::getConfig()->getOptions()->getTmpDir().DS.'importStatus';
		if ($this->getArg('limit')) {
			$this->_limit = $this->getArg('limit');
		}
		if ($this->getArg('task')) {
			$this->_task = $this->getArg('task');
		}
	}

	public function runStart($resume = false) {
		if (!$resume) {
			// Run import up to products & create serialized data
			$this->exec($this->_baseCmd.' --task '.self::TASK_IMPORT);
		}
		// Run through articles
		if (file_exists("$this->_statusFile") && is_readable("$this->_statusFile")) {
			$status = unserialize(file_get_contents("$this->_statusFile"));
			$importStart = $status['start'];
			echo "starting product import at #".$importStart." of ".$status['count']." in blocks of ".$this->_limit."\n";
			while ($importStart < $status['count']) {
				$this->exec($this->_baseCmd.' --task '.self::TASK_IMPORT_PRODUCTS.' --start '.$importStart.' --limit '.$this->_limit);
				$importStart += $this->_limit;
			}
		} else {
			echo "can't read import status file ".$this->_statusFile;
			exit();
		}
		$this->exec($this->_baseCmd.' --task '.self::TASK_CLEAN);
	}

	public function runImport() {
		$this->_importer->run($this->_importFile, $this->_idFile, $this->_productStatus, 0, 1, self::TASK_IMPORT);
	}

	public function runImportProducts() {
		echo "importing\n";
		// load import status file
		$importStart = 0;
		if ($this->getArg('start')) {
			$importStart = $this->getArg('start');
			echo "starting import at article #".$importStart."\n";
		}
		$this->_importer->run($this->_importFile, $this->_idFile, $this->_productStatus, $importStart, $this->_limit, self::TASK_IMPORT_PRODUCTS);
	}

	public function run() {
		switch ($this->_task) {
			case self::TASK_START:
				if (file_exists($this->_statusFile) && is_readable($this->_statusFile)) {
					// Run resume if status file exists
					$this->runStart(true);
				} else {
					$this->runStart();
				}
				break;
			case self::TASK_IMPORT:
				$this->runImport();
				break;
			case self::TASK_IMPORT_PRODUCTS:
				$this->runImportProducts();
				break;
			case self::TASK_CLEAN:
				$this->clean();
				break;
		}
	}

	public function clean() {
		echo "removing importData\n";
		$importDataFile = Mage::getConfig()->getOptions()->getTmpDir() . DS . 'importData';
		if (file_exists($importDataFile)) {
			unlink(Mage::getConfig()->getOptions()->getTmpDir() . DS . 'importData');
		} else {
			echo "file '$importDataFile' not found\n";
		}
		echo "removing importStatus\n";
		if (file_exists($this->_statusFile)) {
			unlink($this->_statusFile);
		} else {
			echo "file '" . $this->_statusFile . "' not found\n";
		}
	}

    protected function exec ( $cmd ) {
        return passthru($cmd);
    }
    
	public function usageHelp() {
		return <<<USAGE
Usage:  php -f productimport.php -- [options]

  --file <file>					The catalog-xml to import
  --idfile <file>				File containing IDs to import (optional)
  --status active|inactive			Status of products to be imported (optional, default active)
  --start <index>				Index of articles to start importing at (optional, default 0)
  --limit <count>				Number of articles to import from --start (optional, default 50000)
  --task start|import|import_products|clean	(internal use, default start)
  help						This help

USAGE;
	}

}

if ( ! isset($winDebug) ) {
    $shell = new Schracklive_Shell_ProductImport();
    $shell->run();
}

?>

<?php

class Schracklive_Upgrade_Model_Patch {

	protected $_group;
	protected $_module;
	protected $_class;
	protected $_rewriteSource;
	protected $_coreSource;
	protected $_oldCoreSource;

	public function __construct(array $arguments) {
		$this->_group = $arguments['group'];
		$this->_module = $arguments['module'];
		$this->_class = $arguments['class'];
		$this->_oldCoreCodePath = $arguments['oldCoreCodePath'];
	}

	public function run() {
		$rewriteClassPath = Mage::helper('upgrade/config')->getLocalClassPath($this->_group, $this->_module, $this->_class);
		$coreClassPath = Mage::helper('upgrade/config')->getCoreClassPath($this->_group, $this->_module, $this->_class);
		$oldCoreClassPath = Mage::helper('upgrade/config')->getCoreClassPath($this->_group, $this->_module, $this->_class,
						$this->_oldCoreCodePath);

		$this->_rewriteSource = Mage::getModel('upgrade/source', array(file_get_contents($rewriteClassPath), $rewriteClassPath));
		$this->_coreSource = Mage::getModel('upgrade/source', array(file_get_contents($coreClassPath), $coreClassPath));
		$this->_oldCoreSource = Mage::getModel('upgrade/source', array(file_get_contents($oldCoreClassPath), $oldCoreClassPath));

		$this->_createTempFiles();
		if ($this->_writeMergedTempFiles($rewriteClassPath)) {
			$this->_writeDiffedCoreTempFiles($rewriteClassPath.'.core.diff');
			$this->_writeDiffedLocalTempFiles($rewriteClassPath.'.local.diff');
		}
	}

	protected function _createTempFiles() {
		$config = Mage::helper('upgrade/config');

		$tempRewriteClassPath = $config->getLocalClassPath($this->_group, $this->_module, $this->_class, 'diff3-mine-local');
		$tempCoreClassPath = $config->getLocalClassPath($this->_group, $this->_module, $this->_class, 'diff3-your-core');
		$tempOldCoreClassPath = $config->getLocalClassPath($this->_group, $this->_module, $this->_class, 'diff3-old-core');

		$this->_tempRewriteClassPath =  $this->_writeTempFile($tempRewriteClassPath, $this->_rewriteSource->generateClass());
		$this->_tempCoreClassPath = $this->_writeTempFile($tempCoreClassPath, $this->_rewriteSource->generateClass($this->_coreSource->getFunctions()));
		$this->_tempOldCoreClassPath = $this->_writeTempFile($tempOldCoreClassPath, $this->_rewriteSource->generateClass($this->_oldCoreSource->getFunctions()));
	}

	protected function _writeTempFile($filename, $data) {
		$dirname = Mage::helper('upgrade/config')->getTempPath(dirname($filename));
		if (!is_dir($dirname) && !@mkdir($dirname, 0775, true)) {
			throw new RuntimeException('Cannot create temporary directory "'.$dirname.'".');
		}
		$tempFilename = $dirname.DS.basename($filename);
		if (@file_put_contents($tempFilename, $data) === FALSE) {
			throw new RuntimeException('Cannot write temporary file "'.$tempFilename.'".');
		}
		return $tempFilename;
	}

	protected function _writeMergedTempFiles($filename) {
		// diff3 my_file old_file your_file
		$diff3Arguments = $this->_tempRewriteClassPath.' '.$this->_tempOldCoreClassPath.' '.$this->_tempCoreClassPath;
		return $this->_exec('diff3 -m '.$diff3Arguments, $filename);
	}

	protected function _writeDiffedCoreTempFiles($filename) {
		return $this->_exec('diff -u -bwB '.$this->_tempOldCoreClassPath.' '.$this->_tempCoreClassPath, $filename);
	}

	protected function _writeDiffedLocalTempFiles($filename) {
		return $this->_exec('diff -u -bwB '.$this->_tempOldCoreClassPath.' '.$this->_tempRewriteClassPath, $filename);
	}

	protected function _exec($command, $filename) {
		// redirect order is important! stdout goes to file, stderr goes to stdout (the handle, NOT the file)
		exec($command.' 2>&1 > '.$filename, $stdError, $returnValue);
		if (count($stdError) || ($returnValue != 0 && $returnValue != 1)) {
			list($program) = explode(' ', $command);
			throw new RuntimeException($program.' failed with status '.$returnValue.":\n".join("\n",$stdError)."\n");
		}
		return $returnValue;
	}

}

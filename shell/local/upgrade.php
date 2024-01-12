<?php

require_once('shell.php');

class Schracklive_Shell_Upgrade extends Schracklive_Shell {

	public function diffCore($group, $module, $class, $instanceCodePath) {
		$currentVersion = Mage::helper('upgrade/config')->getCoreClassPath($group, $module, $class);
		$newVersion = $instanceCodePath.DS.$classPath;
		passthru('diff -uwBE --strip-trailing-cr '.$currentVersion.' '.$newVersion);
	}

	public function patchClass($group, $module, $class, $oldCoreCodePath) {
		$arguments = array(
			'group' => $group,
			'module' => $module,
			'class' => $class,
			'oldCoreCodePath' => $oldCoreCodePath,
		);
		Mage::getModel('upgrade/patch', $arguments)->run();
	}

	public function showRewrite($group, $module, $class, $void) {
		echo $module->getName(), '::', $class->getName(), ' => ', (string)$class, "\n";
	}

	public function run() {
		$group = 'model';
		if ($this->getArg('group')) {
			$group = $this->getArg('group');
		}
		if ($this->getArg('diff')) {
			if (!is_string($this->getArg('diff'))) {
				$this->help();
			}
			Mage::helper('upgrade/config')->walkRewrites($group, array($this, 'diffCore'), $this->getArg('diff'));
		} elseif ($this->getArg('patch')) {
			if (!is_string($this->getArg('patch'))) {
				$this->help();
			}
			Mage::helper('upgrade/config')->walkRewrites($group, array($this, 'patchClass'), $this->getArg('patch'));
		} else {
			Mage::helper('upgrade/config')->walkRewrites($group, array($this, 'showRewrite'));
		}
	}

}

$upgrade = new Schracklive_Shell_Upgrade();
$upgrade->run();

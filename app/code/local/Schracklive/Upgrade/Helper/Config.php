<?php

class Schracklive_Upgrade_Helper_Config extends Mage_Core_Helper_Abstract {

	public function walkRewrites($group, /* callback */ $function, $argument=NULL) {
		foreach (Mage::getConfig()->getNode('global/'.$group.'s')->children() as $moduleNode) {
			foreach ($moduleNode->children() as $child) {
				if ($child->getName() == 'rewrite') {
					foreach ($child->children() as $classNode) {
						call_user_func($function, $group, $moduleNode, $classNode, $argument);
					}
				}
			}
		}
	}

	public function getCoreClassPath($group, $moduleNode, $classNode, $codeDir='') {
		$className = trim($this->_getClassName($group, $moduleNode->getName().'/'.$classNode->getName()));
		$classPath = 'core'.DS.str_replace('_', DS, $className).'.php';
		return $this->_getCodeDir($codeDir).DS.$classPath;
	}

	public function getLocalClassPath($group, $moduleNode, $classNode, $codeDir='') {
		$classPath = 'local'.DS.str_replace('_', DS, (string)$classNode).'.php';
		return $this->_getCodeDir($codeDir).DS.$classPath;
	}

	protected function _getClassName($groupType, $classId) {
		$classArr = explode('/', trim($classId));
		$group = $classArr[0];
		$class = !empty($classArr[1]) ? $classArr[1] : null;
		$config = Mage::getConfig()->getNode('global')->{$groupType.'s'}->{$group};

		if (!empty($config)) {
			$className = $config->getClassName();
		}
		if (empty($className)) {
			$className = 'mage_'.$group.'_'.$groupType;
		}
		if (!empty($class)) {
			$className .= '_'.$class;
		}
		$className = uc_words($className);

		return $className;
	}

	protected function _getCodeDir($dir) {
		if ($dir) {
			return $dir;
		} else {
			return Mage::getConfig()->getOptions()->getCodeDir();
		}
	}

	public function getTempPath($path) {
		return Mage::getConfig()->getOptions()->getTmpDir().DS.'upgrade'.DS.$path;
	}

}

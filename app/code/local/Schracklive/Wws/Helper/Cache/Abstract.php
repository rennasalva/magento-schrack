<?php

abstract class Schracklive_Wws_Helper_Cache_Abstract {

	const CACHE_GROUP = 'wws';
	const CACHE_LIFETIME = 3600;

	protected $_data = array();
	protected $_prefix;

	protected function _getCacheLifeTime() {
		return self::CACHE_LIFETIME;
	}
	
	abstract public function retrieve();

	abstract public function store(array $infos);

	/**
	 * Fetch unserialized data from cache
	 *
	 * @param CACHE_NAME_* $key
	 *
	 * @return mixed
	 */
	protected function _loadFromCache($key) {
		if (array_key_exists($key, $this->_data)) {
			return $this->_data[$key];
		}
		return $this->_loadFromExternalCache($key);
	}

	protected function _loadFromExternalCache($key) {
		if (Mage::app()->useCache(self::CACHE_GROUP)) {
			$data = unserialize(Mage::app()->loadCache(self::CACHE_GROUP.'_'.$key));
			if (!empty($data)) {
				$this->_data[$key] = $data;
			}
			return $data;
		}
		return null;
	}

	/**
	 * Save single cache item
	 *
	 * @param CACHE_NAME_* $key
	 * @param string $data
	 * @param array tags
	 * @param integer time to live in seconds
	 */
	protected function _storeInCache($key, $data, $tags=array(), $lifeTime=null) {
		$this->_data[$key] = $data;
		$this->_storeInExternalCache($key, $data, $tags, $lifeTime);
	}

	/**
	 * Save single cache item in Magento cache
	 *
	 * @param       $key
	 * @param       $data
	 * @param array $tags
	 * @param null  $lifeTime
	 * @return boolean success
	 */
	protected function _storeInExternalCache($key, $data, $tags=array(), $lifeTime=null) {
		if (Mage::app()->useCache(self::CACHE_GROUP)) {
			if (count($tags) == 0) {
				$tags[] = self::CACHE_GROUP;
			}
			if (!$lifeTime) {
				$lifeTime = $this->_getCacheLifeTime();
			}
			Mage::app()->saveCache(serialize($data), self::CACHE_GROUP.'_'.$key, $tags, $lifeTime);
			return true;
		}
		return false;
	}

	
	abstract protected function _buildCacheKey();

}

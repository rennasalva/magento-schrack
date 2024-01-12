<?php

class Schracklive_SchrackCore_Model_Mysql4_Url_Rewrite extends Mage_Core_Model_Mysql4_Url_Rewrite {

	/**
	 * Load rewrite information for request
	 * If $path is array - we must load all possible records and choose one matching earlier record in array
	 *
	 * @param   Mage_Core_Model_Url_Rewrite $object
	 * @param   array|string $path
	 * @return  Mage_Core_Model_Mysql4_Url_Rewrite
	 */
	public function loadByRequestPath(Mage_Core_Model_Url_Rewrite $object, $path) {
		if (!is_array($path)) {
			$path = array($path);
		}

		$pathBind = array();
		foreach ($path as $key => $url) {
			$pathBind['path'.$key] = rawurldecode($url); // schrack4you - allow for utf8 in URLs
		}
		// Form select
		$read = $this->_getReadAdapter();
		$select = $read->select()
				->from($this->getMainTable())
				->where($this->getMainTable().'.request_path IN (:'.implode(', :', array_flip($pathBind)).')')
				->where('store_id IN(?)', array(0, (int)$object->getStoreId()));

		$items = $read->fetchAll($select, $pathBind);

		// Go through all found records and choose one with lowest penalty - earlier path in array, concrete store
		$mapPenalty = array_flip(array_values($path)); // we got mapping array(path => index), lower index - better
		$currentPenalty = null;
		$foundItem = null;
		foreach ($items as $item) {
			$penalty = $mapPenalty[$item['request_path']] << 1 + ($item['store_id'] ? 0 : 1);
			if (!$foundItem || $currentPenalty > $penalty) {
				$foundItem = $item;
				$currentPenalty = $penalty;
				if (!$currentPenalty) {
					break; // Found best matching item with zero penalty, no reason to continue
				}
			}
		}

		// Set data and finish loading
		if ($foundItem) {
			$object->setData($foundItem);
		}

		// Finish
		$this->unserializeFields($object);
		$this->_afterLoad($object);

		return $this;
	}

}
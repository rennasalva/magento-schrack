<?php

/**
 * Catalog category API extensions
 *
 * @category   Schracklive
 * @package    Schracklive_SchrackCatalog
 * @author     Wolfgang Klinger <wk@plan2.net>
 */
class Schracklive_SchrackCatalog_Model_Category_Api extends Mage_Catalog_Model_Category_Api {

	/**
	 * Returns an array of category attachment infos
	 *
	 * @param integer $categoryId
	 * @return array
	 */
	public function getAttachments($categoryId) {
		$category = $this->_initCategory($categoryId, null); // null = store

		$attachments = $category->getAttachmentsCollection();

		return $attachments->toArray();
	}

	/**
	 * Retrieve category tree
	 *
	 * @param int $parentId
	 * @param string|int $store
	 * @return array
	 */
	public function tree($parentId = null, $store = null) {
		if (is_null($parentId) && !is_null($store)) {
			$parentId = Mage::app()->getStore($this->_getStoreId($store))->getRootCategoryId();
		} elseif (is_null($parentId)) {
			$parentId = 1;
		}

		/* @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
		$tree = Mage::getResourceSingleton('catalog/category_tree')
			->load();

		$root = $tree->getNodeById($parentId);

		if($root && $root->getId() == 1) {
			$root->setName(Mage::helper('catalog')->__('Root'));
		}

		$collection = Mage::getModel('catalog/category')->getCollection()
			->setStoreId($this->_getStoreId($store))
			->addAttributeToSelect('name')
			->addAttributeToSelect('is_active')
			->addAttributeToSelect('url_path');

		$tree->addCollectionData($collection, true);

		return $this->_nodeToArrayWithUrl($root);
	}

	/**
	 * Convert node to array
	 *
	 * @param Varien_Data_Tree_Node $node
	 * @return array
	 */
	protected function _nodeToArrayWithUrl(Varien_Data_Tree_Node $node) {
		// Only basic category data
		$result = array();
		$result['category_id'] = $node->getId();
		$result['parent_id']   = $node->getParentId();
		$result['name']        = $node->getName();
		$result['is_active']   = $node->getIsActive();
		$result['position']    = $node->getPosition();
		$result['level']       = $node->getLevel();
		$result['url_path'] = $node->getUrlPath();
		$result['children']    = array();

		foreach ($node->getChildren() as $child) {
			$result['children'][] = $this->_nodeToArrayWithUrl($child);
		}
		return $result;
	}

}

?>

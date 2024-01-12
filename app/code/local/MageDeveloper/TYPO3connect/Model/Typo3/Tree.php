<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MageDeveloper_TYPO3connect_Model_Typo3_Tree extends Varien_Object
{
	
	/**
	 * Helper
	 * @var MageDeveloper_TYPO3connect_Helper_Data
	 */
	protected $_helper;
	
	/**
	 * XMLAdapter
	 * @var MageDeveloper_TYPO3connect_Model_Typo3_Api_XMLAdapter
	 */
	protected $_xmlAdapter;
	
	public function __construct()
	{
		$this->_helper = Mage::helper('typo3connect');
		$this->_xmlAdapter = Mage::getModel('typo3connect/typo3_api_tree');
		parent::__construct();
	}

	/**
	 * Tree Array
	 * @var array
	 */
	public function getTree($rootNodeUid = null)
	{
		$treeArr = $this->_xmlAdapter->getTree($rootNodeUid);
		$collection = $this->_prepare($treeArr);
		return $collection;
	}

	/**
	 * _prepare
	 * Prepares the tree collection
	 * 
	 * @param array $treeArray Tree Array
	 * @return Varien_Data_Collection
	 */
	protected function _prepare($treeArray)
	{
		$collection = Mage::getModel('typo3connect/typo3_tree_collection');
		
		foreach ($treeArray as $_treeItem) {
			
			$model = Mage::getModel('typo3connect/typo3_page');
			$model->setData($_treeItem);
			
			$_children = $_treeItem['children'];
			unset($_treeItem['children']);
			
			if (is_array($_children)) {
				$model->setChildren( $this->_prepare($_children) );
			}
			$collection->addItem($model);
			
		}
		return $collection;
	}
	
}

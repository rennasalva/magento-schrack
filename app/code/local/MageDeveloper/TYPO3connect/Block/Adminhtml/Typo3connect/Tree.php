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

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Tree extends Mage_Adminhtml_Block_Template
{
	protected $_helper;
	
	
    public function __construct()
    {
        parent::__construct();
		
		$this->_helper = Mage::helper('typo3connect');
		
        $this->setTemplate('typo3connect/tree.phtml');
        $this->setUseAjax(true);
    }
	
	
    public function getLoadTreeUrl($expanded=null)
    {
        $params = array('_current'=>true, 'id'=>null,'store'=>null);
        if (
            (is_null($expanded) && Mage::getSingleton('admin/session')->getIsTreeWasExpanded())
            || $expanded == true) {
            $params['expand_all'] = true;
        }
        return $this->getUrl('*/*/pagesJson', $params);
    }
	
	/**
	 * getRootName
	 * Gets the root name for the tree
	 * 
	 * @return string
	 */
	public function getRootName()
	{
		return $this->_helper->getRootName();
	}
	
    public function getIsWasExpanded()
    {
        return Mage::getSingleton('admin/session')->getIsTreeWasExpanded();
    }

	public function getRoot()
	{
		return null;
	}
	
	/**
	 * getTree
	 * Loads the tree from TYPO3
	 * 
	 * @param int $node Starting Node
	 * @return array|bool
	 */
	public function getTree($rootNodeUid = null)
	{
		$tree = Mage::getModel('typo3connect/typo3_tree')->getTree($rootNodeUid);
		
		if (count($tree) > 0)  {
			return $tree;
		}
		return false;
	}
	
	public function getTreeJson()
	{
		$tree = $this->getTree( $this->getRoot() );
		
		$rootArray['children'] = $this->_buildNodeJson($tree);
			
		$json = Mage::helper('core')->jsonEncode(isset($rootArray['children']) ? $rootArray['children'] : array());
		return $json;
	}
	
	/**
	 * _buildNodeJson
	 * Builds nodes to json string from
	 * given tree nodes
	 * 
	 * @param MageDeveloper_TYPO3connect_Model_Typo3_Tree_Collection $nodes Nodes
	 * @return array
	 */	
    protected function _buildNodeJson($nodes)
    {
		$items = array();
		
		foreach ($nodes as $node) {
			
			$item = array();
			
			if ($node->hasChildren()) {
				$item['children'] = $this->_buildNodeJson( $node->getChildren() );
			}
			
			$item['id']			= $node->getUid();
			$item['text'] 		= $node->getTitle();
			$item['allowDrop'] 	= false;
			$item['allowDrag'] 	= false;
			$item['cls']		= 'folder active-category';
			
			// Assign
			$items[] = $item;
			
		}
		return $items;
    }
	
    /**
     * Check if page loaded by outside link to category edit
     *
     * @return boolean
     */
    public function isClearEdit()
    {
        return false;
    }



}

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

class MageDeveloper_TYPO3connect_Model_Typo3_Api_Tree extends MageDeveloper_TYPO3connect_Model_Typo3_Api_Abstract
{
	/**
	 * Helper
	 * @var MageDeveloper_TYPO3connect_Helper_Data
	 */
	protected $_helper;	
	
	public function __construct()
	{
		$this->_helper = Mage::helper('typo3connect');
	}
	
	
	public function load()
	{
		return;
	}
	
	
	/**
	 * getTree
	 * Gets the page tree
	 * 
	 * @return array
	 */
	public function getTree($rootNodeUid = null)
	{
		$url = $this->_helper->getPagesListUrl();

		$this->setData('url', $url );
		$this->call();
		
		$data = array();
		
		if ($this->getXml()->getNode()) {
			
			if ($rootNodeUid) {
				$node = 'uid_'.$rootNodeUid;
				$data = $this->getXml()->getNode($node)->asArray();	
			} else {
				$data = $this->getXml()->getNode()->asArray();	
			}
		}

		if (sizeof($data)) {
			return $data;
		}
		return array();
	}	
	
	
	
	
}

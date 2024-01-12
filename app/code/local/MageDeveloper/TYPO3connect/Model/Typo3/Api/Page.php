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

class MageDeveloper_TYPO3connect_Model_Typo3_Api_Page extends MageDeveloper_TYPO3connect_Model_Typo3_Api_Abstract
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
	
	/**
	 * getPageByUid
	 * Gets page information by a given uid
	 * 
	 * @param int $uid Page UID
	 * @return array
	 */
	public function getPageByUid($uid)
	{
		$url = $this->_helper->getTypo3UrlByUid($uid);

		$this->setData('url', $url );
		$this->setTypeNum( $this->_helper->getTypeNum() );
		$this->call();
		
		if ($this->getXml()->getNode()) {
			// Get full node as an array
			$data = $this->getXml()->getNode()->asArray();		
	
			// Check if node has uid
			if (array_key_exists('uid', $data) && $data['uid'] == $uid) {
				return $data;
			}
		}
		return array();
	}	
	
	
	
	
}

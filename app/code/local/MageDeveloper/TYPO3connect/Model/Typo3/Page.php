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

class MageDeveloper_TYPO3connect_Model_Typo3_Page extends Varien_Object
{
	const DELETED = 1;
	const NOT_DELETED = 0;
	
	const HIDDEN = 1;
	const NOT_HIDDEN = 0;
	
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
		$this->_xmlAdapter = Mage::getModel('typo3connect/typo3_api_page');
		parent::__construct();
	}
		
	/**
	 * loadByUid
	 * Loads an TYPO3 Page by an given uid
	 * 
	 * @param int $uid UID of the Page
	 * @return self
	 */
	public function loadByUid($uid)
	{
		$pageData = $this->_xmlAdapter->getPageByUid($uid);
		$this->setData($pageData);
		return $this;
	}
	
	/**
	 * getContent
	 * Gets the content
	 * 
	 * @return string
	 */
	public function getContent()
	{
		if ($this->hasData('tt_content')) {
			return $this->getData('tt_content');
		}
		return '';
	}
	
	/**
	 * hasChildren
	 * Determines if the page has children
	 * 
	 * @return bool
	 */
	public function hasChildren()
	{
		if ($this->getData('children')) {
			return true;
		}
		return false;
	}
	
	/**
	 * getId
	 * Get the page uid
	 * 
	 * @return int|null
	 */
	public function getId()
	{
		if ($this->getData('uid')) {
			return $this->getData('uid');
		}
		return null;
	}
	
	/**
	 * getUrlKey
	 * Gets an url key of the page
	 * 
	 * @return string
	 */
	public function getUrlKey()
	{
		
		if ($this->getData('nav_title')) {
			return Mage::helper('typo3connect')->createCodeFromValue( $this->getData('nav_title') );
		}
		return Mage::helper('typo3connect')->createCodeFromValue( $this->getData('title') );;
	}
	
	
	
}
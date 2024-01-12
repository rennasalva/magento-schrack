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

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Page_Abstract extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
    }
	
    public function getEditUrl()
    {
        return $this->getUrl("*/typo3connect/edit", array('_current'=>true, 'store'=>null, '_query'=>false, 'id'=>null, 'parent'=>null));
    }
	
	/**
	 * getPage
	 * Gets the actual page object from
	 * the registry
	 * 
	 * @return MageDeveloper_TYPO3connect_Model_Typo3_Page
	 */
	public function getPage()
	{
		return Mage::registry('page');
	}
	
	/**
	 * getPageUid
	 * Gets the page uid
	 * 
	 * @return int
	 */
	public function getPageUid()
	{
		if ($this->getPage()) {
			return $this->getPage()->getId();
		}
		return 0;
	}
	
	/**
	 * getPageId
	 * Alias for getPageUid
	 * 
	 * @return int
	 */
	public function getPageId()
	{
		return $this->getPageUid();
	}
	
	/**
	 * getPageTitle
	 * Gets the page title
	 * 
	 * @return string
	 */
	public function getPageTitle()
	{
		return $this->getPage()->getTitle();
	}
	
    public function getImportUrl(array $args = array())
    {
        $params = array('_current'=>true);
        $params = array_merge($params, $args);
        return $this->getUrl('*/*/import', $params);
    }
    
	
	
}

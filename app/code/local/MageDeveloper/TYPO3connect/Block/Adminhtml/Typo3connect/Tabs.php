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

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Initialize Tabs
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('page_info_tabs');
        $this->setDestElementId('category_tab_content');
        $this->setTitle(Mage::helper('typo3connect')->__('Page Data'));
        $this->setTemplate('widget/tabshoriz.phtml');
    }
	
    /**
     * Retrieve page object
     *
     * @return MageDeveloper_TYPO3connect_Model_Typo3_Page
     */
    public function getPage()
    {
        return Mage::registry('current_page');
    }
	
    /**
     * Prepare Layout Content
     *
     * @return Mage_Adminhtml_Block_Catalog_Category_Tabs
     */
    protected function _prepareLayout()
    {
		$generalBlock = $this->getLayout()
							 ->createBlock('typo3connect/adminhtml_typo3connect_tabs_general', '')
                			 ->toHtml();
							 
		$contentBlock = $this->getLayout()					 
							 ->createBlock('typo3connect/adminhtml_typo3connect_tabs_content', '')
                			 ->toHtml();
							 
		$metaBlock	  = $this->getLayout()
							 ->createBlock('typo3connect/adminhtml_typo3connect_tabs_meta', '')
							 ->toHtml();	
							 
		$designBlock  = $this->getLayout()
							 ->createBlock('typo3connect/adminhtml_typo3connect_tabs_design', '')
							 ->toHtml();					 
							 				 
		
        $this->addTab('general', array(
            'label'     => Mage::helper('cms')->__('Page Information'),
            'content'   => $generalBlock,
            'active'	=> true
        ));
		
        $this->addTab('content', array(
            'label'     => Mage::helper('cms')->__('Content'),
            'content'   => $contentBlock
        ));
		
        $this->addTab('design', array(
            'label'     => Mage::helper('cms')->__('Design'),
            'content'   => $designBlock
        ));
		
        $this->addTab('meta', array(
            'label'     => Mage::helper('cms')->__('Meta Data'),
            'content'   => $metaBlock
        ));

        return parent::_prepareLayout();
    }
	
	
}

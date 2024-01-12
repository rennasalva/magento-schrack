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

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Edit_Form extends MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Page_Abstract
{
    /**
     * Additional buttons on category page
     *
     * @var array
     */
    protected $_additionalButtons = array();
	
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('typo3connect/edit/form.phtml');
    }
	
	
    protected function _prepareLayout()
    {
        $page = $this->getPage();
		
        $this->setChild('tabs',
           $this->getLayout()->createBlock('typo3connect/adminhtml_typo3connect_tabs', 'tabs')
        );


        // Save button
		$this->setChild('import_button',
			$this->getLayout()->createBlock('adminhtml/widget_button')
					->setData(array(
                        'label'     => Mage::helper('typo3connect')->__('Import Page'),
                        'onclick'   => "categorySubmit('" . $this->getImportUrl() . "', true)",
                        'class' => 'save'
							)
				    )
		);

        return parent::_prepareLayout();
    }
	
    public function getSaveButtonHtml()
    {
		return $this->getChildHtml('import_button');
    }

    public function getTabsHtml()
    {
        return $this->getChildHtml('tabs');
    }

	public function getHeader()
	{
		return Mage::helper('typo3connect')->__('Import Page from TYPO3');
	}
	
    public function isAjax()
    {
        return Mage::app()->getRequest()->isXmlHttpRequest() || Mage::app()->getRequest()->getParam('isAjax');
    }
	
	
	
}

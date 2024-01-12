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

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Tabs_General extends Mage_Adminhtml_Block_Catalog_Form
{
    protected $_page;

    public function __construct()
    {
        parent::__construct();
        $this->setShowGlobalIcon(true);
    }
	
    public function getPage()
    {
        if (!$this->_page) {
            $this->_page = Mage::registry('page');
        }
        return $this->_page;
    }
	
    public function _prepareLayout()
    {
        parent::_prepareLayout();
		
        /* @var $model Mage_Cms_Model_Page */
        $model = Mage::getModel('cms/page');
		
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');
        $form->setDataObject($this->getPage());

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('typo3connect')->__('Page Information')));

        if ($this->getPage()->getId()) {
        	
			
	        $fieldset->addField('title', 'text', array(
	            'name'      => 'title',
	            'label'     => Mage::helper('cms')->__('Page Title'),
	            'title'     => Mage::helper('cms')->__('Page Title'),
	            'required'  => true,
	        ));
		
	        $fieldset->addField('identifier', 'text', array(
	            'name'      => 'identifier',
	            'label'     => Mage::helper('cms')->__('URL Key'),
	            'title'     => Mage::helper('cms')->__('URL Key'),
	            'required'  => true,
	            'class'     => 'validate-identifier',
	            'note'      => Mage::helper('cms')->__('Relative to Website Base URL'),
	            'value'		=> $this->getPage()->getUrlKey()
	        ));
		
	        if (!Mage::app()->isSingleStoreMode()) {
	            $field = $fieldset->addField('store_id', 'multiselect', array(
	                'name'      => 'stores[]',
	                'label'     => Mage::helper('cms')->__('Store View'),
	                'title'     => Mage::helper('cms')->__('Store View'),
	                'required'  => true,
	                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
	            ));
	            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
	            $field->setRenderer($renderer);
	        }
	        else {
	            $fieldset->addField('store_id', 'hidden', array(
	                'name'      => 'stores[]',
	                'value'     => Mage::app()->getStore(true)->getId()
	            ));
	            $model->setStoreId(Mage::app()->getStore(true)->getId());
	        }
			
	        $fieldset->addField('is_active', 'select', array(
	            'label'     => Mage::helper('cms')->__('Status'),
	            'title'     => Mage::helper('cms')->__('Page Status'),
	            'name'      => 'is_active',
	            'required'  => true,
	            'options'   => $model->getAvailableStatuses(),
	            'value'		=> Mage_Cms_Model_Page::STATUS_DISABLED
	        ));
		
		
			
			
			
			// Hidden Fields
            $fieldset->addField('id', 'hidden', array(
                'name'  => 'id',
                'value' => $this->getPage()->getId()
            ));
			
			
        } 
        $form->addValues($this->getPage()->getData());
        $this->setForm($form);
    }
	
	
	
}

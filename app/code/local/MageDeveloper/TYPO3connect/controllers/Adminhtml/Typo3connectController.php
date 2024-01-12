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

class MageDeveloper_TYPO3connect_Adminhtml_Typo3connectController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Initialize requested category and put it into registry.
     * Root category can be returned, if inappropriate store/category is specified
     *
     * @param int $uid
     */
    protected function _initPage()
    {
    	$pageUid = (int) $this->getRequest()->getParam('uid',false);
    	$page = Mage::getModel('typo3connect/typo3_page');
		
    	if ($pageUid) {
    		$page->loadByUid($pageUid);
		} 
		
        Mage::register('page', $page);
        Mage::register('current_page', $page);
		return $page;
	}
	
	public function importAction()
	{
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {
            $data = $this->_filterPostData($data);
			
            //init model and set data
            $model = Mage::getModel('cms/page');
            $model->setData($data);

            //validating
            if (!$this->_validatePostData($data)) {
                $this->_redirect('*/*/edit', array('uid' => $model->getId(), '_current' => true));
                return;
            }
			
            try {
                // save the data
                $model->save();
				
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('cms')->__('The page has been saved.'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);
				
			
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('cms')->__('An error occurred while saving the page.'));
            }

        }
        $url = $this->getUrl('*/*/edit', array('_current' => true, 'uid' => 83));
        $this->getResponse()->setBody(
            '<script type="text/javascript">parent.updateContent("' . $url . '", {});</script>'
        );
	}
	
	
    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'new':
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('cms/page/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('cms/page/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('cms/page');
                break;
        }
    }
	
    /**
     * Validate post data
     *
     * @param array $data
     * @return bool     Return FALSE if someone item is invalid
     */
    protected function _validatePostData($data)
    {
        $errorNo = true;
        if (!empty($data['layout_update_xml']) || !empty($data['custom_layout_update_xml'])) {
            /** @var $validatorCustomLayout Mage_Adminhtml_Model_LayoutUpdate_Validator */
            $validatorCustomLayout = Mage::getModel('adminhtml/layoutUpdate_validator');
            if (!empty($data['layout_update_xml']) && !$validatorCustomLayout->isValid($data['layout_update_xml'])) {
                $errorNo = false;
            }
            if (!empty($data['custom_layout_update_xml'])
            && !$validatorCustomLayout->isValid($data['custom_layout_update_xml'])) {
                $errorNo = false;
            }
            foreach ($validatorCustomLayout->getMessages() as $message) {
                $this->_getSession()->addError($message);
            }
        }
        return $errorNo;
    }
	
		
    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('custom_theme_from', 'custom_theme_to'));
        return $data;
    }
	
    /**
     * editAction
     * Edit Action for displaying
     * all categories
	 * 
     * @return void
     */
    public function editAction()
    {
        $uid = (int) $this->getRequest()->getParam('uid');

        $this->_title( $this->__('Import Pages from TYPO3') );

        if (!($page = $this->_initPage())) {
            return;
        }

		// If page was chosen    	
    	if ($this->getRequest()->getQuery('isAjax')) {

			$this->loadLayout();
				
			$eventResponse = new Varien_Object(array(
				'content' => $this->getLayout()->getBlock('typo3connect.edit')->getFormHtml(),
				'messages' => $this->getLayout()->getMessagesBlock()->getGroupedHtml(),
			));
				
			$this->getResponse()->setBody(
				Mage::helper('core')->jsonEncode($eventResponse->getData())
			);
            return;
		}

  		$this->loadLayout();
        $this->_setActiveMenu('typo3connect/typo3_pages');
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
            ->setContainerCssClass('catalog-categories');

        $this->_addBreadcrumb(Mage::helper('catalog')->__('Manage Catalog Categories'),
             Mage::helper('catalog')->__('Manage Categories')
        );

        $block = $this->getLayout()->getBlock('catalog.wysiwyg.js');

        $this->renderLayout();
		
    }       
	
    /**
     * index action
     */
    public function indexAction()
    {
        $this->_forward('edit');
    }	
	
}
